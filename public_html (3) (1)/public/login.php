<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$lang = $_SESSION['lang'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'ar');

// If already logged in, redirect
if (!empty($_SESSION['user'])) {
  header('Location: index.php'); exit;
}

$err = '';
      /*AUDIT_FAILURE_START*/
      audit_login($pdo, $email ?: '-', 'failure', null);
      /*AUDIT_FAILURE_END*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = (string)($_POST['password'] ?? '');

  try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
      // Success login → session + audit
      $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
      ];
      audit_login($pdo, $email, 'success', (int)$user['id']);
      header('Location: index.php'); exit;
    } else {
      // Failure → audit and show error
      audit_login($pdo, $email, 'failure', null);
      $err = ($lang==='ar') ? 'البريد الإلكتروني أو كلمة المرور غير صحيحة' : 'Incorrect email or password';
    }
  } catch (Throwable $e) {
    // On unexpected errors, still audit failure best-effort
    audit_login($pdo, $email ?: '-', 'failure', null);
    $err = ($lang==='ar') ? 'حدث خطأ أثناء الدخول' : 'An error occurred while logging in';
  }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-4">
    <div class="card p-3">
      <h4 class="mb-3"><?= ($lang==='ar') ? 'تسجيل الدخول' : 'Sign in' ?></h4>
      <?php if ($err): ?><div class="alert alert-danger"><?= esc($err) ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
        <div class="mb-3">
          <label class="form-label"><?= ($lang==='ar') ? 'البريد الإلكتروني' : 'Email' ?></label>
          <input class="form-control" type="email" name="email" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label"><?= ($lang==='ar') ? 'كلمة المرور' : 'Password' ?></label>
          <input class="form-control" type="password" name="password" required>
        </div>
        <button class="btn btn-primary w-100"><?= ($lang==='ar') ? 'دخول' : 'Sign in' ?></button>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
