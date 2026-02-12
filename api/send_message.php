<?php
require_once '../auth.php';
requireLogin();

$type = $_POST['type'] ?? 'text';
$templateId = $_POST['template_id'] ?? null;

if ($type === 'template' && $templateId) {
    $stmt = $pdo->prepare("SELECT content, gupshup_id, source_number FROM whatsapp_gupshup_templates WHERE id = ?");
    $stmt->execute([$templateId]);
    $template = $stmt->fetch();
    if ($template) {
        $message = $template['content'];
        $gupshup_template_id = $template['gupshup_id'];
        $source_number = $template['source_number'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Şablon bulunamadı.']);
        exit;
    }
}

if (!$phone || !$message) {
    echo json_encode(['success' => false, 'message' => 'Eksik bilgi.']);
    exit;
}

// --- GupShup API Call ---
$success = true; // Default to true for now to allow local testing
$error_msg = '';

if (defined('GUPSHUP_API_KEY') && !empty(GUPSHUP_API_KEY)) {
    // Implement GupShup API call logic here
    // For template messages, GupShup usually requires sending the template ID and potentially parameters.
    // Example logic (Conceptual):
    /*
    $ch = curl_init('https://api.gupshup.io/wa/api/v1/template/msg');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Cache-Control: no-cache',
        'Content-Type: application/x-www-form-urlencoded',
        'apikey: ' . GUPSHUP_API_KEY
    ]);

    $postBody = [
        'source' => GUPSHUP_SOURCE_NUMBER,
        'destination' => $phone,
        'template' => json_encode([
            'id' => $gupshup_template_id,
            'params' => [] // Add params if template is dynamic
        ]),
        'appname' => GUPSHUP_APP_NAME
    ];

    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postBody));
    $response = curl_exec($ch);
    $resData = json_decode($response, true);
    if ($resData['status'] !== 'submitted') {
        $success = false;
        $error_msg = $resData['message'] ?? 'API hatası';
    }
    curl_close($ch);
    */
}
// ------------------------

if (!$success) {
    echo json_encode(['success' => false, 'message' => 'Mesaj gönderilemedi: ' . $error_msg]);
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
