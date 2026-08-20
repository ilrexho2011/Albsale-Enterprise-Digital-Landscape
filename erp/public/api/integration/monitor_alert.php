<?php
/**
 * monitor_alert.php — Phase 5 (AEM monitoring): pranon alertet e integrimit nga
 * IF_Salt_Monitoring_Collector dhe i ruan te integration_alert. I mbrojtur me X-Inbound-Token.
 *
 * POST /erp/public/api/integration/monitor_alert.php  (application/json)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';

header('Content-Type: application/json; charset=utf-8');
$cfg = require ERP_BASE . '/src/Config/integration.php';

$token = $_SERVER['HTTP_X_INBOUND_TOKEN'] ?? '';
if ($cfg['inbound_token'] === '' || !hash_equals($cfg['inbound_token'], $token)) {
    json_response(['message' => 'Invalid inbound token'], 401);
}

$d = json_decode((string) file_get_contents('php://input'));
if (!$d) {
    json_response(['message' => 'Malformed JSON'], 400);
}
$sev = strtoupper((string) ($d->severity ?? 'WARNING'));
if (!in_array($sev, ['INFO', 'WARNING', 'CRITICAL'], true)) {
    $sev = 'WARNING';
}

$db = (new Database())->connect();
$db->prepare(
    'INSERT INTO integration_alert (severity, scenario, correlation_id, message_id, error_phrase, event_ts)
     VALUES (:sev, :sc, :corr, :mid, :err, :ts)'
)->execute([
    ':sev' => $sev,
    ':sc'  => substr((string) ($d->scenario ?? ''), 0, 60),
    ':corr'=> substr((string) ($d->correlationId ?? ''), 0, 60),
    ':mid' => substr((string) ($d->messageId ?? ''), 0, 64),
    ':err' => substr((string) ($d->errorPhrase ?? ''), 0, 255),
    ':ts'  => substr((string) ($d->ts ?? ''), 0, 40),
]);

json_response(['message' => 'Alert recorded', 'severity' => $sev]);
