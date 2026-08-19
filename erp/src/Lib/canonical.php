<?php
/**
 * canonical.php — Ndërton XML-in kanonik O2C që shkëmbehet me SAP CI.
 * XML-i kanonik është "formati i mesit"; CI e harton më tej në EDIFACT/IDoc.
 * Struktura pasqyron ORDERS (D.96A) por mbetet e lexueshme si XML.
 */
declare(strict_types=1);

/**
 * Ndërton dokumentin kanonik ORDERS nga një rresht salesorder + të dhënat e klientit.
 *
 * @param array       $order    rreshti salesorder
 * @param array       $cust     të dhënat e klientit (name, surname, email)
 * @param string      $corrId   correlation id
 * @param string|null $senderId identifikuesi i dërguesit (default nga .env)
 */
function build_orders_canonical(array $order, array $cust, string $corrId, ?string $senderId = null): string
{
    $senderId ??= (string) (function_exists('env') ? env('SALT_SENDER_ID', 'ALBSALE_SALT') : 'ALBSALE_SALT');

    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->formatOutput = true;

    $root = $doc->createElement('OrderCreate');
    $root->setAttribute('xmlns', 'urn:albsale:o2c:canonical:1.0');
    $doc->appendChild($root);

    // Header (hartohet -> EDIFACT UNH/BGM/DTM/NAD)
    $h = $doc->createElement('Header');
    $h->appendChild($doc->createElement('DocumentType', 'ORDERS'));      // BGM+220
    $h->appendChild($doc->createElement('CorrelationId', $corrId));
    $h->appendChild($doc->createElement('SenderId', $senderId));         // NAD+SU
    $h->appendChild($doc->createElement('OrderDate', date('Y-m-d')));    // DTM+137
    $root->appendChild($h);

    // Buyer / Customer (NAD+BY) — çelësi ZINN
    $buyer = $doc->createElement('Buyer');
    $buyer->appendChild($doc->createElement('CustomerRef', (string) $order['ZINN']));
    $buyer->appendChild($doc->createElement('Name',
        trim(($cust['name'] ?? '') . ' ' . ($cust['surname'] ?? ''))));
    $buyer->appendChild($doc->createElement('Email', $cust['email'] ?? ''));
    $root->appendChild($buyer);

    // Line item (LIN/QTY/PRI) — një artikull për porosi në modelin aktual
    $lines = $doc->createElement('Lines');
    $line = $doc->createElement('Line');
    $line->appendChild($doc->createElement('LineNo', '0010'));
    $line->appendChild($doc->createElement('ProductRef', (string) $order['saltcode']));
    $line->appendChild($doc->createElement('Description', (string) $order['title']));
    $line->appendChild($doc->createElement('Quantity', (string) $order['quantity']));
    $line->appendChild($doc->createElement('Unit', (string) $order['unit']));
    $line->appendChild($doc->createElement('LineValue', (string) $order['value']));
    $line->appendChild($doc->createElement('Currency', (string) $order['currency']));
    $lines->appendChild($line);
    $root->appendChild($lines);

    // Summary (MOA+86 total)
    $sum = $doc->createElement('Summary');
    $sum->appendChild($doc->createElement('TotalValue', (string) $order['value']));
    $sum->appendChild($doc->createElement('Currency', (string) $order['currency']));
    $root->appendChild($sum);

    return $doc->saveXML();
}

/** Gjeneron një correlation id stabël për porosinë. */
function make_correlation_id($idso, $zinn): string
{
    return 'SALT-' . $zinn . '-' . str_pad((string) $idso, 6, '0', STR_PAD_LEFT)
        . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
}
