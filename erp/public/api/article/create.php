<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Models/Article.php';
apply_cors('POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(['message' => 'Method not allowed'], 405); }
$d = json_decode((string) file_get_contents('php://input'));
if (!$d || !isset($d->saltcode, $d->title)) { json_response(['message' => 'saltcode and title are required'], 400); }
$db = (new Database())->connect();
$a = new Article($db);
$a->saltcode = (int) $d->saltcode;
$a->title = (string) $d->title;
$a->producer = (string) ($d->producer ?? '');
$a->stock = (int) ($d->stock ?? 0);
$a->unit = (string) ($d->unit ?? 'Ton');
$a->priceperunit = (int) ($d->priceperunit ?? 0);
$a->currency = (string) ($d->currency ?? 'EU');
$ok = $a->create();
json_response(['message' => $ok ? 'Article created' : 'Article not created'], $ok ? 201 : 400);
