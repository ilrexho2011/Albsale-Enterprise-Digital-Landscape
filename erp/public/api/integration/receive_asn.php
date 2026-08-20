<?php
/**
 * receive_asn.php — Phase 6: konfirmimi/ASN i furnitorit → përditëson Purchase Order.
 * POST (application/xml) SupplierEvent; Header: X-Inbound-Token.
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
$xml = simplexml_load_string((string) file_get_contents('php://input'));
if ($xml === false) {
    json_response(['message' => 'Malformed XML'], 400);
}
function nd($v) { $v = trim((string) $v); return ($v === '' || $v === '0000-00-00') ? null : $v; }

$eventType = (string) ($xml->Header->EventType ?? '');
$poNumber  = (string) ($xml->Header->PoNumber ?? $xml->Header->CorrelationId ?? '');
$statusMap = ['PO_CONFIRMED' => 'CONFIRMED', 'ASN' => 'CONFIRMED', 'PO_REJECTED' => 'REJECTED'];
$poStatus  = $statusMap[$eventType] ?? 'CONFIRMED';

$db = (new Database())->connect();
$db->beginTransaction();
try {
    $db->prepare('UPDATE purchase_order SET status = :st WHERE po_number = :po')
       ->execute([':st' => $poStatus, ':po' => $poNumber]);

    foreach (($xml->Items->Item ?? []) as $it) {
        $line = (string) ($it->LineNo ?? '');
        $sc   = (int) ($it->ProductRef ?? 0);
        $db->prepare(
            'UPDATE purchase_order_item
                SET confirmed_date = :cd
              WHERE po_number = :po AND (line_no = :ln OR saltcode = :sc)'
        )->execute([':cd' => nd($it->ConfirmedDate ?? ''), ':po' => $poNumber, ':ln' => $line, ':sc' => $sc]);
    }
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    error_log('receive_asn failed: ' . $e->getMessage());
    json_response(['message' => 'Processing error'], 500);
}

json_response(['message' => 'Supplier event applied', 'poNumber' => $poNumber, 'status' => $poStatus]);
