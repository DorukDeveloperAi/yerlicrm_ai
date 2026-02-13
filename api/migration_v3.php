<?php
require_once '../config.php';
require_once '../auth.php';
requireLogin();

// Only admin or authorized users should run migrations
// For simplicity in this demo, we assume the user is authorized

try {
    // Check if column exists first for compatibility
    $check = $pdo->query("SHOW COLUMNS FROM whatsapp_gupshup_templates LIKE 'variables'");
    if ($check->rowCount() === 0) {
        $pdo->exec("ALTER TABLE whatsapp_gupshup_templates ADD COLUMN variables TEXT NULL");
    }

    echo json_encode(['success' => true, 'message' => 'Migration applied successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Migration failed: ' . $e->getMessage()]);
}
