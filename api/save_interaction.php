<?php
require_once '../auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$phone = $_POST['phone'] ?? '';
$type = $_POST['type'] ?? 'update'; // 'update' or 'inspector'
$note = $_POST['note'] ?? '';
$status_text = $_POST['status_id'] ?? '';
$callback_date = $_POST['callback_date'] ?? '';
$lead_score = $_POST['lead_score'] ?? '';
$complaint_hospital = $_POST['complaint_hospital'] ?? '';
$complaint_dept = $_POST['complaint_dept'] ?? '';
$complaint_doctor = $_POST['complaint_doctor'] ?? '';
$complaint_topic = $_POST['complaint_topic'] ?? '';
$complaint_detail = $_POST['complaint_detail'] ?? '';
$kampanya = $_POST['kampanya'] ?? '';

if (!$phone) {
    echo json_encode(['success' => false, 'message' => 'Telefon numarası eksik']);
    exit;
}

// Convert date to timestamp if provided
$callback_ts = $callback_date ? strtotime($callback_date) : 0;

// Handle file upload
$complaint_image = '';
if (isset($_FILES['complaint_image']) && $_FILES['complaint_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../assets/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $file_ext = pathinfo($_FILES['complaint_image']['name'], PATHINFO_EXTENSION);
    $file_name = uniqid() . '.' . $file_ext;
    if (move_uploaded_file($_FILES['complaint_image']['tmp_name'], $upload_dir . $file_name)) {
        $complaint_image = 'assets/uploads/' . $file_name;
    }
}

// Prepare Data
$data = [
    'user_id' => $_SESSION['user_id'],
    'ip_adresi' => $_SERVER['REMOTE_ADDR'],
    'date' => time(),
    'telefon_numarasi' => $phone,
    'personel_mesaji' => $note,
    'gorusme_sonucu_text' => $status_text,
    'tekrar_arama_tarihi' => $callback_ts,
    'lead_puanlama' => $lead_score,
    'sikayet_hastane' => $complaint_hospital,
    'sikayet_bolum' => $complaint_dept,
    'sikayet_doktor' => $complaint_doctor,
    'sikayet_konusu' => $complaint_topic,
    'sikayet_detayi' => $complaint_detail,
    'sikayet_gorseli' => $complaint_image,
    'kampanya' => $kampanya
];

if ($type === 'inspector') {
    $data['denetci_id'] = $_SESSION['user_id'];
    $data['denetci_adi'] = $_SESSION['username'] ?? 'Denetçi';
    $data['denetci_mesaj'] = $note;
    $data['denetci_mesaj_tarihi'] = time();
    $data['denetci_ip_adresi'] = $_SERVER['REMOTE_ADDR'];
}

// Insert into tbl_icerik_bilgileri_ai
$sql = "INSERT INTO tbl_icerik_bilgileri_ai (
    user_id, ip_adresi, date, telefon_numarasi, personel_mesaji, 
    gorusme_sonucu_text, tekrar_arama_tarihi, lead_puanlama, 
    sikayet_hastane, sikayet_bolum, sikayet_doktor, sikayet_konusu, 
    sikayet_detayi, sikayet_gorseli, 
    kampanya,
    denetci_id, denetci_adi, denetci_mesaj, denetci_mesaj_tarihi, denetci_ip_adresi
) VALUES (
    :user_id, :ip_adresi, :date, :telefon_numarasi, :personel_mesaji, 
    :gorusme_sonucu_text, :tekrar_arama_tarihi, :lead_puanlama, 
    :sikayet_hastane, :sikayet_bolum, :sikayet_doktor, :sikayet_konusu, 
    :sikayet_detayi, :sikayet_gorseli, 
    :kampanya,
    :denetci_id, :denetci_adi, :denetci_mesaj, :denetci_mesaj_tarihi, :denetci_ip_adresi
)";

// Ensure keys match exactly - Use safe defaults instead of null to avoid DB constraints
$insert_data = array_merge([
    'denetci_id' => 0,
    'denetci_adi' => '',
    'denetci_mesaj' => '',
    'denetci_mesaj_tarihi' => 0,
    'denetci_ip_adresi' => ''
], $data);

try {
    // 1. Fetch current data from master table for full context log
    $check_stmt = $pdo->prepare("SELECT * FROM icerik_bilgileri WHERE telefon_numarasi = ?");
    $check_stmt->execute([$phone]);
    $masterData = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$masterData) {
        echo json_encode(['success' => false, 'message' => 'Müşteri bulunamadı']);
        exit;
    }

    // 2. Prepare full context data for logging
    // We override master data with form data if provided
    $fullData = array_merge($masterData, $insert_data);
    unset($fullData['id']); // Remove ID from master before insert

    // SQL Error Fix: 'kayit_tarihi' exists in master but not in log (date is used instead)
    if (isset($fullData['kayit_tarihi'])) {
        if (empty($fullData['date'])) {
            $fullData['date'] = $fullData['kayit_tarihi'];
        }
        unset($fullData['kayit_tarihi']);
    }

    // Ensure metadata is correct
    $fullData['user_id'] = $_SESSION['user_id'];
    $fullData['kullanici_bilgileri_adi'] = $_SESSION['username'] ?? '';
    $fullData['ip_adresi'] = $_SERVER['REMOTE_ADDR'];
    $fullData['date'] = time();
    $fullData['personel_mesaji'] = $note;
    $fullData['telefon_numarasi'] = $phone;

    // Update fields from form
    if ($status_text)
        $fullData['gorusme_sonucu_text'] = $status_text;
    if ($callback_ts)
        $fullData['tekrar_arama_tarihi'] = $callback_ts;
    if ($lead_score)
        $fullData['lead_puanlama'] = $lead_score;
    if ($kampanya)
        $fullData['kampanya'] = $kampanya;

    // Complaint fields (only if it's an update/interaction, handled by form conditionally)
    if ($complaint_hospital)
        $fullData['sikayet_hastane'] = $complaint_hospital;
    if ($complaint_dept)
        $fullData['sikayet_bolum'] = $complaint_dept;
    if ($complaint_doctor)
        $fullData['sikayet_doktor'] = $complaint_doctor;
    if ($complaint_topic)
        $fullData['sikayet_konusu'] = $complaint_topic;
    if ($complaint_detail)
        $fullData['sikayet_detayi'] = $complaint_detail;
    if ($complaint_image)
        $fullData['sikayet_gorseli'] = $complaint_image;

    // Handle Inspector specific fields
    if ($type === 'inspector') {
        $fullData['denetci_id'] = $_SESSION['user_id'];
        $fullData['denetci_adi'] = $_SESSION['username'] ?? 'Denetçi';
        $fullData['denetci_mesaj'] = $note;
        $fullData['denetci_mesaj_tarihi'] = time();
        $fullData['denetci_ip_adresi'] = $_SERVER['REMOTE_ADDR'];
    }

    // Dynamic Column Filtering & Default Values: Fetch log table columns to avoid "Unknown column" or "No default value" errors
    $logColsStmt = $pdo->query("SHOW COLUMNS FROM tbl_icerik_bilgileri_ai");
    $validLogCols = $logColsStmt->fetchAll(PDO::FETCH_ASSOC);

    $finalLogData = [];
    foreach ($validLogCols as $colInfo) {
        $fieldName = $colInfo['Field'];
        $fieldType = strtolower($colInfo['Type']);

        if (isset($fullData[$fieldName]) && $fullData[$fieldName] !== null) {
            $finalLogData[$fieldName] = $fullData[$fieldName];
        } else {
            // Field exists in DB but not in our combined data or is null.
            // If it's NOT NULL and has no default, we MUST provide one.
            if ($colInfo['Null'] === 'NO' && $colInfo['Default'] === null) {
                // Determine safe default by type
                if (strpos($fieldType, 'int') !== false || strpos($fieldType, 'decimal') !== false || strpos($fieldType, 'float') !== false) {
                    $finalLogData[$fieldName] = 0;
                } else {
                    $finalLogData[$fieldName] = '';
                }
            } else {
                // If it is NULLable or has a default, we can just skip it or set to null
                // but for stability with PDO::execute, we can just not put it in finalLogData
                // unless it's a specific field we want to guarantee as empty string
                if ($fieldName === 'lead_kodu' || $fieldName === 'lead_id') {
                    $finalLogData[$fieldName] = '';
                }
            }
        }
    }

    // Insert into log table (Filtered & Sanitized Full Context)
    $columns = array_keys($finalLogData);
    $placeholders = array_map(function ($col) {
        return ":$col";
    }, $columns);
    $logSql = "INSERT INTO tbl_icerik_bilgileri_ai (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute($finalLogData);

    // 3. Update master table (icerik_bilgileri)
    // Only update satis_temsilcisi if it's currently empty
    $current_st = $masterData['satis_temsilcisi'];
    $st_to_save = $current_st;
    if (empty($current_st) || $current_st == '0') {
        $st_to_save = $_SESSION['username'] ?? 'Sistem';
    }

    $updateMasterSql = "UPDATE icerik_bilgileri 
                        SET son_mesaj_yeri = 'personel_mesaji', 
                            son_islem_tarihi = ?, 
                            satis_temsilcisi = ?,
                            gorusme_sonucu_text = ?,
                            tekrar_arama_tarihi = ?,
                            lead_puanlama = ?,
                            kampanya = ?
                        WHERE telefon_numarasi = ?";
    $stmtMaster = $pdo->prepare($updateMasterSql);
    $stmtMaster->execute([
        time(),
        $st_to_save,
        $status_text ?: $masterData['gorusme_sonucu_text'],
        $callback_ts ?: $masterData['tekrar_arama_tarihi'],
        $lead_score ?: $masterData['lead_puanlama'],
        $kampanya ?: $masterData['kampanya'],
        $phone
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
