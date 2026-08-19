<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Models/User.php';
apply_cors('POST, PUT, OPTIONS');
$d = json_decode((string) file_get_contents('php://input'));
if (!$d || !isset($d->id)) { json_response(['message' => 'id is required'], 400); }
$db = (new Database())->connect();
$u = new User($db);
$u->id      = (int) $d->id;
$u->name    = (string) ($d->name ?? '');
$u->surname = (string) ($d->surname ?? '');
$u->ZINN    = (string) ($d->ZINN ?? '');
$u->email   = filter_var($d->email ?? '', FILTER_SANITIZE_EMAIL);
$u->tel     = (string) ($d->tel ?? '');
json_response(['message' => $u->update() ? 'User updated' : 'User not updated']);
