<?php
require_once '../config.php';
require_once '../auth.php';
requireLogin();

header('Content-Type: application/json');

try {
    $data = [
        'campaigns' => $pdo->query("SELECT DISTINCT kampanya FROM icerik_bilgileri WHERE kampanya IS NOT NULL AND kampanya != '' ORDER BY kampanya ASC")->fetchAll(PDO::FETCH_COLUMN),
        'hospitals' => $pdo->query("SELECT DISTINCT hastane FROM icerik_bilgileri WHERE hastane IS NOT NULL AND hastane != '' ORDER BY hastane ASC")->fetchAll(PDO::FETCH_COLUMN),
        'departments' => $pdo->query("SELECT DISTINCT bolum FROM icerik_bilgileri WHERE bolum IS NOT NULL AND bolum != '' ORDER BY bolum ASC")->fetchAll(PDO::FETCH_COLUMN),
        'branches' => $pdo->query("SELECT DISTINCT brans FROM icerik_bilgileri WHERE brans IS NOT NULL AND brans != '' ORDER BY brans ASC")->fetchAll(PDO::FETCH_COLUMN),
        'doctors' => $pdo->query("SELECT DISTINCT doktor FROM icerik_bilgileri WHERE doktor IS NOT NULL AND doktor != '' ORDER BY doktor ASC")->fetchAll(PDO::FETCH_COLUMN),
        'requests' => $pdo->query("SELECT DISTINCT talep_icerik FROM icerik_bilgileri WHERE talep_icerik IS NOT NULL AND talep_icerik != '' ORDER BY talep_icerik ASC")->fetchAll(PDO::FETCH_COLUMN),
        'personnel' => $pdo->query("SELECT id, username FROM users WHERE status = 1 ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC)
    ];

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>