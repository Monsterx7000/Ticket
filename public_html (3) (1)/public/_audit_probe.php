<?php
// _audit_probe.php — temporary diagnostic to test login_audit insert
// Upload to public/ then open in browser once to verify DB insert works.

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/audit.php';

$email = 'probe+' . date('Ymd_His') . '@example.com';

$ok1 = audit_login($pdo, $email, 'failure', null);
$ok2 = audit_login($pdo, $email, 'success', 1);

if ($ok1 && $ok2) {
  echo "OK: wrote two rows for $email<br>";
} else {
  echo "ERROR: audit_login returned false<br>";
}

// try to fetch last 2 rows
try {
  $stmt = $pdo->query("SELECT id, email, status, ip_address, created_at FROM login_audit ORDER BY id DESC LIMIT 5");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo "<pre>" . htmlspecialchars(print_r($rows, true), ENT_QUOTES, 'UTF-8') . "</pre>";
} catch (Throwable $e) {
  echo "SELECT error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

// show file log if any
$log = __DIR__ . '/../uploads/logs/audit_errors.log';
if (file_exists($log)) {
  echo "<h3>audit_errors.log</h3><pre>" . htmlspecialchars(file_get_contents($log), ENT_QUOTES, 'UTF-8') . "</pre>";
} else {
  echo "<p>No audit_errors.log present.</p>";
}
