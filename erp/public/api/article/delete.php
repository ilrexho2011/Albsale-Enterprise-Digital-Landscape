<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Models/Article.php';
apply_cors('POST, DELETE, OPTIONS');
$d = json_decode((string) file_get_contents('php://input'));
if (!$d || !isset($d->saltcode)) { json_response(['message' => 'saltcode is required'], 400); }
$db = (new Database())->connect();
$a = new Article($db);
$a->saltcode = (int) $d->saltcode;
json_response(['message' => $a->delete() ? 'Article deleted' : 'Article not deleted']);
