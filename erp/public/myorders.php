<?php
/**
 * myorders.php — Kundenportal: "Meine Bestellungen"
 * Pamja self-service ku klienti (ZINN) sheh porositë e veta dhe statusin O2C,
 * dhe mund t'i dërgojë te SAP (përmes SAP CI) me një klik.
 */
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_once ERP_BASE . '/src/Config/Database.php';

$db = (new Database())->connect();

// Lista e klientëve për selektorin ZINN
$users = $db->query('SELECT ZINN, name, surname FROM user ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$selectedZinn = trim((string) ($_GET['zinn'] ?? ''));

$orders = [];
$history = [];
if ($selectedZinn !== '') {
    $st = $db->prepare('SELECT * FROM salesorder WHERE ZINN = :z ORDER BY created DESC');
    $st->execute([':z' => $selectedZinn]);
    $orders = $st->fetchAll(PDO::FETCH_ASSOC);

    $h = $db->prepare('SELECT * FROM order_status_history WHERE zinn = :z ORDER BY created DESC LIMIT 30');
    $h->execute([':z' => $selectedZinn]);
    $history = $h->fetchAll(PDO::FETCH_ASSOC);
}

function badge(string $status): string
{
    $c = ['NEW' => '#1a73e8', 'SENT' => '#8430ce', 'CONFIRMED' => '#137333',
          'DELIVERED' => '#0b8043', 'INVOICED' => '#137333', 'REJECTED' => '#c5221f'];
    $bg = $c[$status] ?? '#5f6368';
    $s  = htmlspecialchars($status, ENT_QUOTES);
    return "<span style='background:$bg;color:#fff;padding:2px 8px;border-radius:10px;font-size:12px'>$s</span>";
}

function h(?string $v): string { return htmlspecialchars((string) $v, ENT_QUOTES); }
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Meine Bestellungen — Albsale Vlora</title>
  <link rel="stylesheet" href="assets/css/stili.css" />
  <style>
    body{font-family:system-ui,Arial;margin:0;background:#f6f8fa;color:#202124}
    header{background:#0a3d62;color:#fff;padding:14px 24px;display:flex;align-items:center;gap:14px}
    .wrap{padding:22px;max-width:1100px;margin:0 auto}
    table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #d0d7de;border-radius:8px;overflow:hidden}
    th,td{padding:9px 11px;border-bottom:1px solid #eaecef;text-align:left;font-size:14px}
    th{background:#eef2f6}
    select,button{padding:7px 10px;font-size:14px;border-radius:6px;border:1px solid #b7c0c9}
    button{background:#0a3d62;color:#fff;border:none;cursor:pointer}
    .tl{background:#fff;border:1px solid #d0d7de;border-radius:8px;padding:12px 16px;margin-top:18px}
    .tl div{padding:4px 0;border-bottom:1px dashed #eee;font-size:13px}
  </style>
</head>
<body>
<header>
  <img src="assets/img/logo.png" height="34" onerror="this.style.display='none'"/>
  <div><b>Albsale Vlora — Kundenportal</b><div style="font-size:12px;opacity:.8">Order-to-Cash · verbunden mit SAP über SAP CI</div></div>
</header>
<div class="wrap">
  <form method="get" style="margin-bottom:18px">
    <label>Kunde (ZINN):</label>
    <select name="zinn" onchange="this.form.submit()">
      <option value="">— wählen —</option>
      <?php foreach ($users as $u): ?>
        <option value="<?= h($u['ZINN']) ?>" <?= $u['ZINN'] === $selectedZinn ? 'selected' : '' ?>>
          <?= h($u['name'] . ' ' . $u['surname'] . ' (' . $u['ZINN'] . ')') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <?php if ($selectedZinn !== ''): ?>
    <h3>Meine Bestellungen</h3>
    <table>
      <thead><tr>
        <th>IDSO</th><th>SAP Order</th><th>Artikel</th><th>Menge</th><th>Wert</th>
        <th>Status</th><th>Lieferung</th><th>Rechnung</th><th>Aktion</th>
      </tr></thead>
      <tbody>
      <?php if (!$orders): ?>
        <tr><td colspan="9" style="text-align:center;color:#888">Keine Bestellungen</td></tr>
      <?php else: foreach ($orders as $o): ?>
        <tr>
          <td><?= (int) $o['idso'] ?></td>
          <td><?= h($o['s4_order_id'] ?? '') ?></td>
          <td><?= h($o['title']) ?></td>
          <td><?= (int) $o['quantity'] ?> <?= h($o['unit']) ?></td>
          <td><?= (int) $o['value'] ?> <?= h($o['currency']) ?></td>
          <td><?= badge($o['order_status'] ?? 'NEW') ?></td>
          <td><?= h($o['delivery_no'] ?? '') ?></td>
          <td><?= h($o['invoice_no'] ?? '') ?></td>
          <td>
            <?php if (($o['order_status'] ?? 'NEW') === 'NEW'): ?>
              <button onclick="sendToSap(<?= (int) $o['idso'] ?>, this)">An SAP senden</button>
            <?php else: ?>—<?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>

    <?php if ($history): ?>
      <div class="tl">
        <b>Verlauf (O2C-Dokumente von SAP)</b>
        <?php foreach ($history as $hh): ?>
          <div><?= h($hh['created']) ?> · <b><?= h($hh['event_type']) ?></b>
            → <?= badge($hh['status']) ?>
            <?= $hh['doc_ref'] ? '· Ref: ' . h($hh['doc_ref']) : '' ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<script>
function sendToSap(idso, btn) {
  btn.disabled = true; btn.textContent = 'Senden...';
  fetch('api/integration/send_order.php', {
    method: 'POST', headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({idso: idso})
  }).then(r => r.json()).then(d => {
    alert(d.message + (d.correlationId ? '\nCorrelation: ' + d.correlationId : ''));
    location.reload();
  }).catch(e => { alert('Fehler: ' + e); btn.disabled = false; btn.textContent = 'An SAP senden'; });
}
</script>
</body>
</html>
