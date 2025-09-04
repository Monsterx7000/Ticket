<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
// Simple stats
$uid = user()['id'];
$role = user()['role'];
if (in_array($role, ['admin','agent'])) {
    $total = $pdo->query("SELECT COUNT(*) c FROM tickets")->fetch()['c'];
    $open  = $pdo->query("SELECT COUNT(*) c FROM tickets WHERE status='open'")->fetch()['c'];
    $pend  = $pdo->query("SELECT COUNT(*) c FROM tickets WHERE status='pending'")->fetch()['c'];
    $closed= $pdo->query("SELECT COUNT(*) c FROM tickets WHERE status='closed'")->fetch()['c'];
} else {
    $stmt = $pdo->prepare("SELECT
        SUM(status='open') open,
        SUM(status='pending') pending,
        SUM(status='closed') closed,
        COUNT(*) total
        FROM tickets WHERE user_id=?");
    $stmt->execute([$uid]);
    $row = $stmt->fetch();
    $open=$row['open']; $pend=$row['pending']; $closed=$row['closed']; $total=$row['total'];
}
include __DIR__ . '/../includes/header.php';
?>
<div class="row g-3">
  <div class="col-md-3"><div class="card p-3"><div class="text-muted"><?= t('tickets') ?></div><div class="h3 mb-0"><?= (int)$total ?></div></div></div>
  <div class="col-md-3"><div class="card p-3"><div class="text-muted"><?= t('open') ?></div><div class="h3 mb-0"><?= (int)$open ?></div></div></div>
  <div class="col-md-3"><div class="card p-3"><div class="text-muted"><?= t('pending') ?></div><div class="h3 mb-0"><?= (int)$pend ?></div></div></div>
  <div class="col-md-3"><div class="card p-3"><div class="text-muted"><?= t('closed') ?></div><div class="h3 mb-0"><?= (int)$closed ?></div></div></div>
</div>
<div class="card p-3 mt-3">
  <h5><?= t('welcome') ?>, <?= esc(user()['name']) ?>!</h5>
  <a class="btn btn-primary mt-2" href="ticket_new.php"><?= t('new_ticket') ?></a>
  <a class="btn btn-outline-secondary mt-2" href="tickets.php"><?= t('tickets') ?></a>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
