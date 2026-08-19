<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Models/Order.php';
apply_cors('POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(['message' => 'Method not allowed'], 405); }
$d = json_decode((string) file_get_contents('php://input'));
if (!$d || !isset($d->ZINN, $d->saltcode, $d->quantity)) {
    json_response(['message' => 'ZINN, saltcode and quantity are required'], 400);
}
$db = (new Database())->connect();
$o = new Order($db);
$o->idso     = isset($d->idso) ? (int) $d->idso : null;
$o->ZINN     = (string) $d->ZINN;
$o->saltcode = (int) $d->saltcode;
$o->title    = (string) ($d->title ?? '');
$o->quantity = (int) $d->quantity;
$o->unit     = (string) ($d->unit ?? 'Ton');
$o->value    = (int) ($d->value ?? 0);
$o->currency = (string) ($d->currency ?? 'EU');
$ok = $o->create();
json_response(['message' => $ok ? 'Sales order created' : 'Sales order not created'], $ok ? 201 : 400);
