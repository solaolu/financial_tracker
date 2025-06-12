<?php
require_once __DIR__ . '/../../config/db.php';
class Finance {
    public static function save($userId, $month, $budget, $expenses) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO finance (user_id, month, budget, expenses) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $month, $budget, $expenses]);
    }
    public static function getByUser($userId) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM finance WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
?>