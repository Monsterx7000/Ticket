<?php
require_once __DIR__ . '/../db.php';
$email = $_GET['email'] ?? 'admin@example.com';
$newPassword = $_GET['pass'] ?? 'Admin@123';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if ($u) {
        $pdo->prepare("UPDATE users SET password=?, role='admin' WHERE id=?")->execute([$hash, $u['id']]);
        $msg = "Password RESET for admin: $email";
    } else {
        $pdo->prepare("INSERT INTO users (name,email,password,role,created_at) VALUES (?,?,?,?,NOW())")
            ->execute(['Admin', $email, $hash, 'admin']);
        $msg = "Admin CREATED: $email";
    }
    $pdo->commit();
    echo $msg . "\nUse these credentials:\nEmail: $email\nPassword: $newPassword";
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}