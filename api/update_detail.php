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
    'satis_temsilcisi' => 'Satış Temsilcisi'
];

if (!array_key_exists($field, $allowedFields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field.']);
    exit;
}

try {
    // Mapping internal field names to database columns if they differ
    $dbField = $field;

    // Check if sales representative is empty
    $check_stmt = $pdo->prepare("SELECT satis_temsilcisi FROM icerik_bilgileri WHERE telefon_numarasi = ?");
    $check_stmt->execute([$phone]);
    $current_st = $check_stmt->fetchColumn();

    $st_to_save = $current_st;

    // Eğer saha satis_temsilcisi ise ve bir değer gönderilmişse, manuel seçimi uygula
    if ($field === 'satis_temsilcisi' && !empty($value)) {
        // Eğer gelen değer sayısal ise (ID ise), ismine çevir (garanti için)
        if (is_numeric($value)) {
            $st_get = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $st_get->execute([$value]);
            $st_to_save = $st_get->fetchColumn() ?: $value;
        } else {
            $st_to_save = $value;
        }
        $value = $st_to_save; // Ana UPDATE sorgusu için value'yu da güncelle
    }
    // Eğer mevcut temsilci boşsa ve manuel bir seçim yapılmıyorsa otomatik ata
    elseif (empty($current_st) || $current_st == '0') {
        $st_to_save = $_SESSION['username'] ?? 'Sistem';
    }

    // Update the master table
    $sql = "UPDATE icerik_bilgileri SET $dbField = ?, son_islem_tarihi = ?, satis_temsilcisi = ? WHERE telefon_numarasi = ?";
    $stmt = $pdo->prepare($sql);
    $params = [$value, time(), $st_to_save, $phone];
    $stmt->execute($params);

    // Also record the change in the audit/log table (tbl_icerik_bilgileri_ai)
    // We fetch the latest record from log to clone it with the change note
    $stmt_latest = $pdo->prepare("SELECT * FROM tbl_icerik_bilgileri_ai WHERE telefon_numarasi = ? ORDER BY id DESC LIMIT 1");
    $stmt_latest->execute([$phone]);
    $latest = $stmt_latest->fetch(PDO::FETCH_ASSOC);

    if ($latest) {
        $newData = $latest;
        unset($newData['id']);
        // The requested fields to change
        $newData['date'] = time();
        $newData['user_id'] = $_SESSION['user_id'] ?? 0;
        $newData['ip_adresi'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $newData['kullanici_bilgileri_adi'] = $_SESSION['username'] ?? '';

        // Map field for log table (it uses internal field names)
        $newData[$field] = $value;

        // Clear messages as requested
        $newData['personel_mesaji'] = '';
        $newData['musteri_mesaji'] = '';

        $friendlyName = $allowedFields[$field];
        $displayValue = $value;
        if ($field === 'user_id') {
            $st_rep = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $st_rep->execute([$value]);
            $displayValue = $st_rep->fetchColumn() ?: $value;
        }
        $newData['yapilan_degisiklik_notu'] = "$friendlyName: $displayValue Olarak Değiştirildi";

        $columns = array_keys($newData);
        $placeholders = array_map(function ($col) {
            return ":$col";
        }, $columns);
        $logSql = "INSERT INTO tbl_icerik_bilgileri_ai (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $logStmt = $pdo->prepare($logSql);
        $logStmt->execute($newData);
    }

    echo json_encode(['success' => true, 'message' => 'Değişiklik kaydedildi.']);

} catch (PDOException $e) {
    error_log("Update detail error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
