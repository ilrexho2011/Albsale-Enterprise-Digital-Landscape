<?php
/**
 * check_atp.php — Phase 3 (aATP i vërtetë): kontroll disponueshmërie me sasi/datë të konfirmuar.
 * Thërret iFlow-in IF_Salt_ATP_Check në CI, i cili pyet aATP-në e S/4 (Availability Information)
 * dhe kthen {confirmedQuantity, confirmedDate, shortfall, fullyConfirmed}. Logon te atp_check_log.
 *
 * POST /erp/public/api/integration/check_atp.php
 *   Body: {"saltcode":13455, "quantity":50, "date":"2026-09-01", "plant":"1000"}
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
$plant    = preg_replace('/[^A-Za-z0-9]/', '', (string) ($d->plant ?? '1000'));
$date     = preg_replace('/[^0-9\-]/', '', (string) ($d->date ?? date('Y-m-d')));
if ($saltcode <= 0 || $quantity <= 0) {
    json_response(['message' => 'saltcode and quantity are required'], 400);
}
if ($cfg['cpi_atp_url'] === '') {
    json_response(['message' => 'ATP endpoint not configured'], 500);
}

$payload = json_encode(['material' => (string) $saltcode, 'plant' => $plant,
                        'quantity' => $quantity, 'date' => $date]);
$headers = ['Content-Type: application/json', 'Accept: application/json',
            'Authorization: Bearer ' . $cfg['cpi_token']];
[$code, $body, $err] = http_send('POST', $cfg['cpi_atp_url'], $payload, $headers, (int) $cfg['http_timeout']);

if ($code < 200 || $code >= 300) {
    json_response(['message' => 'aATP service unavailable', 'error' => $err ?: ('HTTP ' . $code)], 502);
}

$r = json_decode($body, true) ?: [];
$confQty  = (float) ($r['confirmedQuantity'] ?? 0);
$confDate = (string) ($r['confirmedDate'] ?? '');
$short    = (float) ($r['shortfall'] ?? max(0, $quantity - $confQty));
$full     = (bool) ($r['fullyConfirmed'] ?? ($short <= 0));

// Log
$db = (new Database())->connect();
$db->prepare(
    'INSERT INTO atp_check_log
        (saltcode, plant, requested_qty, requested_date, confirmed_qty, confirmed_date, shortfall, fully_confirmed)
     VALUES (:s, :p, :rq, :rd, :cq, :cd, :sf, :fc)'
)->execute([
    ':s' => $saltcode, ':p' => $plant, ':rq' => $quantity, ':rd' => ($date ?: null),
    ':cq' => $confQty, ':cd' => ($confDate !== '' ? $confDate : null), ':sf' => $short, ':fc' => $full ? 1 : 0,
]);

json_response([
    'saltcode'          => $saltcode,
    'plant'             => $plant,
    'requestedQuantity' => $quantity,
    'requestedDate'     => $date,
    'availableQuantity' => (float) ($r['availableQuantity'] ?? $confQty),
    'confirmedQuantity' => $confQty,
    'confirmedDate'     => $confDate,
    'shortfall'         => $short,
    'fullyConfirmed'    => $full,
    'source'            => (string) ($r['source'] ?? 'S4_aATP'),
]);
