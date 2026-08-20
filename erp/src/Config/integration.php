<?php
/**
 * integration.php — Konfigurimi Salt ERP <-> SAP Cloud Integration (ZRC_IR OrderFlow).
 * Të gjitha vlerat vijnë nga mjedisi (.env). Asnjë sekret i hardkoduar.
 */
declare(strict_types=1);

return [
    // Endpoint i iFlow-t INBOUND në CI që pranon porosinë kanonike (ORDERS) — SINKRON (Phase 1)
    'cpi_orders_url' => (string) env('CPI_ORDERS_URL', ''),

    // Endpoint ASINKRON (Phase 2): CI e vë porosinë në radhë JMS dhe kthen 202
    'cpi_orders_async_url' => (string) env('CPI_ORDERS_ASYNC_URL', ''),

    // Endpoint OData ATP/Stock (Phase 2): GET /salt/stock?material=..&plant=..
    'cpi_stock_url'  => (string) env('CPI_STOCK_URL', ''),

    // Token/kredencial për CI (Bearer)
    'cpi_token'      => (string) env('CPI_TOKEN', ''),

    // Retry i dispatcher-it të outbox-it (Phase 2)
    'outbox_max_attempts' => (int) env('OUTBOX_MAX_ATTEMPTS', 6),
    'outbox_retry_base'   => (int) env('OUTBOX_RETRY_BASE', 30),   // sekonda, backoff eksponencial
    'stock_cache_ttl'     => (int) env('STOCK_CACHE_TTL', 300),    // sekonda

    // Token që CI duhet të dërgojë kur POST-on evente te receive_event.php
    'inbound_token'  => (string) env('SALT_INBOUND_TOKEN', ''),

    // Identifikuesit e sistemit (partner profile / logical system)
    'sender_id'      => (string) env('SALT_SENDER_ID', 'ALBSALE_SALT'),
    'receiver_id'    => (string) env('SALT_RECEIVER_ID', 'ZS4CLNT100'),

    // Timeout për thirrjet HTTP drejt CI (sekonda)
    'http_timeout'   => (int) env('CPI_HTTP_TIMEOUT', 15),
];
