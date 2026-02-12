<?php
require_once '../config.php';

try {
    // Check if table exists, if not create it with all fields
    $pdo->exec("CREATE TABLE IF NOT EXISTS whatsapp_gupshup_templates (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        gupshup_id VARCHAR(255) NULL,
        title VARCHAR(255) NOT NULL, 
        content TEXT NOT NULL, 
        status TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // If it was renamed but lacks gupshup_id, add it
    try {
        $pdo->exec("ALTER TABLE whatsapp_gupshup_templates ADD COLUMN IF NOT EXISTS gupshup_id VARCHAR(255) NULL AFTER id;");
    } catch (PDOException $e) {
        // Column might already exist or DB doesn't support IF NOT EXISTS for ADD COLUMN
    }

    echo json_encode(['success' => true, 'message' => 'Database configured successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>