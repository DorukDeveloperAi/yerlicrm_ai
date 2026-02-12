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
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'user';

// New Fields (Handle Array Inputs for Multi-Select)
$telefon_numarasi = $_POST['telefon_numarasi'] ?? '';
$telefon_numarasi_2 = $_POST['telefon_numarasi_2'] ?? '';
$telefon_numarasi_3 = $_POST['telefon_numarasi_3'] ?? '';
$sensor_kodu = $_POST['sensor_kodu'] ?? '';

$kullanici_grup_bilgileri = isset($_POST['kullanici_grup_bilgileri']) && is_array($_POST['kullanici_grup_bilgileri']) ? implode(', ', $_POST['kullanici_grup_bilgileri']) : '';
$sorumlu_oldugu_hastane = isset($_POST['sorumlu_oldugu_hastane']) && is_array($_POST['sorumlu_oldugu_hastane']) ? implode(', ', $_POST['sorumlu_oldugu_hastane']) : '';
$sorumlu_oldugu_doktor = isset($_POST['sorumlu_oldugu_doktor']) && is_array($_POST['sorumlu_oldugu_doktor']) ? implode(', ', $_POST['sorumlu_oldugu_doktor']) : '';
$sorumlu_oldugu_kampanya = isset($_POST['sorumlu_oldugu_kampanya']) && is_array($_POST['sorumlu_oldugu_kampanya']) ? implode(', ', $_POST['sorumlu_oldugu_kampanya']) : '';
$sorumlu_oldugu_sikayet_konusu = isset($_POST['sorumlu_oldugu_sikayet_konusu']) && is_array($_POST['sorumlu_oldugu_sikayet_konusu']) ? implode(', ', $_POST['sorumlu_oldugu_sikayet_konusu']) : '';

if (!$username) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı adı zorunludur.']);
    exit;
}

try {
    if ($id) {
        // Update existing user
        $sql = "UPDATE users SET 
                username = ?, 
                role = ?, 
                telefon_numarasi = ?, 
                telefon_numarasi_2 = ?, 
                telefon_numarasi_3 = ?, 
                sensor_kodu = ?, 
                kullanici_grup_bilgileri = ?, 
                sorumlu_oldugu_hastane = ?, 
                sorumlu_oldugu_doktor = ?, 
                sorumlu_oldugu_kampanya = ?, 
                sorumlu_oldugu_sikayet_konusu = ?";

        $params = [
            $username,
            $role,
            $telefon_numarasi,
            $telefon_numarasi_2,
            $telefon_numarasi_3,
            $sensor_kodu,
            $kullanici_grup_bilgileri,
            $sorumlu_oldugu_hastane,
            $sorumlu_oldugu_doktor,
            $sorumlu_oldugu_kampanya,
            $sorumlu_oldugu_sikayet_konusu
        ];

        if ($password) {
            $sql .= ", password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

    } else {
        // Create new user
        if (!$password) {
            echo json_encode(['success' => false, 'message' => 'Yeni kullanıcı için şifre zorunludur.']);
            exit;
        }

        $sql = "INSERT INTO users (
            username, password, role, 
            telefon_numarasi, telefon_numarasi_2, telefon_numarasi_3, 
            sensor_kodu, kullanici_grup_bilgileri, 
            sorumlu_oldugu_hastane, sorumlu_oldugu_doktor, 
            sorumlu_oldugu_kampanya, sorumlu_oldugu_sikayet_konusu
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            $role,
            $telefon_numarasi,
            $telefon_numarasi_2,
            $telefon_numarasi_3,
            $sensor_kodu,
            $kullanici_grup_bilgileri,
            $sorumlu_oldugu_hastane,
            $sorumlu_oldugu_doktor,
            $sorumlu_oldugu_kampanya,
            $sorumlu_oldugu_sikayet_konusu
        ]);
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>