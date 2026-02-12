<?php
require_once '../config.php';

try {
    $pdo->exec("ALTER TABLE tbl_whatsapp_templates RENAME TO whatsapp_gupshup_templates;");
    echo json_encode(['success' => true, 'message' => 'Table renamed successfully to whatsapp_gupshup_templates']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>