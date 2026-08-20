<?php
/**
 * enqueue_order.php — Phase 2 (async): vë porosinë në outbox dhe e dërgon në mënyrë
 * jo-bllokuese te CI (endpoint asinkron JMS). Kthen shpejt; dispatcher-i garanton dërgimin.
 *
 * POST /erp/public/api/integration/enqueue_order.php   Body: {"idso": 12}
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Lib/outbox.php';

apply_cors('POST, OPTIONS');
$cfg = require ERP_BASE . '/src/Config/integration.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(['message' => 'Method not allowed'], 405);
}
$data = json_decode((string) file_get_contents('php://input'));
$idso = isset($data->idso) ? (int) $data->idso : 0;
if ($idso <= 0) {
    json_response(['message' => 'idso is required'], 400);
}

$db = (new Database())->connect();
$payload = outbox_build_payload($db, $idso, $cfg['sender_id']);
if ($payload === null) {
    json_response(['message' => 'Order not found'], 404);
}
[$order, , $corrId, $xml] = $payload;

// 1) Ruaj në outbox (PENDING) — burimi i së vërtetës për dërgimin
outbox_upsert($db, $idso, (string) $order['ZINN'], $corrId, $xml, (int) $cfg['outbox_max_attempts']);

// 2) Provë e menjëhershme (jo e detyrueshme); dispatcher-i rimerr në rast dështimi
$row = ['correlation_id' => $corrId, 'payload' => $xml, 'attempts' => 0,
        'max_attempts' => (int) $cfg['outbox_max_attempts']];
$res = outbox_try_send($db, $cfg, $row);

json_response([
    'message'       => $res['ok'] ? 'Order queued and dispatched to SAP CI' : 'Order queued (will retry)',
    'correlationId' => $corrId,
    'dispatchStatus'=> $res['status'],
], 202);
