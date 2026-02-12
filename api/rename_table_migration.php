<?php
require_once '../config.php';

try {
    // Check if table exists, if not create it with all fields
    $pdo->exec("CREATE TABLE IF NOT EXISTS whatsapp_gupshup_templates (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        gupshup_id VARCHAR(255) NULL,
        source_number VARCHAR(50) NULL,
        title VARCHAR(255) NOT NULL, 
        content TEXT NOT NULL, 
        status TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // If it was renamed but lacks gupshup_id or source_number, add them
    try {
        $pdo->exec("ALTER TABLE whatsapp_gupshup_templates ADD COLUMN IF NOT EXISTS gupshup_id VARCHAR(255) NULL AFTER id;");
        $pdo->exec("ALTER TABLE whatsapp_gupshup_templates ADD COLUMN IF NOT EXISTS source_number VARCHAR(50) NULL AFTER gupshup_id;");
    } catch (PDOException $e) {
        // Support older DB versions without IF NOT EXISTS for ADD COLUMN
        try {
            $pdo->exec("ALTER TABLE whatsapp_gupshup_templates ADD COLUMN gupshup_id VARCHAR(255) NULL AFTER id;");
        } catch (Exception $e2) {
        }
        try {
            $pdo->exec("ALTER TABLE whatsapp_gupshup_templates ADD COLUMN source_number VARCHAR(50) NULL AFTER gupshup_id;");
        } catch (Exception $e2) {
        }
    }

    echo json_encode(['success' => true, 'message' => 'Database configured successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>