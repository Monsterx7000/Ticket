<?php
/**
 * includes/bilingual_file_input.php
 * Drop-in bilingual file input (AR/EN) that hides native "Choose file / No file chosen"
 * and replaces it with translatable labels. Works without Bootstrap.
 *
 * Usage:
 *   <?php
 *     $currentLang = $_SESSION['lang'] ?? ($_GET['lang'] ?? 'ar'); // adjust to your app
 *     include __DIR__ . '/bilingual_file_input.php';
 *     render_file_input([
 *        'name' => 'attachment',            // input name
 *        'id'   => 'ticketFile',            // input id (unique per page)
 *        'lang' => $currentLang,            // 'ar' or 'en'
 *        'multiple' => false,               // allow multiple files
 *        'accept' => ''                     // e.g. 'image/*,.pdf'
 *     ]);
 *   ?>
 */

if (!function_exists('render_file_input')) {
  function render_file_input(array $opts = []) {
    $name     = $opts['name']     ?? 'attachment';
    $id       = $opts['id']       ?? 'ticketFile';
    $lang     = strtolower($opts['lang'] ?? 'ar');
    $multiple = !empty($opts['multiple']);
    $accept   = $opts['accept']   ?? '';
    $dir      = ($lang === 'ar') ? 'rtl' : 'ltr';

    $t = [
      'ar' => [
        'choose' => 'اختر ملف',
        'none'   => 'لم يتم اختيار ملف',
        'change' => 'تغيير',
        'remove' => 'إزالة'
      ],
      'en' => [
        'choose' => 'Choose file',
        'none'   => 'No file chosen',
        'change' => 'Change',
        'remove' => 'Remove'
      ]
    ];
    $i18n = $t[$lang] ?? $t['ar'];

    // Ensure assets paths (adjust if you serve assets elsewhere)
    $cssPath = '/assets/css/bilingual_file_input.css';
    $jsPath  = '/assets/js/bilingual_file_input.js';

    // print assets once
    static $once = false;
    if (!$once) {
      echo '<link rel="stylesheet" href="'.$cssPath.'">';
      echo '<script>window.__BILINGUAL_FILE_INPUT_I18N = '.json_encode($t).';</script>';
      echo '<script src="'.$jsPath.'"></script>';
      $once = true;
    }

    // component markup
    echo '<div class="bf-input" dir="'.htmlspecialchars($dir).'"
               data-lang="'.htmlspecialchars($lang).'" 
               data-i18n="'.htmlspecialchars(json_encode($t)).'">';
    echo '  <input type="file" id="'.htmlspecialchars($id).'" name="'.htmlspecialchars($name).($multiple?'[]':'').'" '.($multiple?'multiple':'').' '.($accept?'accept="'.htmlspecialchars($accept).'"':'').' hidden>';
    echo '  <button type="button" class="bf-btn" data-action="browse" aria-controls="'.htmlspecialchars($id).'">'.$i18n['choose'].'</button>';
    echo '  <span class="bf-filename">'.$i18n['none'].'</span>';
    echo '  <button type="button" class="bf-icon" data-action="clear" title="'.htmlspecialchars($i18n['remove']).'" aria-controls="'.htmlspecialchars($id).'" style="display:none;">×</button>';
    echo '</div>';
  }
}
