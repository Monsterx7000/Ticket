<?php
// Admin: Manage Categories (names AR/EN, slug, enabled, sort order).
// Safe-delete (prevents delete if tickets exist), CSRF protection, Admin-only.

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/categories.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_login(); require_role(['admin']); check_csrf();

$lang = $_SESSION['lang'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'ar');

$msg = ''; $err = '';

// Helpers
function slugify_basic($text) {
    // Keep ASCII letters/digits and dashes; spaces to dash; strip others.
    $text = trim($text);
    $text = preg_replace('~[\s_]+~u', '-', $text);
    $text = preg_replace('~[^A-Za-z0-9\-]+~u', '', $text);
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if ($text === '') $text = bin2hex(random_bytes(4));
    return substr($text, 0, 64);
}

function category_in_use($slug) {
    global $pdo;
    $q = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE category=?");
    $q->execute([$slug]);
    return (int)$q->fetchColumn() > 0;
}

function load_categories() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, slug, name_en, name_ar, enabled, sort_order, created_at, updated_at FROM categories ORDER BY sort_order ASC, id ASC");
    return $stmt->fetchAll();
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new category
    if (isset($_POST['add_category'])) {
        $name_en = trim($_POST['name_en'] ?? '');
        $name_ar = trim($_POST['name_ar'] ?? '');
        $slug    = trim($_POST['slug'] ?? '');
        $enabled = isset($_POST['enabled']) ? 1 : 0;
        $sort    = (int)($_POST['sort_order'] ?? 0);

        if ($name_en === '') {
            $err = ($lang==='ar') ? 'الرجاء إدخال الاسم بالإنجليزية' : 'Please enter English name';
        } else {
            if ($slug === '') $slug = slugify_basic($name_en);
            // Ensure unique slug
            $test = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug=?");
            $test->execute([$slug]);
            if ((int)$test->fetchColumn() > 0) {
                $err = ($lang==='ar') ? 'الـSlug مستخدم مسبقًا، الرجاء تغييره' : 'Slug already exists; please choose another';
            } else {
                $ins = $pdo->prepare("INSERT INTO categories (slug, name_en, name_ar, enabled, sort_order, created_at, updated_at) VALUES (?,?,?,?,?, NOW(), NOW())");
                $ins->execute([$slug, $name_en, $name_ar, $enabled, $sort]);
                $msg = ($lang==='ar') ? 'تم إضافة التصنيف' : 'Category added';
            }
        }
    }

    // Update existing category
    if (isset($_POST['update_category']) && isset($_POST['id'])) {
        $id      = (int)$_POST['id'];
        $name_en = trim($_POST['name_en'] ?? '');
        $name_ar = trim($_POST['name_ar'] ?? '');
        $slug    = trim($_POST['slug'] ?? '');
        $enabled = isset($_POST['enabled']) ? 1 : 0;
        $sort    = (int)($_POST['sort_order'] ?? 0);

        if ($name_en === '') {
            $err = ($lang==='ar') ? 'الرجاء إدخال الاسم بالإنجليزية' : 'Please enter English name';
        } elseif ($slug === '') {
            $err = ($lang==='ar') ? 'الرجاء إدخال الـSlug' : 'Please enter a slug';
        } else {
            // unique slug except self
            $chk = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug=? AND id<>?");
            $chk->execute([$slug, $id]);
            if ((int)$chk->fetchColumn() > 0) {
                $err = ($lang==='ar') ? 'الـSlug مستخدم في تصنيف آخر' : 'Slug is already used by another category';
            } else {
                $upd = $pdo->prepare("UPDATE categories SET slug=?, name_en=?, name_ar=?, enabled=?, sort_order=?, updated_at=NOW() WHERE id=?");
                $upd->execute([$slug, $name_en, $name_ar, $enabled, $sort, $id]);
                $msg = ($lang==='ar') ? 'تم تحديث التصنيف' : 'Category updated';
            }
        }
    }

    // Delete category (safe)
    if (isset($_POST['delete_category']) && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $row = $pdo->prepare("SELECT slug FROM categories WHERE id=?");
        $row->execute([$id]);
        $slug = $row->fetchColumn();
        if (!$slug) {
            $err = ($lang==='ar') ? 'التصنيف غير موجود' : 'Category not found';
        } elseif (category_in_use($slug)) {
            $err = ($lang==='ar') ? 'لا يمكن حذف التصنيف لوجود تذاكر مرتبطة به. يمكنك تعطيله بدلاً من ذلك.' : 'Cannot delete: tickets reference this category. You can disable it instead.';
        } else {
            $del = $pdo->prepare("DELETE FROM categories WHERE id=?");
            $del->execute([$id]);
            $msg = ($lang==='ar') ? 'تم حذف التصنيف' : 'Category deleted';
        }
    }
}

$cats = load_categories();
include __DIR__ . '/../includes/header.php';
?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><?= ($lang==='ar') ? 'إدارة التصنيفات' : 'Manage Categories' ?></h4>
        <a class="btn btn-outline-secondary" href="admin.php"><?= t('admin_panel') ?></a>
      </div>

      <?php if ($msg): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-danger"><?= esc($err) ?></div><?php endif; ?>

      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th>Slug</th>
              <th><?= ($lang==='ar') ? 'الاسم (EN)' : 'Name (EN)' ?></th>
              <th><?= ($lang==='ar') ? 'الاسم (AR)' : 'Name (AR)' ?></th>
              <th><?= ($lang==='ar') ? 'مفعل' : 'Enabled' ?></th>
              <th><?= ($lang==='ar') ? 'ترتيب' : 'Sort' ?></th>
              <th><?= t('actions') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$cats): ?>
              <tr><td colspan="7" class="text-center text-muted py-4"><?= ($lang==='ar') ? 'لا توجد تصنيفات بعد' : 'No categories yet' ?></td></tr>
            <?php endif; ?>
            <?php foreach ($cats as $c): ?>
              <tr>
                <td><?= (int)$c['id'] ?></td>
                <td>
                  <form class="d-flex gap-2" method="post">
                    <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                    <input class="form-control form-control-sm" name="slug" value="<?= esc($c['slug']) ?>" maxlength="64" required>
                </td>
                <td><input class="form-control form-control-sm" name="name_en" value="<?= esc($c['name_en']) ?>" maxlength="120" required></td>
                <td><input class="form-control form-control-sm" name="name_ar" value="<?= esc($c['name_ar']) ?>" maxlength="120"></td>
                <td class="text-center">
                  <input class="form-check-input" type="checkbox" name="enabled" <?= $c['enabled'] ? 'checked' : '' ?>>
                </td>
                <td style="width:100px"><input class="form-control form-control-sm" type="number" name="sort_order" value="<?= (int)$c['sort_order'] ?>"></td>
                <td class="text-nowrap">
                    <button class="btn btn-sm btn-primary" name="update_category" value="1"><?= t('save') ?></button>
                  </form>
                  <form class="d-inline" method="post" onsubmit="return confirm('<?= ($lang==='ar') ? 'هل تريد حذف هذا التصنيف؟' : 'Delete this category?' ?>');">
                    <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger" name="delete_category" value="1"><?= t('delete') ?></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card p-3">
      <h5 class="mb-3"><?= ($lang==='ar') ? 'إضافة تصنيف جديد' : 'Add New Category' ?></h5>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= esc(csrf_token()) ?>">
        <div class="mb-2">
          <label class="form-label"><?= ($lang==='ar') ? 'الاسم (بالإنجليزية)' : 'Name (English)' ?></label>
          <input class="form-control" name="name_en" maxlength="120" required>
          <div class="form-text"><?= ($lang==='ar') ? 'مطلوب، يُستخدم أيضًا لتوليد الـSlug إذا تركته فارغًا' : 'Required; used to auto-generate slug if left blank' ?></div>
        </div>
        <div class="mb-2">
          <label class="form-label"><?= ($lang==='ar') ? 'الاسم (بالعربية)' : 'Name (Arabic)' ?></label>
          <input class="form-control" name="name_ar" maxlength="120">
        </div>
        <div class="mb-2">
          <label class="form-label">Slug</label>
          <input class="form-control" name="slug" maxlength="64" placeholder="auto-from-English-name">
          <div class="form-text"><?= ($lang==='ar') ? 'حروف/أرقام إنجليزية وشرطـة (-). إن تركته فارغًا يُولّد تلقائيًا.' : 'ASCII letters/digits and dash (-). Leave blank to auto-generate.' ?></div>
        </div>
        <div class="mb-2">
          <label class="form-label"><?= ($lang==='ar') ? 'ترتيب' : 'Sort order' ?></label>
          <input class="form-control" type="number" name="sort_order" value="0">
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="enabledAdd" name="enabled" checked>
          <label class="form-check-label" for="enabledAdd"><?= ($lang==='ar') ? 'مفعل' : 'Enabled' ?></label>
        </div>
        <button class="btn btn-success" name="add_category" value="1"><?= ($lang==='ar') ? 'إضافة' : 'Add' ?></button>
      </form>
    </div>

    <div class="card p-3 mt-3">
      <h6 class="mb-2"><?= ($lang==='ar') ? 'ملاحظات' : 'Notes' ?></h6>
      <ul class="small mb-0">
        <li><?= ($lang==='ar') ? 'التذاكر تُخزّن الفئة كـSlug. عند تعديل الـSlug، تأكد من التوافق.' : 'Tickets store the category by slug. When changing a slug, ensure consistency.' ?></li>
        <li><?= ($lang==='ar') ? 'إذا كانت هناك تذاكر مرتبطة بتصنيف، لا يمكن حذفه—يمكنك تعطيله بدلاً من ذلك.' : 'If tickets reference a category, you cannot delete it — disable it instead.' ?></li>
        <li><?= ($lang==='ar') ? 'يعتمد عرض الاسم في الواجهة على لغة المستخدم (AR/EN).' : 'Display name is localized based on UI language (AR/EN).' ?></li>
      </ul>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
