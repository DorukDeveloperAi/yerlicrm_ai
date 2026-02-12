<?php
require_once 'config.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status INT DEFAULT 1");
        echo "Successfully added 'status' column to 'users' table.<br>";
    } else {
        echo "'status' column already exists.<br>";
    }
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "<br>";
}
?>