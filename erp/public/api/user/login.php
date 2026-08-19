<?php
/** Login i sigurt: prepared statement + password_verify(). Pa SQLi, pa base64. */
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Models/User.php';
require_once ERP_BASE . '/src/Security/auth.php';
apply_cors('POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(['message' => 'Method not allowed'], 405); }
$d = json_decode((string) file_get_contents('php://input'));
$username = (string) ($d->username ?? '');
$password = (string) ($d->password ?? '');
if ($username === '' || $password === '') {
    json_response(['status' => false, 'message' => 'username and password are required'], 400);
}
$db = (new Database())->connect();
$row = (new User($db))->findByUsername($username);
// Mesazh i njëjtë për user/pass gabim (mos zbulo cili ishte i gabuar)
if (!$row || !verify_password($password, (string) $row['password'])) {
    json_response(['status' => false, 'message' => 'Invalid username or password'], 401);
}
secure_session_start();
session_regenerate_id(true);
$_SESSION['user'] = ['id' => $row['id'], 'username' => $row['username'], 'ZINN' => $row['ZINN']];
json_response(['status' => true, 'message' => 'Login successful',
    'id' => $row['id'], 'username' => $row['username'], 'ZINN' => $row['ZINN']]);
