<?php
require_once '../auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method.']));
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : null;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

if (empty($title) || empty($content)) {
    die(json_encode(['success' => false, 'message' => 'Başlık ve içerik gereklidir.']));
}

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE whatsapp_gupshup_templates SET title = ?, content = ? WHERE id = ?");
        $stmt->execute([$title, $content, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO whatsapp_gupshup_templates (title, content) VALUES (?, ?)");
        $stmt->execute([$title, $content]);
    }
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>