<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Models/User.php';
apply_cors('POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(['message' => 'Method not allowed'], 405); }
$d = json_decode((string) file_get_contents('php://input'));
if (!$d || !isset($d->name, $d->ZINN)) { json_response(['message' => 'name and ZINN are required'], 400); }
$db = (new Database())->connect();
$u = new User($db);
$u->id      = isset($d->id) ? (int) $d->id : null;
$u->name    = (string) $d->name;
$u->surname = (string) ($d->surname ?? '');
$u->ZINN    = (string) $d->ZINN;
$u->email   = filter_var($d->email ?? '', FILTER_SANITIZE_EMAIL);
$u->tel     = (string) ($d->tel ?? '');
$ok = $u->create();
json_response(['message' => $ok ? 'User created' : 'User not created'], $ok ? 201 : 400);
