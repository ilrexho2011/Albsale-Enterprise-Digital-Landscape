<?php
/**
 * receive_finance.php — Phase 4 (Finance/FI)
 * INBOUND: SAP CI -> ERP. Përpunon FinanceEvent: FI_POSTED (dokument kontabël i faturës)
 * dhe PAYMENT_CLEARED (clearing/pagesë). Përditëson salesorder + histori, transaksional.
 *
 * POST /erp/public/api/integration/receive_finance.php  (application/xml)
 * Header: X-Inbound-Token
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';

header('Content-Type: application/json; charset=utf-8');
$cfg = require ERP_BASE . '/src/Config/integration.php';

$token = $_SERVER['HTTP_X_INBOUND_TOKEN'] ?? '';
if ($cfg['inbound_token'] === '' || !hash_equals($cfg['inbound_token'], $token)) {
    json_response(['message' => 'Invalid inbound token'], 401);
}

$raw = (string) file_get_contents('php://input');
libxml_use_internal_errors(true);
$xml = simplexml_load_string($raw);
if ($xml === false) {
    json_response(['message' => 'Malformed XML'], 400);
}
function nn($v) { $v = trim((string) $v); return $v === '' ? null : $v; }
function nd($v) { $v = trim((string) $v); return ($v === '' || $v === '0000-00-00') ? null : $v; }

$eventType = (string) ($xml->Header->EventType ?? '');
$corrId    = (string) ($xml->Header->CorrelationId ?? '');
$s4Order   = (string) ($xml->Header->S4OrderId ?? '');
$zinn      = (string) ($xml->Reference->CustomerRef ?? '');
$idso      = (int) ($xml->Reference->SaltOrderRef ?? 0);
$invoiceNo = (string) ($xml->Reference->InvoiceNo ?? '');
$docRef    = '';

$db = (new Database())->connect();
$db->beginTransaction();
try {
    if ($eventType === 'FI_POSTED') {
        $a = $xml->Accounting;
        $docRef = (string) ($a->AccountingDoc ?? '');
        $db->prepare(
            'INSERT INTO finance_document
                (accounting_doc, company_code, fiscal_year, idso, zinn, invoice_no, s4_order_id,
                 posting_date, document_type, amount, currency, fi_status, correlation_id)
             VALUES (:ad, :cc, :fy, :idso, :zinn, :inv, :s4, :pd, :dt, :amt, :cur, "POSTED", :corr)
             ON DUPLICATE KEY UPDATE posting_date=VALUES(posting_date), amount=VALUES(amount),
                currency=VALUES(currency), fi_status="POSTED"'
        )->execute([
            ':ad' => $docRef ?: 'UNKNOWN', ':cc' => (string) ($a->CompanyCode ?? '1000'),
            ':fy' => (string) ($a->FiscalYear ?? ''), ':idso' => $idso ?: null, ':zinn' => nn($zinn),
            ':inv' => nn($invoiceNo), ':s4' => nn($s4Order), ':pd' => nd($a->PostingDate ?? ''),
            ':dt' => nn($a->DocumentType ?? ''), ':amt' => nn($a->Amount ?? ''),
            ':cur' => nn($a->Currency ?? ''), ':corr' => nn($corrId),
        ]);

        $sets = ['fi_doc = :fd', 'fi_status = :fs'];
        $params = [':fd' => $docRef, ':fs' => 'POSTED'];

    } elseif ($eventType === 'PAYMENT_CLEARED') {
        $p = $xml->Payment;
        $docRef = (string) ($p->ClearingDoc ?? $p->PaymentRef ?? '');
        $amount = nn($p->Amount ?? '');
        $payDate = nd($p->PaymentDate ?? '');
        $db->prepare(
            'INSERT INTO payment
                (payment_ref, clearing_doc, idso, zinn, invoice_no, payment_date, amount, currency, clearing_status, correlation_id)
             VALUES (:pr, :cd, :idso, :zinn, :inv, :pd, :amt, :cur, :cs, :corr)'
        )->execute([
            ':pr' => nn($p->PaymentRef ?? ''), ':cd' => nn($p->ClearingDoc ?? ''), ':idso' => $idso ?: null,
            ':zinn' => nn($zinn), ':inv' => nn($invoiceNo), ':pd' => $payDate, ':amt' => $amount,
            ':cur' => nn($p->Currency ?? ''), ':cs' => (string) ($p->ClearingStatus ?? 'CLEARED'), ':corr' => nn($corrId),
        ]);
        // shëno faturën si e paguar
        if ($invoiceNo !== '') {
            $db->prepare('UPDATE finance_document SET fi_status = "CLEARED" WHERE invoice_no = :inv')
               ->execute([':inv' => $invoiceNo]);
        }
        $sets = ['fi_status = :fs', 'paid_date = :pd', 'paid_amount = :amt'];
        $params = [':fs' => 'CLEARED', ':pd' => $payDate, ':amt' => $amount];
    } else {
        $sets = ['last_event = :le']; $params = [':le' => $eventType];
    }

    // Përditëso salesorder
    if ($idso > 0)          { $where = 'idso = :idso';           $params[':idso'] = $idso; }
    elseif ($corrId !== '') { $where = 'correlation_id = :corr'; $params[':corr'] = $corrId; }
    else                    { $where = 's4_order_id = :s4w';     $params[':s4w']  = $s4Order; }
    $db->prepare('UPDATE salesorder SET ' . implode(', ', $sets) . ' WHERE ' . $where)->execute($params);

    // Histori
    $db->prepare(
        'INSERT INTO order_status_history (idso, s4_order_id, zinn, event_type, status, doc_ref, message, correlation_id)
         VALUES (:idso, :s4, :zinn, :ev, :status, :ref, :msg, :corr)'
    )->execute([
        ':idso' => $idso ?: null, ':s4' => $s4Order, ':zinn' => $zinn, ':ev' => $eventType,
        ':status' => ($eventType === 'PAYMENT_CLEARED' ? 'PAID' : 'FI_POSTED'),
        ':ref' => $docRef, ':msg' => (string) ($xml->Header->Message ?? ''), ':corr' => $corrId,
    ]);

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    error_log('receive_finance failed: ' . $e->getMessage());
    json_response(['message' => 'Processing error'], 500);
}

json_response(['message' => 'Finance event applied', 'eventType' => $eventType,
               'docRef' => $docRef, 'invoiceNo' => $invoiceNo, 'correlationId' => $corrId]);
