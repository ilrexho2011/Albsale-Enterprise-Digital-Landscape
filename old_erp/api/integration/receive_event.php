<?php
/**
 * receive_event.php — INBOUND: SAP CI -> Salt ERP (ORDRSP / DESADV / INVOIC).
 * CI dërgon dokumentin O2C kanonik (XML). Ky endpoint përditëson statusin e
 * porosisë dhe shkruan një rresht në order_status_history.
 *
 * POST /salt/api/integration/receive_event.php   (Content-Type: application/xml)
 * Header: X-Inbound-Token
 */
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

include_once '../../config/Database.php';
$cfg = require '../../config/integration.php';

// 1) Autentikim i thjeshtë me token
$token = $_SERVER['HTTP_X_INBOUND_TOKEN'] ?? '';
if (!hash_equals($cfg['inbound_token'], $token)) {
    http_response_code(401);
    echo json_encode(['message' => 'Invalid inbound token']); exit;
}

$raw = file_get_contents('php://input');
libxml_use_internal_errors(true);
$xml = simplexml_load_string($raw);
if ($xml === false) {
    http_response_code(400);
    echo json_encode(['message' => 'Malformed XML']); exit;
}

// 2) Nxjerr fushat e përbashkëta
$eventType = (string)($xml->Header->DocumentType ?? '');   // ORDRSP / DESADV / INVOIC
$corrId    = (string)($xml->Header->CorrelationId ?? '');
$s4Order   = (string)($xml->Header->S4OrderId ?? '');
$zinn      = (string)($xml->Reference->CustomerRef ?? '');
$idso      = (int)($xml->Reference->SaltOrderRef ?? 0);

// 3) Harto dokumentin -> status + fusha specifike
$map = [
    'ORDRSP' => 'CONFIRMED',
    'DESADV' => 'DELIVERED',
    'INVOIC' => 'INVOICED',
    'REJECT' => 'REJECTED',
];
$status  = $map[$eventType] ?? 'UNKNOWN';
$docRef  = '';
$confQty = null;
$sets    = ['order_status = :status', 'last_event = :ev', 's4_order_id = :s4'];
$params  = [':status' => $status, ':ev' => $eventType, ':s4' => $s4Order];

if ($eventType === 'ORDRSP') {
    $confQty = (int)($xml->Confirmation->ConfirmedQuantity ?? 0);
    $sets[]  = 'confirmed_qty = :cq'; $params[':cq'] = $confQty;
    $docRef  = $s4Order;
} elseif ($eventType === 'DESADV') {
    $docRef  = (string)($xml->Despatch->DeliveryNo ?? '');
    $sets[]  = 'delivery_no = :dn'; $params[':dn'] = $docRef;
} elseif ($eventType === 'INVOIC') {
    $docRef  = (string)($xml->Invoice->InvoiceNo ?? '');
    $sets[]  = 'invoice_no = :inv'; $params[':inv'] = $docRef;
}

$db = (new Database())->connect();

// 4) Përditëso salesorder-in (me correlation_id ose idso ose s4_order_id)
$where = $idso > 0 ? 'idso = :idso'
       : ($corrId !== '' ? 'correlation_id = :corr' : 's4_order_id = :s4w');
if ($idso > 0)            $params[':idso'] = $idso;
elseif ($corrId !== '')  $params[':corr'] = $corrId;
else                     $params[':s4w']  = $s4Order;

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
    ':msg' => (string)($xml->Header->Message ?? ''), ':corr' => $corrId,
]);

echo json_encode([
    'message'       => 'Event applied',
    'eventType'     => $eventType,
    'status'        => $status,
    'correlationId' => $corrId,
    's4OrderId'     => $s4Order,
]);
