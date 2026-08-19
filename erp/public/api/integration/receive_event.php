<?php
/**
 * receive_event.php — INBOUND: SAP CI -> Salt ERP (ORDRSP / DESADV / INVOIC).
 * CI dërgon dokumentin O2C kanonik (XML). Ky endpoint përditëson statusin e
 * porosisë dhe shkruan një rresht në order_status_history.
 *
 * POST /erp/public/api/integration/receive_event.php   (Content-Type: application/xml)
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

// 2) Nxjerr fushat e përbashkëta
$eventType = (string) ($xml->Header->DocumentType ?? '');   // ORDRSP / DESADV / INVOIC
$corrId    = (string) ($xml->Header->CorrelationId ?? '');
$s4Order   = (string) ($xml->Header->S4OrderId ?? '');
$zinn      = (string) ($xml->Reference->CustomerRef ?? '');
$idso      = (int) ($xml->Reference->SaltOrderRef ?? 0);

// 3) Harto dokumentin -> status + fusha specifike
$map = [
    'ORDRSP' => 'CONFIRMED',
    'DESADV' => 'DELIVERED',
    'INVOIC' => 'INVOICED',
    'REJECT' => 'REJECTED',
];
$status  = $map[$eventType] ?? 'UNKNOWN';
$docRef  = '';
$sets    = ['order_status = :status', 'last_event = :ev', 's4_order_id = :s4'];
$params  = [':status' => $status, ':ev' => $eventType, ':s4' => $s4Order];

if ($eventType === 'ORDRSP') {
    $confQty = (int) ($xml->Confirmation->ConfirmedQuantity ?? 0);
    $sets[]  = 'confirmed_qty = :cq'; $params[':cq'] = $confQty;
    $docRef  = $s4Order;
} elseif ($eventType === 'DESADV') {
    $docRef  = (string) ($xml->Despatch->DeliveryNo ?? '');
    $sets[]  = 'delivery_no = :dn'; $params[':dn'] = $docRef;
} elseif ($eventType === 'INVOIC') {
    $docRef  = (string) ($xml->Invoice->InvoiceNo ?? '');
    $sets[]  = 'invoice_no = :inv'; $params[':inv'] = $docRef;
}

$db = (new Database())->connect();

// 4) Përditëso salesorder-in (me idso ose correlation_id ose s4_order_id)
if ($idso > 0) {
    $where = 'idso = :idso';           $params[':idso'] = $idso;
} elseif ($corrId !== '') {
    $where = 'correlation_id = :corr'; $params[':corr'] = $corrId;
} else {
    $where = 's4_order_id = :s4w';     $params[':s4w']  = $s4Order;
}

$sql = 'UPDATE salesorder SET ' . implode(', ', $sets) . ' WHERE ' . $where;
$db->prepare($sql)->execute($params);

// 5) Shkruaj historinë (audit i pandryshueshëm)
$db->prepare(
    'INSERT INTO order_status_history
       (idso, s4_order_id, zinn, event_type, status, doc_ref, message, correlation_id)
     VALUES (:idso, :s4, :zinn, :ev, :status, :ref, :msg, :corr)'
)->execute([
    ':idso' => $idso ?: null, ':s4' => $s4Order, ':zinn' => $zinn,
    ':ev' => $eventType, ':status' => $status, ':ref' => $docRef,
    ':msg' => (string) ($xml->Header->Message ?? ''), ':corr' => $corrId,
]);

json_response([
    'message'       => 'Event applied',
    'eventType'     => $eventType,
    'status'        => $status,
    'correlationId' => $corrId,
    's4OrderId'     => $s4Order,
]);
