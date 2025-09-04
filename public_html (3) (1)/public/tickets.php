<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/categories.php';
require_login();
$role = user()['role'];
$uid = user()['id'];

$status = $_GET['status'] ?? '';
$q = trim($_GET['q'] ?? '');

$where = [];
$params = [];
if ($status && in_array($status, ['open','pending','closed'])) {
    $where[] = "t.status=?";
    $params[] = $status;
}
if ($q !== '') {
    $where[] = "(t.subject LIKE ? OR t.message LIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%";
}
if (!in_array($role, ['admin','agent'])) {
    $where[] = "t.user_id=?";
    $params[] = $uid;
}
$wsql = $where ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("SELECT t.*, u.name owner, a.name assigned_name
  FROM tickets t
  JOIN users u ON u.id=t.user_id
  LEFT JOIN users a ON a.id=t.assigned_to
  $wsql
  ORDER BY t.updated_at DESC
  LIMIT 200");
$stmt->execute($params);
$tickets = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
$lang = $_SESSION['lang'] ?? DEFAULT_LANG;
?>
<div class="card p-3">
  <div class="d-flex gap-2 align-items-end">
    <form class="row g-2" method="get">
      <div class="col-auto">
        <label class="form-label"><?= t('status') ?></label>
        <select name="status" class="form-select">
          <option value=""><?= t('status') ?></option>
          <option value="open" <?= $status==='open'?'selected':'' ?>><?= t('open') ?></option>
          <option value="pending" <?= $status==='pending'?'selected':'' ?>><?= t('pending') ?></option>
          <option value="closed" <?= $status==='closed'?'selected':'' ?>><?= t('closed') ?></option>
        </select>
      </div>
      <div class="col-auto">
        <label class="form-label"><?= t('search') ?></label>
        <input class="form-control" name="q" value="<?= esc($q) ?>">
      </div>
      <div class="col-auto pt-4">
        <button class="btn btn-primary"><?= t('filter') ?></button>
      </div>
    </form>
    <div class="ms-auto">
      <a class="btn btn成功" href="ticket_new.php"><?= t('new_ticket') ?></a>
    </div>
  </div>
</div>

<div class="card p-3 mt-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr>
        <th>#</th>
        <th><?= t('subject') ?></th>
        <th><?= t('status') ?></th>
        <th><?= t('priority') ?></th>
        <th><?= t('category') ?></th>
        <th><?= t('owner') ?></th>
        <th><?= t('assigned') ?></th>
        <th><?= t('last_update') ?></th>
        <th><?= t('actions') ?></th>
      </tr></thead>
      <tbody>
      <?php if (!$tickets): ?>
        <tr><td colspan="9" class="text-center text-muted py-5"><?= t('no_tickets') ?></td></tr>
      <?php else: foreach ($tickets as $t): ?>
        <tr>
          <td><?= (int)$t['id'] ?></td>
          <td><?= esc($t['subject']) ?></td>
          <td><span class="badge bg-<?= $t['status']==='open'?'success':($t['status']==='pending'?'warning':'secondary') ?>"><?= t($t['status']) ?></span></td>
          <td><?= esc(priority_label($t['priority'])) ?></td>
          <td><?= esc(cat_label($t['category'])) ?></td>
          <td><?= esc($t['owner']) ?></td>
          <td><?= esc($t['assigned_name'] ?? '-') ?></td>
          <td><?= esc($t['updated_at']) ?></td>
          <td><a class="btn btn-sm btn-primary" href="ticket_view.php?id=<?= (int)$t['id'] ?>"><?= t('view') ?></a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
