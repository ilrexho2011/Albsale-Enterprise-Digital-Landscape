<?php
/**
 * read_by_customer.php — Kthen porositë e NJË klienti (self-service).
 * Klienti sheh vetëm informacionin që i takon palës së vet (filtruar me ZINN).
 *
 * GET /salt/api/order/read_by_customer.php?zinn=I09345R
 */
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

include_once '../../config/Database.php';

$zinn = $_GET['zinn'] ?? '';
if ($zinn === '') { http_response_code(400); echo json_encode(['message' => 'zinn is required']); exit; }

$db = (new Database())->connect();
$stmt = $db->prepare(
    'SELECT idso, s4_order_id, ZINN, saltcode, title, quantity, unit, value, currency,
            order_status, confirmed_qty, delivery_no, invoice_no, created, updated
     FROM salesorder WHERE ZINN = :z ORDER BY created DESC'
);
$stmt->execute([':z' => $zinn]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows ?: []);
