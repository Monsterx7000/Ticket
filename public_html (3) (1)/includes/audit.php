<?php
// includes/audit.php — reusable login audit helper
// Usage: require_once __DIR__.'/audit.php'; audit_login($pdo, $email, 'success'|'failure', $user_id_or_null);

if (!function_exists('audit_login')) {
  function audit_login($pdo, $email, $status, $user_id = null) {
    try {
      if (!$pdo) { throw new Exception('PDO not provided'); }
      // Normalize
      $email = (string)$email;
      if ($email === '') { $email = '-'; }
      $status = ($status === 'success') ? 'success' : 'failure';

      // IP resolver (considers proxies/CDN)
      $ip = 'unknown';
      $candidates = ['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','REMOTE_ADDR'];
      foreach ($candidates as $h) {
        if (!empty($_SERVER[$h])) {
          $ip = $_SERVER[$h];
          if (strpos($ip, ',') !== false) { $ip = trim(explode(',', $ip)[0]); }
          break;
        }
      }

      // Insert
      $stmt = $pdo->prepare("INSERT INTO login_audit (user_id, email, ip_address, status) VALUES (?, ?, ?, ?);");
      $stmt->execute([ $user_id, $email, $ip, $status ]);
      return true;
    } catch (Throwable $e) {
      // Try to log to filesystem (uploads/logs)
      try {
        $dir = __DIR__ . '/../uploads/logs';
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        $line = date('Y-m-d H:i:s') . " | audit_login error: " . $e->getMessage() . "\n";
        @file_put_contents($dir . '/audit_errors.log', $line, FILE_APPEND);
      } catch (Throwable $e2) {}
      return false;
    }
  }
}
