<?php
/**
 * check_stock.php — Phase 2 (ATP/Stock): merr disponueshmërinë e një artikulli (saltcode)
 * nga S/4HANA përmes CI (OData). Përdor cache (stock_cache) me TTL për të ulur thirrjet.
 *
 * GET /erp/public/api/integration/check_stock.php?saltcode=13455[&plant=1000][&refresh=1]
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Lib/http.php';

apply_cors('GET, OPTIONS');
$cfg = require ERP_BASE . '/src/Config/integration.php';

$saltcode = filter_input(INPUT_GET, 'saltcode', FILTER_VALIDATE_INT);
$plant    = preg_replace('/[^A-Za-z0-9]/', '', (string) ($_GET['plant'] ?? '1000'));
$refresh  = (string) ($_GET['refresh'] ?? '') === '1';
if (!$saltcode) {
    json_response(['message' => 'saltcode (int) is required'], 400);
}

$db = (new Database())->connect();

// 1) Cache hit brenda TTL-së?
if (!$refresh) {
    $c = $db->prepare('SELECT saltcode, plant, available_qty, atp_qty, unit, source, checked_at,
                       TIMESTAMPDIFF(SECOND, checked_at, NOW()) AS age
                       FROM stock_cache WHERE saltcode = :s AND plant = :p LIMIT 1');
    $c->execute([':s' => $saltcode, ':p' => $plant]);
    $cached = $c->fetch(PDO::FETCH_ASSOC);
    if ($cached && (int) $cached['age'] <= (int) $cfg['stock_cache_ttl']) {
        unset($cached['age']);
        json_response(['cached' => true] + $cached);
    }
}

// 2) Thirr CI -> S/4 OData
if ($cfg['cpi_stock_url'] === '') {
    json_response(['message' => 'Stock endpoint not configured'], 500);
}
$url = $cfg['cpi_stock_url'] . '?material=' . rawurlencode((string) $saltcode) . '&plant=' . rawurlencode($plant);
$headers = ['Accept: application/json', 'Authorization: Bearer ' . $cfg['cpi_token']];
[$code, $body, $err] = http_send('GET', $url, null, $headers, (int) $cfg['http_timeout']);

if ($code < 200 || $code >= 300) {
    // Fallback: kthe cache-n e vjetër nëse ekziston, ndryshe gabim
    $c = $db->prepare('SELECT saltcode, plant, available_qty, atp_qty, unit, source, checked_at
                       FROM stock_cache WHERE saltcode = :s AND plant = :p LIMIT 1');
    $c->execute([':s' => $saltcode, ':p' => $plant]);
    if ($stale = $c->fetch(PDO::FETCH_ASSOC)) {
        json_response(['cached' => true, 'stale' => true] + $stale);
    }
    json_response(['message' => 'Stock service unavailable', 'error' => $err ?: ('HTTP ' . $code)], 502);
}

$data = json_decode($body, true) ?: [];
$avail = (float) ($data['availableQuantity'] ?? 0);
$atp   = (float) ($data['atpQuantity'] ?? $avail);
$unit  = (string) ($data['unit'] ?? '');
$src   = (string) ($data['source'] ?? 'SAP_CI');

// 3) Ruaj në cache
$db->prepare('INSERT INTO stock_cache (saltcode, plant, available_qty, atp_qty, unit, source)
              VALUES (:s, :p, :a, :atp, :u, :src)
              ON DUPLICATE KEY UPDATE available_qty = VALUES(available_qty), atp_qty = VALUES(atp_qty),
                unit = VALUES(unit), source = VALUES(source)')
   ->execute([':s' => $saltcode, ':p' => $plant, ':a' => $avail, ':atp' => $atp, ':u' => $unit, ':src' => $src]);

json_response([
    'cached'            => false,
    'saltcode'          => $saltcode,
    'plant'             => $plant,
    'available_qty'     => $avail,
    'atp_qty'           => $atp,
    'unit'              => $unit,
    'source'            => $src,
]);
