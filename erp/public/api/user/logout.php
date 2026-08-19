<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Security/auth.php';
apply_cors('POST, GET, OPTIONS');
secure_session_start();
$_SESSION = [];
session_destroy();
json_response(['status' => true, 'message' => 'Logged out']);
