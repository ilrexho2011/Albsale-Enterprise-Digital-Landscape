<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Models/Order.php';
apply_cors('GET, OPTIONS');
$idso = filter_input(INPUT_GET, 'idso', FILTER_VALIDATE_INT);
if (!$idso) { json_response(['message' => 'idso (int) is required'], 400); }
$db = (new Database())->connect();
$o = new Order($db);
$o->idso = $idso;
if (!$o->read_single()) { json_response(['message' => 'Order not found'], 404); }
json_response(['idso' => $idso, 'ZINN' => $o->ZINN, 'saltcode' => $o->saltcode, 'title' => $o->title,
    'quantity' => $o->quantity, 'unit' => $o->unit, 'value' => $o->value, 'currency' => $o->currency]);
