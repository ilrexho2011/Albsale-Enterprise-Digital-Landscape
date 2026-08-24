<?php
/**
 * Integration configuration — Salt ERP <-> SAP Cloud Integration (ZRC_IR OrderFlow).
 * Vlerat lexohen nga environment variables; fallback vetëm për dev lokal.
 * MOS vendos sekrete reale këtu — përdor variabla mjedisi në prod.
 */
return [
    // Endpoint i iFlow-t INBOUND në CI që pranon porosinë kanonike (ORDERS)
    'cpi_orders_url'  => getenv('CPI_ORDERS_URL')
        ?: 'https://cpi-tenant.it-cpi.eu1.hana.ondemand.com/http/salt/orders',

    // Token/kredencial për CI (Basic/OAuth kalohet nga reverse proxy ose header)
    'cpi_token'       => getenv('CPI_TOKEN') ?: 'dev-token',

    // Token që CI duhet të dërgojë kur POST-on evente te receive_event.php
    'inbound_token'   => getenv('SALT_INBOUND_TOKEN') ?: 'dev-inbound-token',

    // Identifikuesit e sistemit (partner profile / logical system)
    'sender_id'       => getenv('SALT_SENDER_ID') ?: 'ALBSALE_SALT',
    'receiver_id'     => getenv('SALT_RECEIVER_ID') ?: 'ZS4CLNT100',

    // Timeout për thirrjet HTTP drejt CI (sekonda)
    'http_timeout'    => (int)(getenv('CPI_HTTP_TIMEOUT') ?: 15),
];
