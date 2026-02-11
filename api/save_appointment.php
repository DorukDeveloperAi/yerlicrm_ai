<?php
require_once '../config.php';
require_once '../auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$service = $_POST['service'] ?? '';
$doctor_id = $_POST['doctor_id'] ?? '';
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';
$phone = $_POST['phone'] ?? '';

if (empty($service) || empty($doctor_id) || empty($date) || empty($time) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doldurunuz.']);
    exit;
}

$appointment_date = $date . ' ' . $time . ':00';

// Check availability (Mock check for now, can be expanded)
$check = $pdo->prepare("SELECT id FROM tbl_randevular WHERE doctor_id = ? AND appointment_date = ? AND status = 'active'");
$check->execute([$doctor_id, $appointment_date]);
if ($check->rowCount() > 0) {
    echo json_encode(['success' => false, 'message' => 'Seçilen tarih ve saatte doktor dolu.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO tbl_randevular (customer_phone, service, doctor_id, appointment_date) VALUES (?, ?, ?, ?)");
    $stmt->execute([$phone, $service, $doctor_id, $appointment_date]);
    echo json_encode(['success' => true, 'message' => 'Randevu başarıyla oluşturuldu.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>