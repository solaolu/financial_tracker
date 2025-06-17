<?php
require_once '../db_connect.php'; // This will create the .sqlite file if it doesn't exist

try {
    $sql = file_get_contents('financial_tracker.sql'); // Read the SQL commands
    $pdo->exec($sql); // Execute the SQL
    echo "SQLite database initialized successfully!";
} catch (PDOException $e) {
    echo "Error initializing database: " . $e->getMessage();
}
?>