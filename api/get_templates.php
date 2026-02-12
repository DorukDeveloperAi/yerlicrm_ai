<?php
require_once '../auth.php';
requireLogin();

try {
    $stmt = $pdo->query("SELECT id, title, content FROM tbl_whatsapp_templates WHERE status = 1 ORDER BY title ASC");
    $templates = $stmt->fetchAll();
    echo json_encode(['success' => true, 'templates' => $templates]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>