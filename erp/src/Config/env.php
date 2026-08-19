<?php
/**
 * env.php — Ngarkues minimal i skedarit `.env` (pa varësi të jashtme).
 * Lexon çift KEY=VALUE dhe i vë në dispozicion me env('KEY').
 * Nuk mbishkruan variablat e vërteta të mjedisit (getenv ka përparësi).
 */
declare(strict_types=1);

if (!function_exists('load_env')) {
    function load_env(string $path): void
    {
        if (!is_readable($path)) {
            return; // në prod vlerat mund të vijnë nga variabla reale mjedisi
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            // hiq komentin inline dhe hapësirat/thonjëzat
            $value = trim($value);
            if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                $q = $value[0];
                $end = strpos($value, $q, 1);
                $value = $end !== false ? substr($value, 1, $end - 1) : substr($value, 1);
            } else {
                // pa thonjëza: prit komentin pas ' #'
                $hash = strpos($value, ' #');
                if ($hash !== false) {
                    $value = substr($value, 0, $hash);
                }
                $value = trim($value);
            }
            if (getenv($key) === false) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

if (!function_exists('env')) {
    /** Merr një variabël mjedisi me fallback dhe kast bazik. */
    function env(string $key, mixed $default = null): mixed
    {
        $val = getenv($key);
        if ($val === false) {
            return $default;
        }
        return match (strtolower($val)) {
            'true'  => true,
            'false' => false,
            'null'  => null,
            default => $val,
        };
    }
}
