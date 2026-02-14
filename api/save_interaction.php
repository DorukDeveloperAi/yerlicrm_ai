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
    $stmt = $pdo->prepare($sql);
    $stmt->execute($insert_data);

    // Update master table (icerik_bilgileri)
    // Only update satis_temsilcisi if it's currently empty
    $check_stmt = $pdo->prepare("SELECT satis_temsilcisi FROM icerik_bilgileri WHERE telefon_numarasi = ?");
    $check_stmt->execute([$phone]);
    $current_st = $check_stmt->fetchColumn();

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
                            lead_puanlama = ?
                        WHERE telefon_numarasi = ?";
    $stmtMaster = $pdo->prepare($updateMasterSql);
    $stmtMaster->execute([
        time(),
        $st_to_save,
        $status_text,
        $callback_ts,
        $lead_score,
        $phone
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
