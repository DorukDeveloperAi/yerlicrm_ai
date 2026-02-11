<?php
// This file is meant to be included in index.php temporarily to create the table
$sql = "CREATE TABLE IF NOT EXISTS tbl_randevular (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_phone VARCHAR(20),
    service VARCHAR(100),
    doctor_id INT,
    appointment_date DATETIME,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (customer_phone),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

try {
    $pdo->exec($sql);
    // Silent success, or log to file
    file_put_contents('db_setup.log', 'Table tbl_randevular created successfully at ' . date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);
} catch (PDOException $e) {
    file_put_contents('db_setup.log', 'Error creating table: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
}
?>