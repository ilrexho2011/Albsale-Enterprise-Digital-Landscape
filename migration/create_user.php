<?php
/**
 * tools/create_user.php — Krijon një përdorues portal me fjalëkalim të HASH-uar.
 * Ekzekutohet VETËM nga CLI (jo web). Fjalëkalimi hash-ohet me password_hash().
 *
 * Përdorim:
 *   php tools/create_user.php <username> <password> <ZINN> [name] [surname] [email] [tel]
 *
 * Shembull:
 *   php tools/create_user.php arben Test1234! 100001 Arben Hoxha arben@albsale.al 0692000000
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Ky skript lejohet vetëm nga command line (CLI).\n");
}

require_once __DIR__ . '/../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Models/User.php';
require_once ERP_BASE . '/src/Security/auth.php';

$args = $argv;
array_shift($args); // hiq emrin e skriptit

if (count($args) < 3) {
    fwrite(STDERR, "Përdorim: php tools/create_user.php <username> <password> <ZINN> [name] [surname] [email] [tel]\n");
    exit(2);
}

[$username, $password, $zinn] = $args;
$name    = $args[3] ?? 'Test';
$surname = $args[4] ?? 'Klient';
$email   = $args[5] ?? ($username . '@albsale.local');
$tel     = $args[6] ?? null;

if (strlen($password) < 8) {
    fwrite(STDERR, "Gabim: fjalëkalimi duhet të ketë të paktën 8 karaktere.\n");
    exit(2);
}

try {
    $db = (new Database())->connect();
    $u  = new User($db);

    if ($u->usernameExists($username)) {
        fwrite(STDERR, "Gabim: username '{$username}' ekziston tashmë.\n");
        exit(1);
    }

    $u->name     = $name;
    $u->surname  = $surname;
    $u->username = $username;
    $u->password = hash_password($password);  // HASH — kurrë tekst i thjeshtë
    $u->ZINN     = $zinn;
    $u->email    = $email;
    $u->tel      = $tel;

    if ($u->signup()) {
        echo "OK — u krijua përdoruesi #{$u->id}: username='{$username}', ZINN='{$zinn}'.\n";
        echo "Hyr te portali me këto kredenciale (fjalëkalimi ruhet i hash-uar).\n";
        exit(0);
    }

    fwrite(STDERR, "Dështoi krijimi i përdoruesit (kontrollo skemën/DB).\n");
    exit(1);

} catch (Throwable $e) {
    fwrite(STDERR, "Përjashtim: " . $e->getMessage() . "\n");
    exit(1);
}
