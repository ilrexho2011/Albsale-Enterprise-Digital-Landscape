<?php
/**
 * reserve_atp.php — Phase 6: rezervim aATP real (BOP) për një porosi.
 * Thërret IF_Salt_ATP_Reserve në CI, ruan atp_reservation dhe përditëson salesorder
 * (reservation_id, reserved_qty, backorder_qty). Nëse ka backorder, riblerja vjen nga reorder_check.
 *
 * POST /erp/public/api/integration/reserve_atp.php
 *   Body: {"saltcode":13455, "quantity":50, "date":"2026-09-01", "idso":12}
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Lib/http.php';

apply_cors('POST, OPTIONS');
$cfg = require ERP_BASE . '/src/Config/integration.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(['message' => 'Method not allowed'], 405);
}
$d = json_decode((string) file_get_contents('php://input'));
$saltcode = isset($d->saltcode) ? (int) $d->saltcode : 0;
$quantity = isset($d->quantity) ? (float) $d->quantity : 0.0;
$idso     = isset($d->idso) ? (int) $d->idso : 0;
$date     = preg_replace('/[^0-9\-]/', '', (string) ($d->date ?? date('Y-m-d')));
if ($saltcode <= 0 || $quantity <= 0) {
    json_response(['message' => 'saltcode and quantity are required'], 400);
}
if ($cfg['cpi_atp_reserve_url'] === '') {
    json_response(['message' => 'ATP reserve endpoint not configured'], 500);
}

$payload = json_encode(['material' => (string) $saltcode, 'plant' => '1000',
                        'quantity' => $quantity, 'date' => $date, 'idso' => (string) $idso]);
[$code, $body, $err] = http_send('POST', $cfg['cpi_atp_reserve_url'], $payload,
    ['Content-Type: application/json', 'Accept: application/json', 'Authorization: Bearer ' . $cfg['cpi_token']],
    (int) $cfg['http_timeout']);

if ($code < 200 || $code >= 300) {
    json_response(['message' => 'aATP reserve unavailable', 'error' => $err ?: ('HTTP ' . $code)], 502);
}
$r = json_decode($body, true) ?: [];
$resId = (string) ($r['reservationId'] ?? '');
$conf  = (float) ($r['confirmedQuantity'] ?? 0);
$back  = (float) ($r['backorderQty'] ?? max(0, $quantity - $conf));
$confDate = (string) ($r['confirmedDate'] ?? '');

$db = (new Database())->connect();
$db->beginTransaction();
try {
    $db->prepare(
        'INSERT INTO atp_reservation (reservation_id, idso, saltcode, requested_qty, confirmed_qty, backorder_qty, confirmed_date, status)
         VALUES (:rid, :idso, :sc, :rq, :cq, :bq, :cd, :stt)'
    )->execute([
        ':rid' => $resId ?: null, ':idso' => $idso ?: null, ':sc' => $saltcode, ':rq' => $quantity,
        ':cq' => $conf, ':bq' => $back, ':cd' => ($confDate !== '' ? $confDate : null),
        ':stt' => ($back > 0 ? 'BACKORDER' : 'RESERVED'),
    ]);
    if ($idso > 0) {
        $db->prepare(
            'UPDATE salesorder SET reservation_id = :rid, reserved_qty = :cq, backorder_qty = :bq WHERE idso = :idso'
        )->execute([':rid' => $resId, ':cq' => $conf, ':bq' => $back, ':idso' => $idso]);
    }
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    error_log('reserve_atp failed: ' . $e->getMessage());
    json_response(['message' => 'Processing error'], 500);
}

json_response([
    'reservationId'     => $resId,
    'saltcode'          => $saltcode,
    'requestedQuantity' => $quantity,
    'confirmedQuantity' => $conf,
    'backorderQty'      => $back,
    'confirmedDate'     => $confDate,
    'fullyConfirmed'    => ($back <= 0),
    'note'              => $back > 0 ? 'Backorder — reorder_check do të nisë riblerjen' : 'Fully reserved',
]);
