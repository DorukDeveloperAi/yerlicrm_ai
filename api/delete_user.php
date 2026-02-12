<?php
require_once '../config.php';
require_once '../auth.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = $_POST['id'] ?? '';

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID gerekli.']);
    exit;
}

try {
    // Prevent deleting own account to avoid lockout (optional but good practice)
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
        echo json_encode(['success' => false, 'message' => 'Kendi hesabınızı silemezsiniz.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET status = 0 WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>