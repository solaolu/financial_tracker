<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/../database.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE,
    password TEXT,
    reset_code TEXT DEFAULT NULL
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS finance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    month TEXT,
    income REAL,
    expenses REAL,
    is_recurring INTEGER DEFAULT 0,
    FOREIGN KEY(user_id) REFERENCES users(id)
)");
?>