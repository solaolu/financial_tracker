<?php
require_once __DIR__ . '/../../config/db.php';
class User {
    public static function register($username, $password) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        return $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
    }
    public static function login($username, $password) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) return $user;
        return false;
    }
    public static function resetPassword($username, $code, $newPassword) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND reset_code = ?");
        $stmt->execute([$username, $code]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_code = NULL WHERE username = ?");
            return $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $username]);
        }
        return false;
    }
}
?>