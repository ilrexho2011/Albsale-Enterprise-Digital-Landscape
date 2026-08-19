<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Models/User.php';
apply_cors('POST, DELETE, OPTIONS');
$d = json_decode((string) file_get_contents('php://input'));
if (!$d || !isset($d->id)) { json_response(['message' => 'id is required'], 400); }
$db = (new Database())->connect();
$u = new User($db);
$u->id = (int) $d->id;
json_response(['message' => $u->delete() ? 'User deleted' : 'User not deleted']);
