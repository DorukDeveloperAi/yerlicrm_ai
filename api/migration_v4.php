<?php
require_once '../config.php';

try {
    // 1. Create gupshup_accounts table
    $pdo->exec("CREATE TABLE IF NOT EXISTS gupshup_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        phone_number VARCHAR(50) NOT NULL,
        api_key VARCHAR(255) NOT NULL,
        app_name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Add gupshup_account_id to whatsapp_gupshup_templates if not exists
    $stmt = $pdo->query("SHOW COLUMNS FROM whatsapp_gupshup_templates LIKE 'gupshup_account_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE whatsapp_gupshup_templates ADD COLUMN gupshup_account_id INT NULL;");
    }

    echo json_encode(['success' => true, 'message' => 'Migration v4 (GupShup Accounts) completed successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Migration failed: ' . $e->getMessage()]);
}
