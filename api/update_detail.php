<?php
require_once '../config.php';
require_once '../auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$phone = $_POST['phone'] ?? '';
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

if (!$phone || !$field) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// Allowed fields to map to database columns and friendly names for the note
$allowedFields = [
    'kampanya' => 'Kampanya',
    'talep_icerik' => 'Talep İçerik',
    'hastane' => 'Hastane',
    'bolum' => 'Bölüm',
    'doktor' => 'Doktor',
    'dogum_haftasi' => 'Doğum Haftası',
    'user_id' => 'Satış Temsilcisi'
];

if (!array_key_exists($field, $allowedFields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field.']);
    exit;
}

try {
    // 1. Get the latest record
    $stmt = $pdo->prepare("SELECT * FROM tbl_icerik_bilgileri_ai WHERE telefon_numarasi = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$phone]);
    $latest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$latest) {
        echo json_encode(['success' => false, 'message' => 'Record not found.']);
        exit;
    }

    // 2. Prepare new data
    $newData = $latest;
    unset($newData['id']); // Remove ID to auto-increment
    unset($newData['created_at']); // Let DB set new timestamp if default, or we set it
    // Note: If 'date' column exists and represents row creation time, we might need to update it too. 
    // Assuming 'date' is unix timestamp based on previous code usage (date('d/m/Y', $c['date'])).
    $newData['date'] = time();

    // Update the specific field
    $newData[$field] = $value;

    // Clear message fields as requested
    $newData['personel_mesaji'] = '';
    $newData['musteri_mesaji'] = '';

    // Set change note
    $friendlyName = $allowedFields[$field];
    $displayValue = $value;

    // If updating user_id, try to get the name for the note
    if ($field === 'user_id') {
        $st_rep = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $st_rep->execute([$value]);
        $displayValue = $st_rep->fetchColumn() ?: $value;
    }

    $note = "$friendlyName: $displayValue Olarak Değiştirildi";
    $newData['yapilan_degisiklik_notu'] = $note;

    // 3. Construct Insert Query dynamically
    $columns = array_keys($newData);
    $placeholders = array_map(function ($col) {
        return ":$col";
    }, $columns);

    $sql = "INSERT INTO tbl_icerik_bilgileri_ai (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

    $insertStmt = $pdo->prepare($sql);

    foreach ($newData as $col => $val) {
        $insertStmt->bindValue(":$col", $val);
    }

    $insertStmt->execute();

    echo json_encode(['success' => true, 'message' => 'Değişiklik kaydedildi.']);

} catch (PDOException $e) {
    error_log("Update detail error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
