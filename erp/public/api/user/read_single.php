<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Models/User.php';
apply_cors('GET, OPTIONS');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { json_response(['message' => 'id (int) is required'], 400); }
$db = (new Database())->connect();
$u = new User($db);
$u->id = $id;
if (!$u->read_single()) { json_response(['message' => 'User not found'], 404); }
json_response(['id' => $id, 'name' => $u->name, 'surname' => $u->surname,
    'ZINN' => $u->ZINN, 'email' => $u->email, 'tel' => $u->tel]);
