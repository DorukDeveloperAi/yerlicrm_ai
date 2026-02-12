<?php
require_once '../auth.php';
requireLogin();

try {
    // Using * to be safe with dynamic schema changes during migration
    $stmt = $pdo->query("SELECT * FROM whatsapp_gupshup_templates WHERE status = 1 ORDER BY title ASC");
    $templates = $stmt->fetchAll();
    echo json_encode(['success' => true, 'templates' => $templates]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>