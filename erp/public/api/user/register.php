<?php
/** Regjistrim i sigurt: password_hash(), pa base64, pa SQL injection. */
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Models/User.php';
require_once ERP_BASE . '/src/Security/auth.php';
apply_cors('POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(['message' => 'Method not allowed'], 405); }
$d = json_decode((string) file_get_contents('php://input'));
if (!$d || empty($d->username) || empty($d->password)) {
    json_response(['message' => 'username and password are required'], 400);
}
if (strlen((string) $d->password) < 8) {
    json_response(['message' => 'Password must be at least 8 characters'], 422);
}
$db = (new Database())->connect();
$u = new User($db);
$u->name     = (string) ($d->name ?? '');
$u->surname  = (string) ($d->surname ?? '');
$u->username = (string) $d->username;
$u->password = hash_password((string) $d->password);   // hash, jo base64
$u->ZINN     = (string) ($d->ZINN ?? '');
$u->email    = filter_var($d->email ?? '', FILTER_SANITIZE_EMAIL);
$u->tel      = (string) ($d->tel ?? '');
if ($u->signup()) {
    json_response(['status' => true, 'message' => 'Registration successful', 'id' => $u->id, 'username' => $u->username], 201);
}
json_response(['status' => false, 'message' => 'User already exists or registration failed'], 409);
