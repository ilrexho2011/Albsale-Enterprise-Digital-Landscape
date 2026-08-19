<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Models/Order.php';
apply_cors('POST, DELETE, OPTIONS');
$d = json_decode((string) file_get_contents('php://input'));
if (!$d || !isset($d->idso)) { json_response(['message' => 'idso is required'], 400); }
$db = (new Database())->connect();
$o = new Order($db);
$o->idso = (int) $d->idso;
json_response(['message' => $o->delete() ? 'Sales order deleted' : 'Sales order not deleted']);
