<?php
/**
 * reorder_check.php — Phase 6 (worker): skanon stokun nën reorder point dhe krijon
 * Purchase Orders (PO) drejt CI (IF_Salt_PO_Send). Idempotent me flag `on_order`.
 *
 * CLI:  php public/api/integration/reorder_check.php
 * HTTP: GET ...reorder_check.php?token=DISPATCH_TOKEN
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Lib/http.php';
require_once ERP_BASE . '/src/Lib/procurement.php';

$cfg = require ERP_BASE . '/src/Config/integration.php';
$isCli = (PHP_SAPI === 'cli');
if (!$isCli) {
    $expected = (string) env('DISPATCH_TOKEN', '');
    if ($expected === '' || !hash_equals($expected, (string) ($_GET['token'] ?? ''))) {
        json_response(['message' => 'Forbidden'], 403);
    }
}

$db = (new Database())->connect();
$needed = $db->query('SELECT * FROM v_reorder_needed')->fetchAll(PDO::FETCH_ASSOC);

$summary = ['checked' => count($needed), 'created' => 0, 'sent' => 0, 'failed' => 0, 'pos' => []];
foreach ($needed as $row) {
    $saltcode = (int) $row['saltcode'];
    $qty      = (int) ($row['reorder_qty'] > 0 ? $row['reorder_qty'] : max(1, $row['reorder_point'] * 2 - $row['stock']));
    $supplier = (string) ($row['supplier_id'] ?? ($row['producer'] ?: 'DEFAULT_SUP'));
    $poNumber = make_po_number($saltcode);
    $plant    = '1000';

    // 1) Persist PO (CREATED) + item, dhe rezervo "on_order"
    $db->beginTransaction();
    try {
        $db->prepare(
            'INSERT INTO purchase_order (po_number, supplier_id, po_date, currency, plant, status, total_value, correlation_id)
             VALUES (:po, :sup, CURDATE(), :cur, :pl, "CREATED", 0, :po2)'
        )->execute([':po' => $poNumber, ':sup' => $supplier, ':cur' => 'EU', ':pl' => $plant, ':po2' => $poNumber]);
        $db->prepare(
            'INSERT INTO purchase_order_item (po_number, line_no, saltcode, description, quantity, unit, delivery_date)
             VALUES (:po, "10", :sc, :d, :q, "Ton", DATE_ADD(CURDATE(), INTERVAL 7 DAY))'
        )->execute([':po' => $poNumber, ':sc' => $saltcode, ':d' => (string) $row['title'], ':q' => $qty]);
        $db->prepare('UPDATE salt SET on_order = on_order + :q WHERE saltcode = :sc')
           ->execute([':q' => $qty, ':sc' => $saltcode]);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        $summary['failed']++;
        continue;
    }
    $summary['created']++;

    // 2) Dërgo PO te CI (IF_Salt_PO_Send)
    if ($cfg['cpi_po_send_url'] !== '') {
        $xml = build_po_canonical($poNumber, $supplier, 'EU', $plant, [[
            'line_no' => '10', 'saltcode' => $saltcode, 'description' => (string) $row['title'],
            'quantity' => $qty, 'unit' => 'Ton', 'price' => 0,
            'delivery_date' => date('Y-m-d', strtotime('+7 days')),
        ]]);
        [$code] = http_send('POST', $cfg['cpi_po_send_url'], $xml, [
            'Content-Type: application/xml', 'X-Correlation-Id: ' . $poNumber,
            'Authorization: Bearer ' . $cfg['cpi_token'],
        ], (int) $cfg['http_timeout']);
        if ($code >= 200 && $code < 300) {
            $db->prepare('UPDATE purchase_order SET status = "SENT" WHERE po_number = :po')->execute([':po' => $poNumber]);
            $summary['sent']++;
        } else {
            $summary['failed']++;
        }
    }
    $summary['pos'][] = ['po' => $poNumber, 'saltcode' => $saltcode, 'qty' => $qty, 'supplier' => $supplier];
}

$out = ['message' => 'Reorder check complete'] + $summary;
if ($isCli) {
    fwrite(STDOUT, json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL);
} else {
    json_response($out);
}
