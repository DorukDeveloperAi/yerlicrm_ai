<?php
require_once '../auth.php';
requireLogin();

$phone = $_POST['phone'] ?? '';
$message = $_POST['message'] ?? '';

if (!$phone || !$message) {
    echo json_encode(['success' => false, 'message' => 'Eksik bilgi.']);
    exit;
}

// Append message to table
$stmt = $pdo->prepare("INSERT INTO tbl_icerik_bilgileri_ai (telefon_numarasi, personel_mesaji, date, user_id, status) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([
    $phone,
    $message,
    time(),
    $_SESSION['user_id'],
    1
]);

echo json_encode(['success' => true]);
