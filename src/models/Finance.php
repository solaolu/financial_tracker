<?php
require_once __DIR__ . '/../../config/db.php';
class Finance {
    public static function save($userId, $month, $income, $expenses, $isRecurring) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO finance (user_id, month, income, expenses, is_recurring) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $month, $income, $expenses, $isRecurring]);
    }
    public static function getByUser($userId) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM finance WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    public static function getLatestMonth($userId) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM finance WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
}
?>