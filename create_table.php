<?php
require_once 'config.php';

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
    echo "Table created successfully";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>