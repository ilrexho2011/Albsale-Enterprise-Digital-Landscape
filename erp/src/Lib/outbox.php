<?php
/**
 * outbox.php — Logjika e përbashkët e "transactional outbox" për dërgim të besueshëm.
 * Përdoret nga enqueue_order.php (async) dhe dispatch_outbox.php (worker me retry).
 * Pattern: at-least-once + backoff eksponencial + dead-letter (status=DEAD).
 */
declare(strict_types=1);

require_once __DIR__ . '/canonical.php';
require_once __DIR__ . '/http.php';

/** Ndërton payload-in kanonik për një porosi; kthen [order, cust, corrId, xml] ose null. */
function outbox_build_payload(PDO $db, int $idso, string $senderId): ?array
{
    $st = $db->prepare('SELECT * FROM salesorder WHERE idso = :idso LIMIT 1');
    $st->execute([':idso' => $idso]);
    $order = $st->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        return null;
    }
    $cst = $db->prepare('SELECT name, surname, email FROM user WHERE ZINN = :z LIMIT 1');
    $cst->execute([':z' => $order['ZINN']]);
    $cust = $cst->fetch(PDO::FETCH_ASSOC) ?: [];

    $corrId = $order['correlation_id'] ?: make_correlation_id($idso, $order['ZINN']);
    $xml    = build_orders_canonical($order, $cust, $corrId, $senderId);
    return [$order, $cust, $corrId, $xml];
}

/** Fut/përditëson rreshtin në outbox si PENDING, gati për dispatcher. */
function outbox_upsert(PDO $db, int $idso, string $zinn, string $corrId, string $xml, int $maxAttempts): void
{
    $db->prepare(
        'INSERT INTO integration_outbox
            (idso, zinn, doc_type, correlation_id, payload, status, max_attempts, next_attempt_at)
         VALUES (:idso, :zinn, "ORDERS", :corr, :payload, "PENDING", :maxa, NOW())
         ON DUPLICATE KEY UPDATE payload = VALUES(payload), status = "PENDING",
            max_attempts = VALUES(max_attempts), next_attempt_at = NOW()'
    )->execute([':idso' => $idso, ':zinn' => $zinn, ':corr' => $corrId, ':payload' => $xml, ':maxa' => $maxAttempts]);

    $db->prepare('UPDATE salesorder SET order_status = "QUEUED", correlation_id = :c WHERE idso = :idso')
       ->execute([':c' => $corrId, ':idso' => $idso]);
}

/**
 * Provon të dërgojë një rresht outbox te CI (endpoint asinkron).
 * Sukses (2xx): status=SENT. Dështim: rrit attempts, cakton next_attempt_at me backoff,
 * ose status=DEAD nëse u shterua max_attempts.
 * @return array{ok:bool, code:int, error:string, status:string}
 */
function outbox_try_send(PDO $db, array $cfg, array $row): array
{
    $corrId = $row['correlation_id'];
    $url    = $cfg['cpi_orders_async_url'] ?: $cfg['cpi_orders_url'];
    $headers = [
        'Content-Type: application/xml',
        'X-Correlation-Id: ' . $corrId,
        'Authorization: Bearer ' . $cfg['cpi_token'],
    ];
    [$code, , $err] = http_send('POST', $url, $row['payload'], $headers, (int) $cfg['http_timeout']);

    if ($code >= 200 && $code < 300) {
        $db->prepare('UPDATE integration_outbox SET status = "SENT", locked_at = NULL WHERE correlation_id = :c')
           ->execute([':c' => $corrId]);
        $db->prepare('UPDATE salesorder SET order_status = "SENT", last_event = "ORDERS" WHERE correlation_id = :c')
           ->execute([':c' => $corrId]);
        return ['ok' => true, 'code' => $code, 'error' => '', 'status' => 'SENT'];
    }

    // Dështim -> backoff ose dead-letter
    $attempts = (int) $row['attempts'] + 1;
    $maxA     = (int) $row['max_attempts'];
    $reason   = $err ?: ('HTTP ' . $code);

    if ($attempts >= $maxA) {
        $db->prepare('UPDATE integration_outbox
                        SET status = "DEAD", attempts = :a, last_error = :e, locked_at = NULL
                      WHERE correlation_id = :c')
           ->execute([':a' => $attempts, ':e' => substr($reason, 0, 250), ':c' => $corrId]);
        return ['ok' => false, 'code' => $code, 'error' => $reason, 'status' => 'DEAD'];
    }

    // backoff eksponencial: base * 2^(attempts-1) sekonda
    $delay = (int) $cfg['outbox_retry_base'] * (2 ** ($attempts - 1));
    $db->prepare('UPDATE integration_outbox
                    SET status = "FAILED", attempts = :a, last_error = :e, locked_at = NULL,
                        next_attempt_at = DATE_ADD(NOW(), INTERVAL :d SECOND)
                  WHERE correlation_id = :c')
       ->execute([':a' => $attempts, ':e' => substr($reason, 0, 250), ':d' => $delay, ':c' => $corrId]);
    return ['ok' => false, 'code' => $code, 'error' => $reason, 'status' => 'FAILED'];
}
