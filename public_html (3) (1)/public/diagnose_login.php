<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php'; // for esc()

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT id, email, name, role, password, LENGTH(password) AS len FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if (!$u) {
        $result = ['status'=>'error','msg'=>'Email not found in users table.','email'=>$email];
    } else {
        $hash = $u['password'];
        $prefix = substr($hash, 0, 4);
        $verify = password_verify($pass, $hash);
        $rehash_needed = password_needs_rehash($hash, PASSWORD_DEFAULT);
        $result = [
            'status'=> $verify ? 'ok' : 'mismatch',
            'msg'=> $verify ? 'Password OK' : 'Password does NOT match the stored hash',
            'user'=> ['id'=>$u['id'], 'email'=>$u['email'], 'role'=>$u['role']],
            'hash_info'=> ['prefix'=>$prefix, 'length'=>$u['len'], 'rehash_needed'=>$rehash_needed],
            'hash_sample'=> substr($hash,0,12) . '...'
        ];
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Diagnose Login</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head><body class="bg-light">
<div class="container py-4">
  <div class="card p-3">
    <h3>Diagnose Login</h3>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input class="form-control" type="email" name="email" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input class="form-control" type="text" name="password" required>
      </div>
      <button class="btn btn-primary">Test</button>
    </form>
  </div>
  <?php if ($result): ?>
  <div class="card p-3 mt-3">
    <h5>Result</h5>
    <pre class="mb-0"><?php echo esc(json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)); ?></pre>
  </div>
  <?php endif; ?>
  <div class="alert alert-warning mt-3">
    Delete this file after use for security.
  </div>
</div></body></html>
