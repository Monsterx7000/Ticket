<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';

function set_language() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $default = defined('DEFAULT_LANG') ? DEFAULT_LANG : 'ar';
    $lang = $_SESSION['lang'] ?? $default;
    if (isset($_GET['lang'])) {
        $req = $_GET['lang'];
        if (in_array($req, ['ar','en'], true)) {
            $_SESSION['lang'] = $req;
            // Redirect back to the same URI without duplicating the 'lang' param
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            // Remove existing lang param (if any)
            $uri = preg_replace('/([?&])lang=(ar|en)(&|$)/', '$1', $uri);
            // Clean trailing ? or &
            $uri = rtrim($uri, '?&');
            if ($uri === '') { $uri = '/'; }
            header('Location: ' . $uri);
            exit;
        }
    }
    return $_SESSION['lang'] ?? $lang;
}

function t($key) {
    static $L;
    if (!$L) {
        $lang = $_SESSION['lang'] ?? DEFAULT_LANG;
        $file = __DIR__ . '/../lang/' . ($lang === 'en' ? 'en' : 'ar') . '.php';
        $L = require $file;
    }
    return $L[$key] ?? $key;
}

function dir_attr() {
    $lang = $_SESSION['lang'] ?? DEFAULT_LANG;
    return $lang === 'ar' ? 'rtl' : 'ltr';
}

function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
// Map canonical priority values to localized labels without changing DB values.
function priority_label($p) {
    $p = strtolower(trim((string)$p));
    $map_en = ['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'];
    $map_ar = ['low'=>'منخفض','normal'=>'عادي','high'=>'مرتفع','urgent'=>'عاجل'];
    $lang = $_SESSION['lang'] ?? DEFAULT_LANG;
    $map = ($lang === 'ar') ? $map_ar : $map_en;
    return $map[$p] ?? esc($p);
}


function csrf_token() {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}

function check_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ok = isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']);
        if (!$ok) { http_response_code(400); die('Bad CSRF'); }
    }
}

function is_logged_in() { return !empty($_SESSION['user']); }
function user() { return $_SESSION['user'] ?? null; }
function require_login() { if (!is_logged_in()) { header('Location: index.php'); exit; } }
function require_role($role) {
    $u = user();
    if (!$u || !in_array($u['role'], (array)$role, true)) {
        http_response_code(403); die('Forbidden');
    }
}

function upload_file($field) {
    global $ALLOWED_EXT;
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) return null;
    if ($f['size'] > MAX_UPLOAD) return null;
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $ALLOWED_EXT, true)) return null;
    $basename = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = __DIR__ . '/../uploads/' . $basename;
    if (!move_uploaded_file($f['tmp_name'], $dest)) return null;
    return $basename;
}
