<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Models/Order.php';
apply_cors('GET, OPTIONS');
$db = (new Database())->connect();
$rows = (new Order($db))->read()->fetchAll(PDO::FETCH_ASSOC);
json_response(['data' => $rows]);
