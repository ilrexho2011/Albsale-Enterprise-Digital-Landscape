<?php
/**
 * receive_warehouse.php — Phase 4 (EWM/Warehouse)
 * INBOUND: SAP CI -> ERP. Përpunon WarehouseEvent (PICKED/PACKED/GOODS_ISSUED) me
 * handling units dhe warehouse tasks. Përditëson salesorder + histori, transaksional.
 *
 * POST /erp/public/api/integration/receive_warehouse.php  (application/xml)
 * Header: X-Inbound-Token
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';

header('Content-Type: application/json; charset=utf-8');
$cfg = require ERP_BASE . '/src/Config/integration.php';

$token = $_SERVER['HTTP_X_INBOUND_TOKEN'] ?? '';
if ($cfg['inbound_token'] === '' || !hash_equals($cfg['inbound_token'], $token)) {
    json_response(['message' => 'Invalid inbound token'], 401);
}

$raw = (string) file_get_contents('php://input');
libxml_use_internal_errors(true);
$xml = simplexml_load_string($raw);
if ($xml === false) {
    json_response(['message' => 'Malformed XML'], 400);
}
function nn($v) { $v = trim((string) $v); return $v === '' ? null : $v; }
function nd($v) { $v = trim((string) $v); return ($v === '' || $v === '0000-00-00') ? null : $v; }

$eventType  = (string) ($xml->Header->EventType ?? '');
$corrId     = (string) ($xml->Header->CorrelationId ?? '');
$deliveryNo = (string) ($xml->Header->DeliveryNo ?? '');
$s4Order    = (string) ($xml->Header->S4OrderId ?? '');
$warehouse  = (string) ($xml->Header->Warehouse ?? '');
$zinn       = (string) ($xml->Reference->CustomerRef ?? '');
$idso       = (int) ($xml->Reference->SaltOrderRef ?? 0);

$gi = $xml->GoodsIssue;
$giDate = nd($gi->GIDate ?? '');

$db = (new Database())->connect();
$db->beginTransaction();
try {
    // 1) Log i ngjarjes (append)
    $db->prepare(
        'INSERT INTO warehouse_event
            (event_type, delivery_no, idso, zinn, s4_order_id, warehouse, movement_type,
             gi_date, total_qty, unit, correlation_id)
         VALUES (:et, :dn, :idso, :zinn, :s4, :wh, :mt, :gd, :tq, :un, :corr)'
    )->execute([
        ':et' => $eventType, ':dn' => nn($deliveryNo), ':idso' => $idso ?: null, ':zinn' => nn($zinn),
        ':s4' => nn($s4Order), ':wh' => nn($warehouse), ':mt' => nn($gi->MovementType ?? ''),
        ':gd' => $giDate, ':tq' => nn($gi->TotalQuantity ?? ''), ':un' => nn($gi->Unit ?? ''),
        ':corr' => nn($corrId),
    ]);

    // 2) Handling units (rifresko sipas delivery)
    if ($deliveryNo !== '' && isset($xml->HandlingUnits)) {
        $db->prepare('DELETE FROM handling_unit WHERE delivery_no = :dn')->execute([':dn' => $deliveryNo]);
        $insHU = $db->prepare(
            'INSERT INTO handling_unit (delivery_no, hu_id, pack_material, gross_weight, weight_unit, tracking_no)
             VALUES (:dn, :hu, :pm, :gw, :wu, :trk)'
        );
        foreach (($xml->HandlingUnits->HandlingUnit ?? []) as $hu) {
            if (trim((string) ($hu->HuId ?? '')) === '') continue;
            $insHU->execute([
                ':dn' => $deliveryNo, ':hu' => (string) $hu->HuId, ':pm' => nn($hu->PackMaterial ?? ''),
                ':gw' => nn($hu->GrossWeight ?? ''), ':wu' => nn($hu->WeightUnit ?? ''), ':trk' => nn($hu->TrackingNo ?? ''),
            ]);
        }
    }

    // 3) Warehouse tasks (rifresko sipas delivery)
    if ($deliveryNo !== '' && isset($xml->Tasks)) {
        $db->prepare('DELETE FROM warehouse_task WHERE delivery_no = :dn')->execute([':dn' => $deliveryNo]);
        $insWT = $db->prepare(
            'INSERT INTO warehouse_task (delivery_no, task_id, product_ref, picked_qty, unit, source_bin, dest_bin, status)
             VALUES (:dn, :ti, :pr, :pq, :un, :sb, :db, :st)'
        );
        foreach (($xml->Tasks->Task ?? []) as $t) {
            $insWT->execute([
                ':dn' => $deliveryNo, ':ti' => nn($t->TaskId ?? ''), ':pr' => nn($t->ProductRef ?? ''),
                ':pq' => nn($t->PickedQuantity ?? ''), ':un' => nn($t->Unit ?? ''),
                ':sb' => nn($t->SourceBin ?? ''), ':db' => nn($t->DestBin ?? ''), ':st' => nn($t->Status ?? ''),
            ]);
        }
    }

    // 4) Përditëso salesorder
    $sets = ['warehouse_status = :ws']; $params = [':ws' => $eventType];
    if ($giDate !== null) { $sets[] = 'gi_date = :gd'; $params[':gd'] = $giDate; }
    if ($idso > 0)          { $where = 'idso = :idso';           $params[':idso'] = $idso; }
    elseif ($corrId !== '') { $where = 'correlation_id = :corr'; $params[':corr'] = $corrId; }
    else                    { $where = 's4_order_id = :s4w';     $params[':s4w']  = $s4Order; }
    $db->prepare('UPDATE salesorder SET ' . implode(', ', $sets) . ' WHERE ' . $where)->execute($params);

    // 5) Histori
    $db->prepare(
        'INSERT INTO order_status_history (idso, s4_order_id, zinn, event_type, status, doc_ref, message, correlation_id)
         VALUES (:idso, :s4, :zinn, :ev, :status, :ref, :msg, :corr)'
    )->execute([
        ':idso' => $idso ?: null, ':s4' => $s4Order, ':zinn' => $zinn, ':ev' => 'WH_' . $eventType,
        ':status' => $eventType, ':ref' => $deliveryNo, ':msg' => (string) ($xml->Header->Message ?? ''), ':corr' => $corrId,
    ]);

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    error_log('receive_warehouse failed: ' . $e->getMessage());
    json_response(['message' => 'Processing error'], 500);
}

json_response(['message' => 'Warehouse event applied', 'eventType' => $eventType,
               'deliveryNo' => $deliveryNo, 'correlationId' => $corrId]);
