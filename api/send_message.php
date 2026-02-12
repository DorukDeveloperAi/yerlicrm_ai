<?php
require_once '../auth.php';
requireLogin();

$type = $_POST['type'] ?? 'text';
$templateId = $_POST['template_id'] ?? null;

if ($type === 'template' && $templateId) {
    $stmt = $pdo->prepare("SELECT content FROM whatsapp_gupshup_templates WHERE id = ?");
    $stmt->execute([$templateId]);
    $template = $stmt->fetch();
    if ($template) {
        $message = $template['content'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Şablon bulunamadı.']);
        exit;
    }
}

if (!$phone || !$message) {
    echo json_encode(['success' => false, 'message' => 'Eksik bilgi.']);
    exit;
}

// --- GupShup API Call Placeholder ---
// TODO: Implement GupShup API call here ($type, $phone, $message)
// ------------------------------------

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
