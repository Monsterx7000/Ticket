<?php
// includes/theme_toggle.php — Drop-in include for theme assets & toggle button
if (session_status() === PHP_SESSION_NONE) session_start();
$lang = $_SESSION['lang'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'ar');
$label_light = ($lang==='ar') ? 'الوضع الفاتح' : 'Light';
$label_dark  = ($lang==='ar') ? 'الوضع الداكن' : 'Dark';
?>
<link rel="stylesheet" href="/public/css/theme.css">
<script src="/public/js/theme.js" defer></script>

<div class="theme-toggle">
  <button class="btn btn-outline-secondary" type="button" onclick="toggleTheme()">
    <span id="themeToggleIcon">🌙</span>
    <span id="themeToggleLabel"
          data-light-label="<?= htmlspecialchars($label_dark) ?>"
          data-dark-label="<?= htmlspecialchars($label_light) ?>"
          class="ms-1"><?= htmlspecialchars($label_dark) ?></span>
  </button>
</div>
