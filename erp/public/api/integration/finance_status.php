<?php
/**
 * finance_status.php — Phase 4: gjendja financiare A/R (open items) e një klienti.
 * Thërret IF_Salt_Finance_Status në CI (OData S/4) dhe kthen JSON me faturat e hapura.
 *
 * GET /erp/public/api/integration/finance_status.php?zinn=I09345R
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Lib/http.php';

apply_cors('GET, OPTIONS');
$cfg = require ERP_BASE . '/src/Config/integration.php';

$zinn = trim((string) ($_GET['zinn'] ?? $_GET['customer'] ?? ''));
if ($zinn === '') {
    json_response(['message' => 'zinn is required'], 400);
}
if ($cfg['cpi_finance_url'] === '') {
    json_response(['message' => 'Finance endpoint not configured'], 500);
}

$url = $cfg['cpi_finance_url'] . '?customer=' . rawurlencode($zinn);
$headers = ['Accept: application/json', 'Authorization: Bearer ' . $cfg['cpi_token']];
[$code, $body, $err] = http_send('GET', $url, null, $headers, (int) $cfg['http_timeout']);

if ($code < 200 || $code >= 300) {
    // Fallback: llogarit nga tabelat lokale (invoice - payment) nëse CI s'përgjigjet
    $db = (new Database())->connect();
    $q = $db->prepare(
        'SELECT i.invoice_no, i.gross_amount AS amount, i.currency, i.due_date,
                COALESCE(fd.fi_status, "POSTED") AS status
         FROM invoice i
         LEFT JOIN finance_document fd ON fd.invoice_no = i.invoice_no
         WHERE i.zinn = :z AND COALESCE(fd.fi_status, "POSTED") <> "CLEARED"
         ORDER BY i.invoice_date DESC'
    );
    $q->execute([':z' => $zinn]);
    $items = $q->fetchAll(PDO::FETCH_ASSOC);
    $total = array_sum(array_map(static fn($r) => (float) $r['amount'], $items));
    json_response(['customer' => $zinn, 'source' => 'ERP_LOCAL_FALLBACK',
                   'openItemCount' => count($items), 'totalOpen' => $total, 'items' => $items]);
}

$data = json_decode($body, true) ?: [];
json_response($data + ['customer' => $zinn]);
