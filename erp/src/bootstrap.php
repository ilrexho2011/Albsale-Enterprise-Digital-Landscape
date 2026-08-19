<?php
/**
 * bootstrap.php — Pika e vetme e nisjes për ÇDO entrypoint (web/api).
 * Ngarkon .env, konfiguron gabimet, seksionin e sigurt dhe helper-at.
 *
 * Përdorim:  require_once __DIR__ . '/../../src/bootstrap.php';
 */
declare(strict_types=1);

define('ERP_BASE', dirname(__DIR__));           // rrënja e projektit erp/
require_once ERP_BASE . '/src/Config/env.php';
load_env(ERP_BASE . '/.env');

// --- Zona kohore ---
date_default_timezone_set((string) env('APP_TIMEZONE', 'Europe/Tirane'));

// --- Trajtimi i gabimeve sipas mjedisit ---
$debug = (bool) env('APP_DEBUG', false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');   // asnjë gabim i shfaqur në prod
ini_set('log_errors', '1');

// --- Seksion i sigurt (vetëm kur duhet) ---
if (!function_exists('secure_session_start')) {
    function secure_session_start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => (($_SERVER['HTTPS'] ?? '') !== ''),
        ]);
        session_start();
    }
}

// --- Helper për përgjigje JSON të njësuar ---
if (!function_exists('json_response')) {
    function json_response(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// --- CORS i kontrolluar (jo wildcard në prod) ---
if (!function_exists('apply_cors')) {
    function apply_cors(string $methods = 'GET, POST, OPTIONS'): void
    {
        $allowed = array_map('trim', explode(',', (string) env('ALLOWED_ORIGINS', '')));
        $origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin !== '' && in_array($origin, $allowed, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
        } elseif (env('APP_ENV') === 'dev' && $origin !== '') {
            header('Access-Control-Allow-Origin: ' . $origin); // lehtësim vetëm në dev
            header('Vary: Origin');
        }
        header('Access-Control-Allow-Methods: ' . $methods);
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Inbound-Token');
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
