<?php
/**
 * integration.php — Konfigurimi Salt ERP <-> SAP Cloud Integration (ZRC_IR OrderFlow).
 * Të gjitha vlerat vijnë nga mjedisi (.env). Asnjë sekret i hardkoduar.
 */
declare(strict_types=1);

return [
    // Endpoint i iFlow-t INBOUND në CI që pranon porosinë kanonike (ORDERS)
    'cpi_orders_url' => (string) env('CPI_ORDERS_URL', ''),

    // Token/kredencial për CI (Bearer)
    'cpi_token'      => (string) env('CPI_TOKEN', ''),

    // Token që CI duhet të dërgojë kur POST-on evente te receive_event.php
    'inbound_token'  => (string) env('SALT_INBOUND_TOKEN', ''),

    // Identifikuesit e sistemit (partner profile / logical system)
    'sender_id'      => (string) env('SALT_SENDER_ID', 'ALBSALE_SALT'),
    'receiver_id'    => (string) env('SALT_RECEIVER_ID', 'ZS4CLNT100'),

    // Timeout për thirrjet HTTP drejt CI (sekonda)
    'http_timeout'   => (int) env('CPI_HTTP_TIMEOUT', 15),
];
