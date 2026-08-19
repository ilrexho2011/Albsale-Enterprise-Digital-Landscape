<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Models/Article.php';
apply_cors('GET, OPTIONS');
$saltcode = filter_input(INPUT_GET, 'saltcode', FILTER_VALIDATE_INT);
if (!$saltcode) { json_response(['message' => 'saltcode (int) is required'], 400); }
$db = (new Database())->connect();
$a = new Article($db);
$a->saltcode = $saltcode;
if (!$a->read_single()) { json_response(['message' => 'Article not found'], 404); }
json_response(['saltcode' => $saltcode, 'title' => $a->title, 'producer' => $a->producer,
    'stock' => $a->stock, 'unit' => $a->unit, 'priceperunit' => $a->priceperunit, 'currency' => $a->currency]);
