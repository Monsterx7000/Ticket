<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_login(); check_csrf();

$id = (int)($_GET['id'] ?? 0);
$role = user()['role'];

$stmt = $pdo->prepare("SELECT t.*, u.name owner_name FROM tickets t JOIN users u ON u.id=t.user_id WHERE t.id=?");
$stmt->execute([$id]);
$ticket = $stmt->fetch();
if (!$ticket) { http_response_code(404); die('Not Found'); }
if (!in_array($role, ['admin','agent']) && $ticket['user_id'] != user()['id']) {
    http_response_code(403); die('Forbidden');
}

$msg=''; $err='';
if (!empty($_SESSION['flash_success'])) { $msg = $_SESSION['flash_success']; unset($_SESSION['flash_success']); }
if (!empty($_SESSION['flash_error'])) { $err = $_SESSION['flash_error']; unset($_SESSION['flash_error']); }

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (isset($_POST['reply'])) {
        $content = trim($_POST['content'] ?? '');
        $file = upload_file('attachment');
        if ($content !== '') {
            $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticket_id,user_id,content,attachment,created_at) VALUES (?,?,?,?,NOW())");
            $stmt->execute([$id, user()['id'], $content, $file]);
            $pdo->prepare("UPDATE tickets SET updated_at=NOW() WHERE id=?")->execute([$id]);
            $_SESSION['flash_success'] = ($_SESSION['lang'] ?? 'ar')==='ar' ? 'تم إرسال الرد' : 'Reply posted';
            header("Location: ticket_view.php?id=".$id); exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
$lang = $_SESSION['lang'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'ar');
$choose_file = ($lang==='ar') ? 'اختر ملف' : 'Choose file';
$no_file     = ($lang==='ar') ? 'لم يتم اختيار ملف' : 'No file chosen';
?>
<div class="row">
  <div class="col-lg-8">
    <div class="card p-3 mb-3">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <h4>#<?= (int)$ticket['id'] ?> - <?= esc($ticket['subject']) ?></h4>
          <div class="text-muted small"><?= t('owner') ?>: <?= esc($ticket['owner_name']) ?></div>
        </div>
        <div>
          <span class="badge bg-<?= $ticket['status']==='open'?'success':($ticket['status']=='pending'?'warning':'secondary') ?>"><?= t($ticket['status']) ?></span>
        </div>
      </div>
      <?php if ($err): ?><div class="alert alert-danger mt-3"><?= esc($err) ?></div><?php endif; ?>
      <?php if ($msg): ?><div class="alert alert-success mt-3"><?= esc($msg) ?></div><?php endif; ?>
      <p class="mt-3"><?= nl2br(esc($ticket['message'])) ?></p>
    </div>

    <div class="card p-3">
      <h5 class="mb-3"><?= t('reply') ?></h5>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
        <div class="mb-3">
          <textarea class="form-control" rows="4" name="content" required></textarea>
        </div>

        <!-- Custom file input (i18n) for reply attachment -->
        <div class="mb-3">
          <label class="form-label"><?= t('attachment') ?> (<?= ($lang==='ar') ? 'اختياري' : 'Optional' ?>)</label>
          <input class="form-control d-none" type="file" id="replyAttachmentInput" name="attachment">
          <label for="replyAttachmentInput" class="btn btn-outline-primary"><?= esc($choose_file) ?></label>
          <span id="replyAttachmentFileName" class="ms-2 text-muted"><?= esc($no_file) ?></span>
        </div>

        <button class="btn btn-primary" name="reply" value="1"><?= t('submit') ?></button>
      </form>
    </div>
  </div>
</div>
<script>
(function(){
  var inp = document.getElementById('replyAttachmentInput');
  var label = document.querySelector("label[for='replyAttachmentInput']");
  var nameSpan = document.getElementById('replyAttachmentFileName');
  if (!inp || !label || !nameSpan) return;
  var NO_FILE = <?= json_encode($no_file, JSON_UNESCAPED_UNICODE) ?>;
  inp.addEventListener('change', function() {
    nameSpan.textContent = (this.files && this.files.length) ? this.files[0].name : NO_FILE;
  });
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
