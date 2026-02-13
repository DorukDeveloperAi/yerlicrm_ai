<?php
require_once '../auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = $_POST['id'] ?? null;
$name = $_POST['name'] ?? '';
$phone_number = $_POST['phone_number'] ?? '';
$api_key = $_POST['api_key'] ?? '';
$app_name = $_POST['app_name'] ?? '';

if (!$name || !$phone_number || !$api_key || !$app_name) {
    echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doldurun.']);
    exit;
}

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE gupshup_accounts SET name = ?, phone_number = ?, api_key = ?, app_name = ? WHERE id = ?");
        $stmt->execute([$name, $phone_number, $api_key, $app_name, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO gupshup_accounts (name, phone_number, api_key, app_name) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $phone_number, $api_key, $app_name]);
    }
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
