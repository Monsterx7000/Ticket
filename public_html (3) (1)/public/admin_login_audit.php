<?php
// admin_login_audit.php — v4 (fix WHERE ambiguity by qualifying columns with la.)
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_login(); require_role(['admin']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') { check_csrf(); }

$lang = $_SESSION['lang'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'ar');
function tr($ar, $en){ global $lang; return $lang==='ar' ? $ar : $en; }

$errors = [];
$table_ok = true;

// Check table existence
try { $pdo->query("SELECT 1 FROM login_audit LIMIT 1"); }
catch (Throwable $e) { $table_ok = false; $errors[] = tr('جدول login_audit غير موجود أو لا يمكن الوصول إليه.', 'Table login_audit is missing or inaccessible.') . ' ' . $e->getMessage(); }

// Filters
$status = $_GET['status'] ?? 'all';
$range  = $_GET['range']  ?? '7d';
$emailQ = trim($_GET['q'] ?? '');

$where = []; $params = [];
if ($status==='success') $where[] = "la.status='success'";
elseif ($status==='failure') $where[] = "la.status='failure'";
if ($range!=='all') {
  $days = $range==='1d' ? 1 : ($range==='30d' ? 30 : 7);
  $where[] = "la.created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
}
if ($emailQ!=='') { $where[] = "la.email LIKE ?"; $params[] = "%{$emailQ}%"; }
$sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Export CSV (qualify columns)
if ($table_ok && isset($_GET['export']) && $_GET['export']==='csv') {
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="login_audit.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['id','user_id','email','ip_address','status','la.created_at']);
  $stmt = $pdo->prepare("SELECT la.id,la.user_id,la.email,la.ip_address,la.status,la.created_at FROM login_audit la {$sqlWhere} ORDER BY la.id DESC LIMIT 5000");
  $stmt->execute($params);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { fputcsv($out, $row); }
  fclose($out); exit;
}

// Fetch data (qualify ORDER and WHERE)
$rows = [];
if ($table_ok) {
  try {
    $stmt = $pdo->prepare("SELECT la.*, u.name as uname FROM login_audit la LEFT JOIN users u ON u.id=la.user_id {$sqlWhere} ORDER BY la.id DESC LIMIT 500");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
  } catch (Throwable $e) {
    $errors[] = tr('فشل جلب البيانات.', 'Failed to fetch data.') . ' ' . $e->getMessage();
  }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row g-3">
  <div class="col-lg-10">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><?= tr('سجلات الدخول', 'Login Audit') ?></h4>
        <div class="d-flex gap-2">
          <a class="btn btn-outline-secondary" href="admin.php"><?= t('admin_panel') ?></a>
        </div>
      </div>

      <?php foreach ($errors as $e): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>

      <form class="row g-2 mb-3" method="get">
        <div class="col-sm-3">
          <label class="form-label"><?= tr('الحالة', 'Status') ?></label>
          <select class="form-select" name="status">
            <option value="all" <?= $status==='all'?'selected':'' ?>><?= tr('الكل', 'All') ?></option>
            <option value="success" <?= $status==='success'?'selected':'' ?>><?= tr('نجاح', 'Success') ?></option>
            <option value="failure" <?= $status==='failure'?'selected':'' ?>><?= tr('فشل', 'Failure') ?></option>
          </select>
        </div>
        <div class="col-sm-3">
          <label class="form-label"><?= tr('الفترة', 'Range') ?></label>
          <select class="form-select" name="range">
            <option value="1d" <?= $range==='1d'?'selected':'' ?>><?= tr('اليوم', 'Today') ?></option>
            <option value="7d" <?= $range==='7d'?'selected':'' ?>><?= tr('آخر 7 أيام', 'Last 7 days') ?></option>
            <option value="30d" <?= $range==='30d'?'selected':'' ?>><?= tr('آخر 30 يومًا', 'Last 30 days') ?></option>
            <option value="all" <?= $range==='all'?'selected':'' ?>><?= tr('الكل', 'All') ?></option>
          </select>
        </div>
        <div class="col-sm-4">
          <label class="form-label"><?= tr('بحث بالبريد', 'Search by email') ?></label>
          <input class="form-control" name="q" value="<?= htmlspecialchars($emailQ) ?>" placeholder="user@example.com">
        </div>
        <div class="col-sm-2 d-flex align-items-end gap-2">
          <button class="btn btn-primary w-100"><?= tr('تصفية', 'Filter') ?></button>
        </div>
      </form>

      <div class="d-flex justify-content-end mb-2">
        <a class="btn btn-outline-success <?= !$table_ok?'disabled':'' ?>" href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['export'=>'csv']))) ?>"><?= tr('تصدير CSV', 'Export CSV') ?></a>
      </div>

      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th><?= tr('الوقت', 'Time') ?></th>
              <th><?= tr('البريد', 'Email') ?></th>
              <th><?= tr('المستخدم', 'User') ?></th>
              <th>IP</th>
              <th><?= tr('الحالة', 'Status') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if ($table_ok && !$rows): ?>
              <tr><td colspan="6" class="text-center text-muted py-4"><?= tr('لا توجد سجلات', 'No records') ?></td></tr>
            <?php endif; ?>
            <?php if ($table_ok): foreach ($rows as $r): ?>
              <tr>
                <td><?= (int)$r['id'] ?></td>
                <td><?= htmlspecialchars($r['la.created_at']) ?></td>
                <td><?= htmlspecialchars($r['email']) ?></td>
                <td><?= htmlspecialchars($r['uname'] ?: '-') ?></td>
                <td><?= htmlspecialchars($r['ip_address']) ?></td>
                <td>
                  <?php if ($r['status']==='success'): ?>
                    <span class="badge bg-success"><?= tr('نجاح', 'Success') ?></span>
                  <?php else: ?>
                    <span class="badge bg-danger"><?= tr('فشل', 'Failure') ?></span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
