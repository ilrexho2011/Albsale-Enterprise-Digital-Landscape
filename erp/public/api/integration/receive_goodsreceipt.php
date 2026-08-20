<?php
/**
 * receive_goodsreceipt.php — Phase 6: Goods Receipt → RIMBUSH stokun dhe nis BOP.
 * Për çdo artikull: salt.stock += received_qty, on_order -= received_qty; regjistron GR;
 * mbyll PO kur plotësohet; rikonfirmon porositë e mbetura (bop_reconfirm).
 *
 * POST (application/xml) GoodsReceipt; Header: X-Inbound-Token.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Lib/procurement.php';

header('Content-Type: application/json; charset=utf-8');
$cfg = require ERP_BASE . '/src/Config/integration.php';
$token = $_SERVER['HTTP_X_INBOUND_TOKEN'] ?? '';
if ($cfg['inbound_token'] === '' || !hash_equals($cfg['inbound_token'], $token)) {
    json_response(['message' => 'Invalid inbound token'], 401);
}
$xml = simplexml_load_string((string) file_get_contents('php://input'));
if ($xml === false) {
    json_response(['message' => 'Malformed XML'], 400);
}
function nn($v) { $v = trim((string) $v); return $v === '' ? null : $v; }
function nd($v) { $v = trim((string) $v); return ($v === '' || $v === '0000-00-00') ? null : $v; }

$matDoc   = (string) ($xml->Header->MaterialDocument ?? '');
$poNumber = (string) ($xml->Header->PoNumber ?? '');
$corrId   = (string) ($xml->Header->CorrelationId ?? '');
$movement = (string) ($xml->Header->MovementType ?? '101');
$postDate = nd($xml->Header->PostingDate ?? '');
$plant    = (string) ($xml->Header->Plant ?? '1000');

$db = (new Database())->connect();
$db->beginTransaction();
$replenished = [];
$bopTouched = 0;
try {
    foreach (($xml->Items->Item ?? []) as $it) {
        $sc  = (int) ($it->ProductRef ?? 0);
        $qty = (float) ($it->ReceivedQuantity ?? 0);
        if ($sc <= 0 || $qty <= 0) {
            continue;
        }
        // 1) RIMBUSH stokun + ul on_order
        $db->prepare('UPDATE salt SET stock = stock + :q, on_order = GREATEST(0, on_order - :q2) WHERE saltcode = :sc')
           ->execute([':q' => $qty, ':q2' => $qty, ':sc' => $sc]);
        // 2) Regjistro GR
        $db->prepare(
            'INSERT INTO goods_receipt (material_doc, po_number, movement_type, posting_date, plant, saltcode, received_qty, unit, batch, correlation_id)
             VALUES (:md, :po, :mt, :pd, :pl, :sc, :q, :un, :bt, :corr)'
        )->execute([
            ':md' => nn($matDoc), ':po' => nn($poNumber), ':mt' => nn($movement), ':pd' => $postDate,
            ':pl' => nn($plant), ':sc' => $sc, ':q' => $qty, ':un' => nn($it->Unit ?? 'Ton'),
            ':bt' => nn($it->Batch ?? ''), ':corr' => nn($corrId),
        ]);
        // 3) Përditëso received_qty në PO item
        if ($poNumber !== '') {
            $db->prepare('UPDATE purchase_order_item SET received_qty = received_qty + :q WHERE po_number = :po AND saltcode = :sc')
               ->execute([':q' => $qty, ':po' => $poNumber, ':sc' => $sc]);
        }
        // 4) BOP: rikonfirmo backorder-et me stokun e ri
        $bopTouched += bop_reconfirm($db, $sc);
        $replenished[] = ['saltcode' => $sc, 'qty' => $qty];
    }

    // 5) Mbyll PO nëse çdo item u plotësua
    if ($poNumber !== '') {
        $oc = $db->prepare('SELECT COUNT(*) FROM purchase_order_item WHERE po_number = :po AND received_qty < quantity');
        $oc->execute([':po' => $poNumber]);
        $openCnt = (int) $oc->fetchColumn();
        $db->prepare('UPDATE purchase_order SET status = :st WHERE po_number = :po')
           ->execute([':st' => ($openCnt === 0 ? 'CLOSED' : 'RECEIVED'), ':po' => $poNumber]);
    }
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    error_log('receive_goodsreceipt failed: ' . $e->getMessage());
    json_response(['message' => 'Processing error'], 500);
}

json_response([
    'message'        => 'Goods receipt applied',
    'materialDoc'    => $matDoc,
    'poNumber'       => $poNumber,
    'replenished'    => $replenished,
    'bopReconfirmed' => $bopTouched,
]);
