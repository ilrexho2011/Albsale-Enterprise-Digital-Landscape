<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Models/Order.php';
apply_cors('POST, PUT, OPTIONS');
$d = json_decode((string) file_get_contents('php://input'));
if (!$d || !isset($d->idso)) { json_response(['message' => 'idso is required'], 400); }
$db = (new Database())->connect();
$o = new Order($db);
$o->idso     = (int) $d->idso;
$o->ZINN     = (string) ($d->ZINN ?? '');
$o->saltcode = (int) ($d->saltcode ?? 0);
$o->title    = (string) ($d->title ?? '');
$o->quantity = (int) ($d->quantity ?? 0);
$o->unit     = (string) ($d->unit ?? 'Ton');
$o->value    = (int) ($d->value ?? 0);
$o->currency = (string) ($d->currency ?? 'EU');
json_response(['message' => $o->update() ? 'Sales order updated' : 'Sales order not updated']);
