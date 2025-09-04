<?php
// Category helpers
// Table expected: categories(id INT PK AI, slug VARCHAR(64) UNIQUE, name_en VARCHAR(120), name_ar VARCHAR(120),
//                          enabled TINYINT(1) DEFAULT 1, sort_order INT DEFAULT 0, created_at DATETIME, updated_at DATETIME)

require_once __DIR__ . '/../db.php';

function categories_enabled() {
    global $pdo;
    $stmt = $pdo->query("SELECT slug, name_en, name_ar FROM categories WHERE enabled=1 ORDER BY sort_order ASC, id ASC");
    return $stmt->fetchAll();
}

function categories_enabled_slugs() {
    $rows = categories_enabled();
    $out = [];
    foreach ($rows as $r) { $out[] = $r['slug']; }
    return $out;
}

function cat_label($slug, $lang = null) {
    if ($slug === null || $slug === '') return '';
    if ($lang === null) {
        $lang = $_SESSION['lang'] ?? (defined('DEFAULT_LANG') ? DEFAULT_LANG : 'ar');
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT name_en, name_ar FROM categories WHERE slug=? LIMIT 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    if (!$row) return $slug;
    return ($lang === 'ar' && !empty($row['name_ar'])) ? $row['name_ar'] : ($row['name_en'] ?: $slug);
}
