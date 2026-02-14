<?php
require_once 'd:\antigravity\yerlicrm\src\config.php';

$phone = '905384719028';

echo "--- Checking $phone ---\n";

try {
    $stmt = $pdo->prepare("SELECT * FROM icerik_bilgileri WHERE telefon_numarasi = ?");
    $stmt->execute([$phone]);
    $master = $stmt->fetch();
    echo "Master table (icerik_bilgileri):\n";
    if ($master) {
        print_r($master);
    } else {
        echo "Not found in master table.\n";
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_icerik_bilgileri_ai WHERE telefon_numarasi = ?");
    $stmt->execute([$phone]);
    $logCount = $stmt->fetchColumn();
    echo "Log table (tbl_icerik_bilgileri_ai) count: $logCount\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>