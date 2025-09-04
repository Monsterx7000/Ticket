<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
check_csrf();
if (is_logged_in()) { header('Location: dashboard.php'); exit; }
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if ($pass !== $confirm) { $msg = 'Passwords do not match'; }
    else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role,created_at) VALUES (?,?,?,?,NOW())");
            $stmt->execute([$name,$email,$hash,'user']);
            // إعدادات البريد (SMTP أو mail())
$toUser = $email;
$toAdmin = "Abdullahx5@hotmail.com"; // عدّل بريد المدير
$subjectUser = "مرحبا $name - تم إنشاء حسابك";
$subjectAdmin = "تسجيل مستخدم جديد في النظام";

$messageUser = "
مرحباً $name،
تم إنشاء حسابك بنجاح في نظام الدعم الفني.

يمكنك تسجيل الدخول باستخدام بريدك: $email
";

$messageAdmin = "
تنبيه: مستخدم جديد قام بالتسجيل
الاسم: $name
البريد: $email
";

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/plain; charset=UTF-8\r\n";
$headers .= "From: Support Desk <no-reply@Abdullah.com>\r\n";

// إرسال للمستخدم
@mail($toUser, $subjectUser, $messageUser, $headers);

// إرسال للمدير
@mail($toAdmin, $subjectAdmin, $messageAdmin, $headers);

            $msg = t('register_success');
        } catch (Exception $e) {
            $msg = $e->getMessage();
        }
    }
}
include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-7 col-lg-6">
    <div class="card p-4">
      <h3 class="mb-3"><?= t('register') ?></h3>
      <?php if ($msg): ?><div class="alert alert-info"><?= esc($msg) ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
        <div class="mb-3">
          <label class="form-label"><?= t('name') ?></label>
          <input class="form-control" name="name" required>
        </div>
        <div class="mb-3">
          <label class="form-label"><?= t('email') ?></label>
          <input class="form-control" type="email" name="email" required>
        </div>
        <div class="mb-3">
          <label class="form-label"><?= t('password') ?></label>
          <input class="form-control" type="password" name="password" required>
        </div>
        <div class="mb-3">
          <label class="form-label"><?= t('confirm_password') ?></label>
          <input class="form-control" type="password" name="confirm" required>
        </div>
        <button class="btn btn-primary w-100"><?= t('register') ?></button>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
