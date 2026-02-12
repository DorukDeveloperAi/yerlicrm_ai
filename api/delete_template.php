<?php
require_once '../auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method.']));
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : null;

if (!$id) {
    die(json_encode(['success' => false, 'message' => 'ID gereklidir.']));
}

try {
    $stmt = $pdo->prepare("UPDATE whatsapp_gupshup_templates SET status = 0 WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>