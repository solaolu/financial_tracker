<?php
// This file establishes the database connection using PDO.

// Database path for SQLite
define('DB_PATH', __DIR__ . '/financial_tracker.sqlite'); // Database file will be in the same directory as db_connect.php

try {
    // Create a new PDO instance for SQLite
    $pdo = new PDO(
        "sqlite:" . DB_PATH,
        null, // SQLite does not use a username
        null, // SQLite does not use a password
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch results as associative arrays
            // PDO::ATTR_EMULATE_PREPARES => false, // Not typically needed for SQLite with file-based DB
        ]
    );
} catch (PDOException $e) {
    // Handle connection errors
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
?>