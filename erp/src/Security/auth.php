<?php
/**
 * auth.php — Helper sigurie për autentikim.
 *  - Fjalëkalimet ruhen me password_hash() (bcrypt/argon), JURË base64.
 *  - Mbrojtje e faqeve me require_login().
 */
declare(strict_types=1);

if (!function_exists('hash_password')) {
    function hash_password(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }
}

if (!function_exists('verify_password')) {
    function verify_password(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        secure_session_start();
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('require_login')) {
    /** Mbron faqet e portalit; ridrejton te login nëse s'ka seksion. */
    function require_login(string $loginUrl = 'login.php'): void
    {
        if (current_user() === null) {
            header('Location: ' . $loginUrl);
            exit;
        }
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        secure_session_start();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }
}

if (!function_exists('csrf_check')) {
    function csrf_check(?string $token): bool
    {
        secure_session_start();
        return is_string($token) && !empty($_SESSION['csrf'])
            && hash_equals($_SESSION['csrf'], $token);
    }
}
