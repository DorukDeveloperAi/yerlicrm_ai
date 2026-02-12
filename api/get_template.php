<?php
require_once '../auth.php';
requireLogin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if (!$id) {
    die(json_encode(['success' => false, 'message' => 'ID gereklidir.']));
}

try {
    $stmt = $pdo->prepare("SELECT * FROM tbl_whatsapp_templates WHERE id = ? AND status = 1");
    $stmt->execute([$id]);
    $template = $stmt->fetch();

    if ($template) {
        echo json_encode(['success' => true, 'template' => $template]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Şablon bulunamadı.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>