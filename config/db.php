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
    budget REAL,
    expenses REAL,
    FOREIGN KEY(user_id) REFERENCES users(id)
)");
?>