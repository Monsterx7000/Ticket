<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/settings.php';
$lang = set_language();
$dir = dir_attr();
$logo = setting_get('logo_file', '');
$company = setting_get('company_name', APP_NAME);

// Build safe links that preserve current URI and strip existing lang
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';
$uriNoLang = preg_replace('/([?&])lang=(ar|en)(&|$)/', '$1', $currentUri);
$uriNoLang = rtrim($uriNoLang, '?&');
function with_lang($base, $lang) {
  $sep = (strpos($base, '?') === false) ? '?' : '&';
  return $base . $sep . 'lang=' . $lang;
}
?>
<!doctype html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($company) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body { background:#f7f7fb; }
    .card { border:0; border-radius: 1rem; box-shadow: 0 6px 18px rgba(0,0,0,.06); }
    .rtl .form-control, .rtl .btn { text-align:right; }
    .brand-logo { height: 32px; width: auto; object-fit: contain; }
  </style>
</head>
<body class="<?= $dir==='rtl' ? 'rtl' : '' ?>">
    <?php include __DIR__ . '/../includes/theme_toggle.php'; ?>
<nav class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="dashboard.php">
      <?php if ($logo): ?>
        <img class="brand-logo" src="../uploads/branding/<?= esc($logo) ?>" alt="logo">
      <?php endif; ?>
      <span><?= esc($company) ?></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="nav" class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <?php if (is_logged_in()): ?>
          <li class="nav-item"><a class="nav-link" href="tickets.php"><?= t('tickets') ?></a></li>
          <li class="nav-item"><a class="nav-link" href="ticket_new.php"><?= t('new_ticket') ?></a></li>
          <?php if (in_array(user()['role'], ['admin','agent'])): ?>
            <li class="nav-item"><a class="nav-link" href="admin.php"><?= t('admin_panel') ?></a></li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><?= $lang==='ar'?'العربية':'English' ?></a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= esc(with_lang($uriNoLang,'ar')) ?>">العربية</a></li>
            <li><a class="dropdown-item" href="<?= esc(with_lang($uriNoLang,'en')) ?>">English</a></li>
          </ul>
        </li>
        <?php if (is_logged_in()): ?>
          <li class="nav-item">
            <a class="nav-link" href="logout.php"
               onclick="return confirm('<?= ($_SESSION['lang'] ?? DEFAULT_LANG)==='ar' ? 'هل أنت متأكد أنك تريد تسجيل الخروج؟' : 'Are you sure you want to logout?'; ?>');">
               <?= t('logout') ?>
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="index.php"><?= t('login') ?></a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container my-4">
