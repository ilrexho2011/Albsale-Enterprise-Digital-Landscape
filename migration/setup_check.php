<?php
/**
 * tools/setup_check.php — Diagnostikë e shpejtë e mjedisit lokal.
 * Kontrollon: versionin PHP, ekstensionet, lidhjen me DB, tabelat & numrin e rreshtave.
 *
 * Përdorim:  php tools/setup_check.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Vetëm CLI.\n");
}

require_once __DIR__ . '/../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';

$ok = true;
function line(string $s): void { echo $s . "\n"; }
function pass(string $s): void { echo "  [OK]   " . $s . "\n"; }
function fail(string $s): void { global $ok; $ok = false; echo "  [GABIM]" . $s . "\n"; }

line("== Albsale Vlora — Kontroll mjedisi lokal ==");
line("");

// 1) PHP
line("1) PHP");
if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
    pass("Versioni PHP " . PHP_VERSION . " (>= 8.0)");
} else {
    fail("PHP " . PHP_VERSION . " është shumë i vjetër; duhet >= 8.0");
}
foreach (['pdo_mysql', 'json', 'mbstring', 'curl'] as $ext) {
    extension_loaded($ext) ? pass("Ekstensioni {$ext} i ngarkuar")
                           : fail("Mungon ekstensioni {$ext}");
}
line("");

// 2) .env
line("2) Konfigurimi (.env)");
is_readable(ERP_BASE . '/.env') ? pass(".env u lexua")
                                : fail(".env mungon te " . ERP_BASE);
foreach (['DB_NAME', 'DB_USER', 'ALLOWED_ORIGINS'] as $k) {
    env($k) !== null ? pass("{$k} = " . (string) env($k))
                     : fail("{$k} nuk është vendosur te .env");
}
line("");

// 3) DB + tabelat
line("3) Baza e të dhënave");
try {
    $db = (new Database())->connect();
    pass("Lidhja PDO me '" . (string) env('DB_NAME') . "' u realizua");

    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    if (!$tables) {
        fail("Asnjë tabelë — ngarko sql/01..07 si root.");
    } else {
        pass(count($tables) . " tabela të pranishme");
        $expect = ['salt', 'user', 'salesorder'];
        foreach ($expect as $t) {
            in_array($t, $tables, true) ? pass("tabela '{$t}' ekziston")
                                        : fail("mungon tabela bazë '{$t}'");
        }
        line("  — Numri i rreshtave për tabelë:");
        foreach ($tables as $t) {
            try {
                $n = (int) $db->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
                echo "      {$t}: {$n}\n";
            } catch (Throwable $e) {
                echo "      {$t}: (s'u lexua) {$e->getMessage()}\n";
            }
        }
        // katalogu duhet të ketë të dhëna
        if (in_array('salt', $tables, true)) {
            $n = (int) $db->query("SELECT COUNT(*) FROM `salt`")->fetchColumn();
            $n > 0 ? pass("katalogu 'salt' ka {$n} artikuj")
                   : fail("katalogu 'salt' është bosh (rifut 01_schema.sql)");
        }
    }
} catch (Throwable $e) {
    fail("Lidhja/kontrolli i DB dështoi: " . $e->getMessage());
}
line("");

line($ok ? "== PËRFUNDIM: gjithçka OK. ==" : "== PËRFUNDIM: ka gabime — shiko [GABIM] më lart. ==");
exit($ok ? 0 : 1);
