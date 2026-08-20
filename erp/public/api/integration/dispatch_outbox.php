<?php
/**
 * dispatch_outbox.php — Phase 2 (worker): ridërgon porositë PENDING/FAILED që kanë
 * arritur next_attempt_at, me backoff eksponencial dhe dead-letter pas max_attempts.
 *
 * Përdorim:
 *   - CLI/cron:  php public/api/integration/dispatch_outbox.php
 *   - HTTP:      GET /erp/public/api/integration/dispatch_outbox.php?token=DISPATCH_TOKEN
 * Mbrohet me DISPATCH_TOKEN (env) kur thirret mbi HTTP.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';
require_once ERP_BASE . '/src/Lib/outbox.php';

$cfg   = require ERP_BASE . '/src/Config/integration.php';
$isCli = (PHP_SAPI === 'cli');

// Mbrojtje kur thirret mbi HTTP (jo CLI)
if (!$isCli) {
    $expected = (string) env('DISPATCH_TOKEN', '');
    $given    = (string) ($_GET['token'] ?? '');
    if ($expected === '' || !hash_equals($expected, $given)) {
        json_response(['message' => 'Forbidden'], 403);
    }
}

$db = (new Database())->connect();

// Merr deri në 50 rreshta të gatshëm (PENDING ose FAILED me next_attempt_at të kaluar)
$batch = $db->query(
    "SELECT * FROM integration_outbox
      WHERE status IN ('PENDING','FAILED')
        AND (next_attempt_at IS NULL OR next_attempt_at <= NOW())
      ORDER BY next_attempt_at ASC
      LIMIT 50"
)->fetchAll(PDO::FETCH_ASSOC);

$summary = ['processed' => 0, 'sent' => 0, 'failed' => 0, 'dead' => 0];
foreach ($batch as $row) {
    // Lock optimist: shmang përpunimin e dyfishtë nga dy worker-a paralelë
    $lock = $db->prepare(
        "UPDATE integration_outbox SET locked_at = NOW()
          WHERE id = :id AND (locked_at IS NULL OR locked_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE))"
    );
    $lock->execute([':id' => $row['id']]);
    if ($lock->rowCount() === 0) {
        continue; // e mori një worker tjetër
    }

    $res = outbox_try_send($db, $cfg, $row);
    $summary['processed']++;
    $summary[strtolower($res['status']) === 'sent' ? 'sent'
           : ($res['status'] === 'DEAD' ? 'dead' : 'failed')]++;
}

$out = ['message' => 'Dispatch run complete'] + $summary;
if ($isCli) {
    fwrite(STDOUT, json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL);
} else {
    json_response($out);
}
