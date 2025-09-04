<?php
if (!isset($pdo)) { require_once __DIR__ . '/../db.php'; }
function setting_get($key, $default=null) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key`=? LIMIT 1");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}
function setting_set($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings(`key`,`value`) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)");
    $stmt->execute([$key, $value]);
}