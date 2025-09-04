<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/categories.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_login(); check_csrf();

$lang = $_SESSION['lang'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'ar');

$msg=''; $err='';

$cats = categories_enabled();
$cat_slugs = array_map(fn($c) => $c['slug'], $cats);

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $subject  = trim($_POST['subject'] ?? '');
    $message  = trim($_POST['message'] ?? '');
    $priority = $_POST['priority'] ?? 'normal';
    $category = $_POST['category'] ?? '';

    $file = upload_file('attachment');

    if ($subject === '' || $message === '') {
        $err = ($lang==='ar') ? 'الرجاء إدخال الموضوع والمحتوى' : 'Please enter subject and message';
    } elseif (!in_array($category, $cat_slugs, true)) {
        $err = ($lang==='ar') ? 'يرجى اختيار فئة صالحة من القائمة' : 'Please choose a valid category from the list';
    } else {
        // Normalize priority
        $allowedP = ['low','normal','high','urgent'];
        if (!in_array($priority, $allowedP, true)) $priority = 'normal';

        // Insert ticket (store category as slug)
        $stmt = $pdo->prepare("INSERT INTO tickets (user_id, subject, message, priority, category, attachment, status, created_at, updated_at) VALUES (?,?,?,?,?,?, 'open', NOW(), NOW())");
        $stmt->execute([user()['id'], $subject, $message, $priority, $category, $file]);
        $tid = (int)$pdo->lastInsertId();

        // Optional system note
        $note = ($lang==='ar') ? '[نظام] تم إنشاء التذكرة' : '[System] Ticket created';
        $pdo->prepare("INSERT INTO ticket_replies (ticket_id,user_id,content,attachment,created_at) VALUES (?,?,?,?,NOW())")
            ->execute([$tid, user()['id'], $note, null]);

        $_SESSION['flash_success'] = ($lang==='ar') ? 'تم إنشاء التذكرة بنجاح' : 'Ticket created successfully';
        header("Location: ticket_view.php?id=".$tid); exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row">
  <div class="col-lg-8">
    <div class="card p-3">
      <h4 class="mb-3"><?= t('new_ticket') ?></h4>

      <?php if ($err): ?><div class="alert alert-danger"><?= esc($err) ?></div><?php endif; ?>
      <?php if (count($cats) === 0): ?>
        <div class="alert alert-warning">
          <?= ($lang==='ar')
              ? 'لا توجد فئات مفعلة حاليًا. يرجى التواصل مع المدير لإضافة/تفعيل الفئات.'
              : 'No enabled categories available. Please contact the admin to add/enable categories.'; ?>
        </div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">

        <div class="mb-3">
          <label class="form-label"><?= t('subject') ?></label>
          <input class="form-control" name="subject" maxlength="200" required>
        </div>

        <div class="mb-3">
          <label class="form-label"><?= t('message') ?></label>
          <textarea class="form-control" name="message" rows="6" required></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label"><?= t('priority') ?></label>
          <select class="form-select" name="priority">
            <option value="low"><?= ($lang==='ar') ? 'منخفضة' : 'Low' ?></option>
            <option value="normal" selected><?= ($lang==='ar') ? 'عادية' : 'Normal' ?></option>
            <option value="high"><?= ($lang==='ar') ? 'مرتفعة' : 'High' ?></option>
            <option value="urgent"><?= ($lang==='ar') ? 'عاجلة' : 'Urgent' ?></option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label"><?= t('category') ?></label>
          <select class="form-select" name="category" required <?= count($cats)===0?'disabled':'' ?>>
            <?php foreach ($cats as $c): ?>
              <option value="<?= esc($c['slug']) ?>"><?= esc(($lang==='ar' && !empty($c['name_ar'])) ? $c['name_ar'] : $c['name_en']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (count($cats)===0): ?>
            <div class="form-text text-danger">
              <?= ($lang==='ar') ? 'لا يمكن إنشاء تذكرة بدون فئة مفعلة' : 'Cannot create a ticket without an enabled category' ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="mb-3">
          <label class="form-label"><?= t('attachment') ?> (<?= ($lang==='ar') ? 'اختياري' : 'Optional' ?>)</label>
          <input class="form-control" type="file" name="attachment">
        </div>

        <button class="btn btn-primary" <?= count($cats)===0?'disabled':'' ?>><?= t('submit') ?></button>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
