<?php
// Unified Admin Panel (v3): merges ALL features we've added previously.
// - Users table with role update (admin/agent), password reset (admin-only)
// - Branding (company name, logo, favicon, OG image) with AR/EN custom file inputs
// - Email Notifications toggles (per-type) with bilingual labels and help text
// - "Manage Categories" button
// Notes: Requires includes/settings.php and includes/notifications.php and functions.php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/notifications.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_login(); require_role(['admin','agent']); check_csrf();

$lang = $_SESSION['lang'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'ar');
$choose_file = ($lang==='ar') ? 'اختر ملف' : 'Choose file';
$no_file     = ($lang==='ar') ? 'لم يتم اختيار ملف' : 'No file chosen';

$msg = ''; $err = '';

// ===== Handle POST actions =====
if ($_SERVER['REQUEST_METHOD']==='POST') {
    // Update role (admin/agent allowed as before)
    if (isset($_POST['uid'], $_POST['role']) && isset($_POST['update_role'])) {
        require_role(['admin','agent']);
        $uid = (int)$_POST['uid'];
        $role = $_POST['role'];
        if (in_array($role, ['user','agent','admin'])) {
            $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role,$uid]);
            $msg = t('updated_success') ?: (($lang==='ar') ? 'تم التحديث' : 'Updated');
        } else { $err = 'Invalid role'; }
    }

    // Reset password (admin only)
    if (isset($_POST['uid'], $_POST['new_pass'], $_POST['confirm_pass']) && isset($_POST['reset_password'])) {
        require_role(['admin']);
        $uid = (int)$_POST['uid'];
        $new = (string)$_POST['new_pass']; $confirm = (string)$_POST['confirm_pass'];
        if ($new !== $confirm) {
            $err = t('passwords_not_match') ?: (($lang==='ar') ? 'كلمتا المرور غير متطابقتين' : 'Passwords do not match');
        } elseif (strlen($new) < 8 || strlen($new) > 64) {
            $err = t('password_length_rule') ?: (($lang==='ar') ? 'يجب أن تكون كلمة المرور بين 8 و 64 حرفًا' : 'Password must be 8-64 characters.');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash,$uid]);
            $msg = t('password_updated_success') ?: (($lang==='ar') ? 'تم تحديث كلمة المرور' : 'Password updated successfully');
        }
    }

    // Branding (admin only)
    if (isset($_POST['branding_save'])) {
        require_role(['admin']);
        $company = trim($_POST['company_name'] ?? '');
        if ($company !== '') setting_set('company_name', $company);

        $destDir = __DIR__ . '/../uploads/branding';
        if (!is_dir($destDir)) { @mkdir($destDir, 0755, true); }

        // Helper to save a file with validation
        $save_upload = function($field, $allowed_exts, $max_bytes, $setting_key) use ($destDir, &$err, &$msg, $lang) {
            if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return;
            $f = $_FILES[$field];
            if ($f['error'] !== UPLOAD_ERR_OK) { $err = 'Upload error code: '.$f['error']; return; }
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_exts, true)) { $err = 'Invalid file type for '.$field; return; }
            if ($f['size'] > $max_bytes) { $err = 'File too large for '.$field; return; }
            $name = bin2hex(random_bytes(8)).'.'.$ext;
            if (move_uploaded_file($f['tmp_name'], $destDir.'/'.$name)) {
                // Remove old file if exists
                $current = setting_get($setting_key, '');
                if ($current) { @unlink($destDir.'/'.$current); }
                setting_set($setting_key, $name);
                $msg = t('updated_success') ?: (($lang==='ar') ? 'تم حفظ الإعدادات' : 'Settings saved');
            } else { $err = 'Failed to save '.$field; }
        };

        // Save files
        $save_upload('logo', ['png','jpg','jpeg','gif'], 2*1024*1024, 'logo_file');
        $save_upload('favicon', ['ico','png'], 256*1024, 'favicon_file');
        $save_upload('og_image', ['png','jpg','jpeg'], 2*1024*1024, 'og_image_file');

        // Remove actions
        $destDir = __DIR__ . '/../uploads/branding';
        if (isset($_POST['remove_logo'])) {
            $current = setting_get('logo_file',''); if ($current) { @unlink($destDir.'/'.$current); }
            setting_set('logo_file',''); $msg = ($lang==='ar') ? 'تم حذف الشعار' : 'Logo removed';
        }
        if (isset($_POST['remove_favicon'])) {
            $current = setting_get('favicon_file',''); if ($current) { @unlink($destDir.'/'.$current); }
            setting_set('favicon_file',''); $msg = ($lang==='ar') ? 'تم حذف الأيقونة' : 'Favicon removed';
        }
        if (isset($_POST['remove_og_image'])) {
            $current = setting_get('og_image_file',''); if ($current) { @unlink($destDir.'/'.$current); }
            setting_set('og_image_file',''); $msg = ($lang==='ar') ? 'تم حذف صورة المشاركة' : 'OG image removed';
        }
    }

    // Notifications toggles (admin only)
    if (isset($_POST['save_notifications'])) {
        require_role(['admin']);
        $defs = notify_defaults();
        foreach ($defs as $k=>$default) {
            $val = isset($_POST[$k]) ? '1' : '0';
            setting_set($k, $val);
        }
        $msg = ($lang==='ar') ? 'تم حفظ إعدادات الإشعارات' : 'Notification settings saved';
    }
}

// Load data
$users = $pdo->query("SELECT id,name,email,role,created_at FROM users ORDER BY id DESC LIMIT 200")->fetchAll();
$company = setting_get('company_name', APP_NAME);
$logo = setting_get('logo_file','');
$favicon = setting_get('favicon_file','');
$og_image = setting_get('og_image_file','');
$notify = notify_get_all();

// Labels & help for notifications (AR/EN)
$LABELS = [
  'notify_assign_agent' => [
    'ar' => 'إشعار عند الإسناد (إلى الوكيل)',
    'en' => 'Notify on assignment (agent)',
    'help_ar' => 'يرسل بريدًا للوكيل المُسند عند إسناد/تغيير إسناد التذكرة.',
    'help_en' => 'Emails the assigned agent when a ticket is assigned or reassigned.'
  ],
  'notify_status_owner' => [
    'ar' => 'إشعار صاحب التذكرة عند تغيير الحالة',
    'en' => 'Notify ticket owner on status change',
    'help_ar' => 'يرسل بريدًا لصاحب التذكرة عند تغيير حالتها (مفتوحة/قيد المعالجة/مغلقة).',
    'help_en' => 'Emails the ticket owner when status changes (Open/Pending/Closed).'
  ],
  'notify_status_agent' => [
    'ar' => 'إشعار الوكيل المُسند عند تغيير الحالة',
    'en' => 'Notify assigned agent on status change',
    'help_ar' => 'يرسل بريدًا للوكيل المُسند عندما تتغير حالة التذكرة.',
    'help_en' => 'Emails the assigned agent when ticket status changes.'
  ],
  'notify_signup_user' => [
    'ar' => 'إشعار ترحيبي للمستخدم عند التسجيل',
    'en' => 'Welcome email to user on signup',
    'help_ar' => 'يرسل رسالة ترحيبية تلقائيًا للمستخدم الجديد.',
    'help_en' => 'Sends an automatic welcome email to newly registered users.'
  ],
  'notify_signup_admin' => [
    'ar' => 'إشعار للمدير عند تسجيل مستخدم جديد',
    'en' => 'Notify admin on new signup',
    'help_ar' => 'يرسل تنبيهًا بالبريد إلى المدراء عند إنشاء حساب جديد.',
    'help_en' => 'Emails admins when a new account is created.'
  ],
  'notify_new_ticket_admin' => [
    'ar' => 'إشعار للمدير عند إنشاء تذكرة جديدة',
    'en' => 'Notify admin on new ticket',
    'help_ar' => 'يرسل إشعارًا بالبريد لكل المدراء عند إنشاء تذكرة جديدة.',
    'help_en' => 'Emails all admins when a new ticket is created.'
  ],
];

include __DIR__ . '/../includes/header.php';
?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><?= t('admin_panel') ?></h4>
        <?php if (user()['role']==='admin'): ?>
          <a class="btn btn-outline-secondary" href="admin_categories.php">
            <?= ($lang==='ar') ? 'إدارة التصنيفات' : 'Manage Categories' ?>
          </a>
        <?php endif; ?>
      </div>

      <?php if ($msg): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-danger"><?= esc($err) ?></div><?php endif; ?>

      <!-- Users list -->
      <div class="table-responsive mb-4">
        <table class="table align-middle">
          <thead><tr><th>#</th><th><?= t('name') ?></th><th><?= t('email') ?></th><th><?= t('role') ?></th><th><?= t('created_at') ?></th><th><?= t('actions') ?></th></tr></thead>
          <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= (int)$u['id'] ?></td>
              <td><?= esc($u['name']) ?></td>
              <td><?= esc($u['email']) ?></td>
              <td><span class="badge bg-secondary"><?= esc($u['role']) ?></span></td>
              <td><?= esc($u['created_at']) ?></td>
              <td>
                <div class="d-flex flex-column gap-2">
                  <!-- Update role -->
                  <form class="d-flex gap-2" method="post">
                    <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
                    <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
                    <select class="form-select form-select-sm" name="role">
                      <option <?= $u['role']==='user'?'selected':'' ?> value="user"><?= t('user') ?></option>
                      <option <?= $u['role']==='agent'?'selected':'' ?> value="agent"><?= t('agent') ?></option>
                      <option <?= $u['role']==='admin'?'selected':'' ?> value="admin"><?= t('admin') ?></option>
                    </select>
                    <button class="btn btn-sm btn-primary" name="update_role" value="1"><?= t('save') ?></button>
                  </form>

                  <!-- Reset password -->
                  <?php if (user()['role'] === 'admin'): ?>
                  <form class="d-flex gap-2" method="post" onsubmit="return confirm('<?= ($lang==='ar') ? 'هل تريد إعادة تعيين كلمة المرور؟' : 'Reset this password?' ?>');">
                    <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
                    <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
                    <input class="form-control form-control-sm" type="password" name="new_pass" placeholder="<?= t('new_password') ?>" minlength="8" maxlength="64" required>
                    <input class="form-control form-control-sm" type="password" name="confirm_pass" placeholder="<?= t('confirm_password') ?>" minlength="8" maxlength="64" required>
                    <button class="btn btn-sm btn-outline-danger" name="reset_password" value="1"><?= t('reset_password') ?></button>
                  </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Branding -->
      <?php
        $logo = setting_get('logo_file','');
        $favicon = setting_get('favicon_file','');
        $og_image = setting_get('og_image_file','');
      ?>
      <div class="mb-4">
        <h5 class="mb-2"><?= ($lang==='ar') ? 'الهوية البصرية' : 'Branding' ?></h5>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
          <div class="mb-2">
            <label class="form-label"><?= ($lang==='ar') ? 'اسم الشركة' : 'Company Name' ?></label>
            <input class="form-control" name="company_name" value="<?= esc($company) ?>" maxlength="120">
          </div>

          <!-- Logo -->
          <div class="mb-2">
            <label class="form-label"><?= ($lang==='ar') ? 'الشعار (PNG/JPG/GIF، حتى 2MB)' : 'Logo (PNG/JPG/GIF, up to 2MB)' ?></label>
            <input class="form-control d-none" type="file" id="logoInput" name="logo" accept=".png,.jpg,.jpeg,.gif">
            <label for="logoInput" class="btn btn-outline-primary"><?= esc($choose_file) ?></label>
            <span id="logoFileName" class="ms-2 text-muted"><?= esc($no_file) ?></span>
            <?php if ($logo): ?><div class="mt-2"><img src="../uploads/branding/<?= esc($logo) ?>" alt="logo" style="max-height:56px"></div><?php endif; ?>
            <?php if ($logo): ?><button class="btn btn-sm btn-outline-danger mt-2" name="remove_logo" value="1" onclick="return confirm('<?= ($lang==='ar') ? 'حذف الشعار؟' : 'Remove logo?' ?>');"><?= ($lang==='ar') ? 'حذف الشعار' : 'Remove Logo' ?></button><?php endif; ?>
          </div>

          <!-- Favicon -->
          <div class="mb-2">
            <label class="form-label"><?= ($lang==='ar') ? 'Favicon (ICO/PNG، حتى 256KB)' : 'Favicon (ICO/PNG, up to 256KB)' ?></label>
            <input class="form-control d-none" type="file" id="favInput" name="favicon" accept=".ico,.png">
            <label for="favInput" class="btn btn-outline-primary"><?= esc($choose_file) ?></label>
            <span id="favFileName" class="ms-2 text-muted"><?= esc($no_file) ?></span>
            <?php if ($favicon): ?><div class="mt-2"><img src="../uploads/branding/<?= esc($favicon) ?>" alt="favicon" style="height:32px;width:32px"></div><?php endif; ?>
            <?php if ($favicon): ?><button class="btn btn-sm btn-outline-danger mt-2" name="remove_favicon" value="1" onclick="return confirm('<?= ($lang==='ar') ? 'حذف الأيقونة؟' : 'Remove favicon?' ?>');"><?= ($lang==='ar') ? 'حذف الأيقونة' : 'Remove Favicon' ?></button><?php endif; ?>
          </div>

          <!-- Open Graph Image -->
          <div class="mb-3">
            <label class="form-label"><?= ($lang==='ar') ? 'صورة المشاركة (OG) 1200×630 (PNG/JPG، حتى 2MB)' : 'Open Graph (OG) image 1200×630 (PNG/JPG, up to 2MB)' ?></label>
            <input class="form-control d-none" type="file" id="ogInput" name="og_image" accept=".png,.jpg,.jpeg">
            <label for="ogInput" class="btn btn-outline-primary"><?= esc($choose_file) ?></label>
            <span id="ogFileName" class="ms-2 text-muted"><?= esc($no_file) ?></span>
            <?php if ($og_image): ?><div class="mt-2"><img src="../uploads/branding/<?= esc($og_image) ?>" alt="og" style="max-height:120px"></div><?php endif; ?>
            <?php if ($og_image): ?><button class="btn btn-sm btn-outline-danger mt-2" name="remove_og_image" value="1" onclick="return confirm('<?= ($lang==='ar') ? 'حذف صورة المشاركة؟' : 'Remove OG image?' ?>');"><?= ($lang==='ar') ? 'حذف صورة المشاركة' : 'Remove OG Image' ?></button><?php endif; ?>
          </div>

          <button class="btn btn-success" name="branding_save" value="1"><?= ($lang==='ar') ? 'حفظ' : 'Save' ?></button>
        </form>
      </div>

      <!-- Email Notifications -->
      <div class="mb-3">
        <h5 class="mb-2"><?= ($lang==='ar') ? 'إشعارات البريد' : 'Email Notifications' ?></h5>
        <p class="text-muted small mb-3">
          <?= ($lang==='ar')
              ? 'فعّل أو عطّل كل نوع على حدة. لغة البريد تعتمد على لغة واجهة المستخدم وقت الحدث.'
              : 'Enable/disable each email type. Email language follows the current UI language at the time of the event.'; ?>
        </p>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
          <?php foreach ($notify as $key=>$val): ?>
            <?php $L = $LABELS[$key] ?? null; ?>
            <div class="border rounded p-2 mb-2">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="n_<?= esc($key) ?>" name="<?= esc($key) ?>" <?= $val==='1'?'checked':'' ?>>
                <label class="form-check-label fw-semibold" for="n_<?= esc($key) ?>">
                  <?php if ($L): ?>
                    <?= ($lang==='ar') ? esc($L['ar']) : esc($L['en']) ?>
                    <span class="text-muted ms-2">/ <?= esc($L['en']) ?></span>
                  <?php else: ?>
                    <?= esc($key) ?>
                  <?php endif; ?>
                </label>
              </div>
              <?php if ($L): ?>
                <div class="form-text mt-1">
                  <?= ($lang==='ar') ? esc($L['help_ar']) : esc($L['help_en']) ?>
                  <span class="text-muted">/ <?= esc($L['help_en']) ?></span>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          <button class="btn btn-primary" name="save_notifications" value="1">
            <?= ($lang==='ar') ? 'حفظ الإعدادات' : 'Save Settings' ?>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  var NO_FILE = <?= json_encode($no_file, JSON_UNESCAPED_UNICODE) ?>;
  function bind(id, spanId){
    var input = document.getElementById(id);
    var span = document.getElementById(spanId);
    var label = document.querySelector("label[for='"+id+"']");
    if (!input || !span || !label) return;
    input.addEventListener('change', function(){
      span.textContent = (this.files && this.files.length) ? this.files[0].name : NO_FILE;
    });
  }
  bind('logoInput','logoFileName');
  bind('favInput','favFileName');
  bind('ogInput','ogFileName');
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
