<?php
/**
 * receive_event.php — v2 (Phase 3)
 * INBOUND: SAP CI -> Salt ERP. Përpunon ORDRSP, DELVRY03 (DESADV) dhe INVOIC02 (INVOIC)
 * me DETAJE TË PLOTA (artikuj, shuma, data). Përditëson salesorder + shkruan histori,
 * dhe persiston dërgesat/faturat në tabelat delivery/invoice (+ items).
 *
 * POST /erp/public/api/integration/receive_event.php  (Content-Type: application/xml)
 * Header: X-Inbound-Token
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';

header('Content-Type: application/json; charset=utf-8');
$cfg = require ERP_BASE . '/src/Config/integration.php';

// 1) Autentikim me token (constant-time)
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

/** Kthen NULL për string bosh (që kolonat DATE/DECIMAL të mos marrin ''). */
function nn($v) { $v = trim((string) $v); return $v === '' ? null : $v; }
function nd($v) { $v = trim((string) $v); return ($v === '' || $v === '0000-00-00') ? null : $v; }

// 2) Fushat e përbashkëta
$eventType = (string) ($xml->Header->DocumentType ?? '');
$corrId    = (string) ($xml->Header->CorrelationId ?? '');
$s4Order   = (string) ($xml->Header->S4OrderId ?? '');
$zinn      = (string) ($xml->Reference->CustomerRef ?? '');
$idso      = (int) ($xml->Reference->SaltOrderRef ?? 0);

$map = ['ORDRSP' => 'CONFIRMED', 'DESADV' => 'DELIVERED', 'INVOIC' => 'INVOICED', 'REJECT' => 'REJECTED'];
$status = $map[$eventType] ?? 'UNKNOWN';
$docRef = '';

$sets   = ['order_status = :status', 'last_event = :ev', 's4_order_id = :s4'];
$params = [':status' => $status, ':ev' => $eventType, ':s4' => $s4Order];

$db = (new Database())->connect();
$db->beginTransaction();
try {
    // 3) Specifikat sipas llojit + persistim i detajeve
    if ($eventType === 'ORDRSP') {
        $confQty  = (float) ($xml->Confirmation->ConfirmedQuantity ?? 0);
        $confDate = nd($xml->Confirmation->ConfirmedDate ?? '');
        $sets[] = 'confirmed_qty = :cq';   $params[':cq'] = $confQty;
        $sets[] = 'confirmed_date = :cd';  $params[':cd'] = $confDate;
        $docRef = $s4Order;

    } elseif ($eventType === 'DESADV') {
        $d = $xml->Despatch;
        $docRef = (string) ($d->DeliveryNo ?? '');
        $delivDate = nd($d->DeliveryDate ?? '');
        $sets[] = 'delivery_no = :dn';    $params[':dn'] = $docRef;
        $sets[] = 'delivery_date = :dd';  $params[':dd'] = $delivDate;

        // Persist delivery header (idempotent)
        $db->prepare(
            'INSERT INTO delivery
               (delivery_no, idso, zinn, s4_order_id, delivery_date, incoterms, carrier,
                tracking_no, ship_to, gross_weight, weight_unit, correlation_id)
             VALUES (:dn, :idso, :zinn, :s4, :dd, :inco, :carr, :trk, :ship, :gw, :wu, :corr)
             ON DUPLICATE KEY UPDATE delivery_date=VALUES(delivery_date), carrier=VALUES(carrier),
                tracking_no=VALUES(tracking_no), ship_to=VALUES(ship_to),
                gross_weight=VALUES(gross_weight), weight_unit=VALUES(weight_unit)'
        )->execute([
            ':dn' => $docRef, ':idso' => $idso ?: null, ':zinn' => nn($zinn), ':s4' => nn($s4Order),
            ':dd' => $delivDate, ':inco' => nn($d->Incoterms ?? ''), ':carr' => nn($d->Carrier ?? ''),
            ':trk' => nn($d->TrackingNo ?? ''), ':ship' => nn($d->ShipToParty ?? ''),
            ':gw' => nn($d->TotalGrossWeight ?? ''), ':wu' => nn($d->WeightUnit ?? ''), ':corr' => nn($corrId),
        ]);
        // Rifresko items (fshi + rifut, idempotent)
        $db->prepare('DELETE FROM delivery_item WHERE delivery_no = :dn')->execute([':dn' => $docRef]);
        $insDI = $db->prepare(
            'INSERT INTO delivery_item (delivery_no, line_no, product_ref, description, delivered_qty, unit, batch)
             VALUES (:dn, :ln, :pr, :ds, :qty, :un, :bt)'
        );
        foreach (($d->Items->Item ?? []) as $it) {
            $insDI->execute([
                ':dn' => $docRef, ':ln' => nn($it->LineNo ?? ''), ':pr' => nn($it->ProductRef ?? ''),
                ':ds' => nn($it->Description ?? ''), ':qty' => nn($it->DeliveredQuantity ?? ''),
                ':un' => nn($it->Unit ?? ''), ':bt' => nn($it->Batch ?? ''),
            ]);
        }

    } elseif ($eventType === 'INVOIC') {
        $inv = $xml->Invoice;
        $docRef = (string) ($inv->InvoiceNo ?? '');
        $invDate = nd($inv->InvoiceDate ?? '');
        $gross   = nn($inv->GrossAmount ?? '');
        $sets[] = 'invoice_no = :inv';    $params[':inv'] = $docRef;
        $sets[] = 'invoice_date = :idt';  $params[':idt'] = $invDate;
        $sets[] = 'gross_amount = :ga';   $params[':ga']  = $gross;

        $db->prepare(
            'INSERT INTO invoice
               (invoice_no, idso, zinn, s4_order_id, invoice_date, due_date, currency,
                net_amount, tax_amount, gross_amount, correlation_id)
             VALUES (:inv, :idso, :zinn, :s4, :idt, :due, :cur, :net, :tax, :gross, :corr)
             ON DUPLICATE KEY UPDATE invoice_date=VALUES(invoice_date), due_date=VALUES(due_date),
                net_amount=VALUES(net_amount), tax_amount=VALUES(tax_amount), gross_amount=VALUES(gross_amount)'
        )->execute([
            ':inv' => $docRef, ':idso' => $idso ?: null, ':zinn' => nn($zinn), ':s4' => nn($s4Order),
            ':idt' => $invDate, ':due' => nd($inv->DueDate ?? ''), ':cur' => nn($inv->Currency ?? ''),
            ':net' => nn($inv->NetAmount ?? ''), ':tax' => nn($inv->TaxAmount ?? ''),
            ':gross' => $gross, ':corr' => nn($corrId),
        ]);
        $db->prepare('DELETE FROM invoice_item WHERE invoice_no = :inv')->execute([':inv' => $docRef]);
        $insII = $db->prepare(
            'INSERT INTO invoice_item (invoice_no, line_no, product_ref, description, quantity, unit, net_value, tax_rate)
             VALUES (:inv, :ln, :pr, :ds, :qty, :un, :nv, :tr)'
        );
        foreach (($inv->Items->Item ?? []) as $it) {
            $insII->execute([
                ':inv' => $docRef, ':ln' => nn($it->LineNo ?? ''), ':pr' => nn($it->ProductRef ?? ''),
                ':ds' => nn($it->Description ?? ''), ':qty' => nn($it->Quantity ?? ''),
                ':un' => nn($it->Unit ?? ''), ':nv' => nn($it->NetValue ?? ''), ':tr' => nn($it->TaxRate ?? ''),
            ]);
        }
    }

    // 4) Përditëso salesorder-in
    if ($idso > 0)              { $where = 'idso = :idso';           $params[':idso'] = $idso; }
    elseif ($corrId !== '')     { $where = 'correlation_id = :corr'; $params[':corr'] = $corrId; }
    else                        { $where = 's4_order_id = :s4w';     $params[':s4w']  = $s4Order; }
    $db->prepare('UPDATE salesorder SET ' . implode(', ', $sets) . ' WHERE ' . $where)->execute($params);

    // 5) Histori (audit i pandryshueshëm)
    $db->prepare(
        'INSERT INTO order_status_history
           (idso, s4_order_id, zinn, event_type, status, doc_ref, message, correlation_id)
         VALUES (:idso, :s4, :zinn, :ev, :status, :ref, :msg, :corr)'
    )->execute([
        ':idso' => $idso ?: null, ':s4' => $s4Order, ':zinn' => $zinn,
        ':ev' => $eventType, ':status' => $status, ':ref' => $docRef,
        ':msg' => (string) ($xml->Header->Message ?? ''), ':corr' => $corrId,
    ]);

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    error_log('receive_event failed: ' . $e->getMessage());
    json_response(['message' => 'Processing error'], 500);
}

json_response([
    'message'       => 'Event applied',
    'eventType'     => $eventType,
    'status'        => $status,
    'correlationId' => $corrId,
    's4OrderId'     => $s4Order,
    'docRef'        => $docRef,
]);
