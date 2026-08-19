<?php
/** Self-service: klienti sheh vetëm porositë e veta (filtruar me ZINN). */
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Models/Order.php';
apply_cors('GET, OPTIONS');
$zinn = trim((string) filter_input(INPUT_GET, 'zinn'));
if ($zinn === '') { json_response(['message' => 'zinn is required'], 400); }
$db = (new Database())->connect();
$rows = (new Order($db))->readByCustomer($zinn);
json_response(['data' => $rows]);
