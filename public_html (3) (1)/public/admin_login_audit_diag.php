<?php
@ini_set('display_errors', 1);
@ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$report = [];

$base = realpath(__DIR__ . '/..');

$report[] = ['PHP Version', PHP_VERSION];
$report[] = ['SAPI', php_sapi_name()];
$report[] = ['Script', __FILE__];

// Check files
$files = ['db.php','includes/functions.php'];
foreach ($files as $f) {
    $p = $base . '/' . $f;
    $report[] = ["Exists: {$f}", file_exists($p) ? 'YES' : 'NO'];
}

// Include and capture warnings
$inc_warnings = [];
set_error_handler(function($errno,$errstr,$errfile,$errline) use (&$inc_warnings){
  $inc_warnings[] = "{$errstr} @ {$errfile}:{$errline}";
  return true;
});
$ok_db = @include $base . '/db.php';
$ok_fn = @include $base . '/includes/functions.php';
restore_error_handler();

$report[] = ['Include db.php', $ok_db ? 'OK' : 'FAILED'];
$report[] = ['Include functions.php', $ok_fn ? 'OK' : 'FAILED'];
$report[] = ['Include warnings', $inc_warnings ? implode("\n", $inc_warnings) : '—'];

// Functions existence
$needFns = ['require_login','require_role','check_csrf'];
foreach ($needFns as $fn) {
  $report[] = ["Function exists: {$fn}", function_exists($fn) ? 'YES' : 'NO'];
}

// DB and table
try {
  if (!isset($pdo)) throw new Exception('$pdo missing from db.php');
  $pdo->query('SELECT 1');
  $report[] = ['DB connection', 'OK'];
  $pdo->query('SELECT 1 FROM login_audit LIMIT 1');
  $report[] = ['Table login_audit', 'OK'];
} catch (Throwable $e) {
  $report[] = ['DB/Table error', $e->getMessage()];
}

// Output HTML
?><!doctype html><html lang="ar" dir="rtl"><head>
<meta charset="utf-8"><title>تشخيص سجلات الدخول</title>
<style>
body{font-family:Tahoma,Arial,sans-serif;margin:20px}
table{border-collapse:collapse;width:100%}
td,th{border:1px solid #ccc;padding:8px}
th{background:#f7f7f7}
pre{background:#f2f2f2;padding:10px}
</style>
</head><body>
<h2>تشخيص — سجلات الدخول (Login Audit)</h2>
<table>
<tr><th>البند</th><th>القيمة/النتيجة</th></tr>
<?php foreach ($report as $row): ?>
<tr><td><?= h($row[0]) ?></td><td><pre><?= h($row[1]) ?></pre></td></tr>
<?php endforeach; ?>
</table>

<p>إذا ظهر أن الجدول غير موجود، نفّذ SQL التالي مرة واحدة:</p>
<pre>CREATE TABLE IF NOT EXISTS login_audit (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  email VARCHAR(190) NOT NULL,
  ip_address VARCHAR(64) NOT NULL,
  status ENUM('success','failure') NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (email),
  INDEX (status),
  INDEX (created_at)
);</pre>
</body></html>
