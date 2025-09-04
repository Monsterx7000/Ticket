<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
check_csrf();
if (is_logged_in()) { header('Location: dashboard.php'); exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT id, name, email, role, password FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if ($u && password_verify($pass, $u['password'])) {
        $_SESSION['user'] = ['id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role']];
        header('Location: dashboard.php'); exit;
    } else { $msg = t('invalid_credentials'); }
}
include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card p-4">
      <h3 class="mb-3"><?= t('login') ?></h3>
      <?php if ($msg): ?><div class="alert alert-danger"><?= esc($msg) ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
        <div class="mb-3">
          <label class="form-label"><?= t('email') ?></label>
          <input class="form-control" type="email" required name="email">
        </div>
        <div class="mb-3">
          <label class="form-label"><?= t('password') ?></label>
          <input class="form-control" type="password" required name="password">
        </div>
        <button class="btn btn-primary w-100"><?= t('login') ?></button>
      </form>
      <hr>
      <a href="register.php" class="btn btn-outline-secondary w-100"><?= t('register') ?></a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
