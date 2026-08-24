<?php
/**
 * send_order.php — OUTBOUND: Salt ERP -> SAP CI (dokumenti ORDERS).
 * Merr një `idso`, ndërton XML-in kanonik, e ruan në outbox (at-least-once),
 * e dërgon te iFlow-i INBOUND i CI-t, dhe përditëson statusin.
 *
 * POST /salt/api/integration/send_order.php   Body: {"idso": 12}
 */
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');

include_once '../../config/Database.php';
require_once '../../lib/canonical.php';
$cfg = require '../../config/integration.php';

$database = new Database();
$db = $database->connect();

$data = json_decode(file_get_contents('php://input'));
$idso = isset($data->idso) ? (int)$data->idso : 0;
if ($idso <= 0) {
    http_response_code(400);
    echo json_encode(['message' => 'idso is required']); exit;
}

// 1) Lexo porosinë + klientin
$st = $db->prepare('SELECT * FROM salesorder WHERE idso = :idso LIMIT 1');
$st->execute([':idso' => $idso]);
$order = $st->fetch(PDO::FETCH_ASSOC);
if (!$order) { http_response_code(404); echo json_encode(['message' => 'Order not found']); exit; }

$cst = $db->prepare('SELECT name, surname, email FROM user WHERE ZINN = :z LIMIT 1');
$cst->execute([':z' => $order['ZINN']]);
$cust = $cst->fetch(PDO::FETCH_ASSOC) ?: [];

// 2) Ndërto correlation id + XML kanonik
$corrId = $order['correlation_id'] ?: make_correlation_id($idso, $order['ZINN']);
$xml    = build_orders_canonical($order, $cust, $corrId);

// 3) Ruaj në outbox (idempotent me correlation_id)
$db->prepare(
    'INSERT INTO integration_outbox (idso, zinn, doc_type, correlation_id, payload, status)
     VALUES (:idso, :zinn, "ORDERS", :corr, :payload, "PENDING")
     ON DUPLICATE KEY UPDATE attempts = attempts + 1'
)->execute([':idso' => $idso, ':zinn' => $order['ZINN'], ':corr' => $corrId, ':payload' => $xml]);

// 4) Dërgo te CI
$ch = curl_init($cfg['cpi_orders_url']);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $xml,
    CURLOPT_TIMEOUT        => $cfg['http_timeout'],
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/xml',
        'X-Correlation-Id: ' . $corrId,
        'Authorization: Bearer ' . $cfg['cpi_token'],
    ],
]);
$resp    = curl_exec($ch);
$httpc   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

// 5) Përditëso statusin sipas rezultatit
if ($resp !== false && $httpc >= 200 && $httpc < 300) {
    $db->prepare('UPDATE salesorder SET order_status = "SENT", correlation_id = :c, last_event = "ORDERS"
                  WHERE idso = :idso')
       ->execute([':c' => $corrId, ':idso' => $idso]);
    $db->prepare('UPDATE integration_outbox SET status = "SENT" WHERE correlation_id = :c')
       ->execute([':c' => $corrId]);
    echo json_encode(['message' => 'Order sent to SAP CI', 'correlationId' => $corrId, 'cpiStatus' => $httpc]);
} else {
    $err = $curlErr ?: ('HTTP ' . $httpc);
    $db->prepare('UPDATE integration_outbox SET status = "FAILED", last_error = :e WHERE correlation_id = :c')
       ->execute([':e' => substr($err, 0, 250), ':c' => $corrId]);
    http_response_code(502);
    echo json_encode(['message' => 'Failed to reach SAP CI', 'error' => $err, 'correlationId' => $corrId]);
}
