<?php
// public/audit_selftest.php — Quick test tool (admin-only)
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_login(); require_role(['admin']);

$lang = $_SESSION['lang'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'ar');
$msgs = [];

// Try inserting sample rows
$ok1 = audit_login($pdo, 'selftest_success@example.com', 'success', 1);
$ok2 = audit_login($pdo, 'selftest_failure@example.com', 'failure', null);
if ($ok1 && $ok2) {
  $msgs[] = ($lang==='ar') ? 'تمت إضافة سجلين تجريبيين بنجاح' : 'Inserted two sample rows successfully';
} else {
  $msgs[] = ($lang==='ar') ? 'تعذر إضافة بعض السجلات — تحقق من ملف السجلات' : 'Failed to insert some rows — check logs';
}

// Read last 10
$rows = [];
try {
  $stmt = $pdo->query("SELECT id,user_id,email,ip_address,status,created_at FROM login_audit ORDER BY id DESC LIMIT 10");
  $rows = $stmt->fetchAll();
} catch (Throwable $e) {
  $msgs[] = 'DB error: ' . $e->getMessage();
}

?>
<!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8"><title>Audit Self-Test</title>
<style>table{border-collapse:collapse}td,th{border:1px solid #ccc;padding:6px}</style>
</head><body>
<h3><?= ($lang==='ar') ? 'اختبار سجلات الدخول' : 'Login Audit Self-Test' ?></h3>
<?php foreach ($msgs as $m): ?><div><?= htmlspecialchars($m) ?></div><?php endforeach; ?>

<h4><?= ($lang==='ar') ? 'آخر 10 سجلات' : 'Last 10 records' ?></h4>
<table>
<tr><th>#</th><th>Email</th><th>User ID</th><th>IP</th><th>Status</th><th>Time</th></tr>
<?php foreach ($rows as $r): ?>
<tr>
  <td><?= (int)$r['id'] ?></td>
  <td><?= htmlspecialchars($r['email']) ?></td>
  <td><?= htmlspecialchars($r['user_id']) ?></td>
  <td><?= htmlspecialchars($r['ip_address']) ?></td>
  <td><?= htmlspecialchars($r['status']) ?></td>
  <td><?= htmlspecialchars($r['created_at']) ?></td>
</tr>
<?php endforeach; ?>
</table>

<p style="margin-top:16px">
  <?= ($lang==='ar') ? 'إذا لم تُضاف السجلات، راجع ملف' : 'If rows did not insert, check' ?>
  <code>uploads/logs/audit_errors.log</code>
</p>
</body></html>
