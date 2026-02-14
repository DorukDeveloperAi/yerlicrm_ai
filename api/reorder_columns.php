<?php
require_once '../config.php';

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $targetTable = 'tbl_icerik_bilgileri_ai'; // Default
    if (in_array('tbl_icerik_bilgileri_ai_icerik', $tables)) {
        $targetTable = 'tbl_icerik_bilgileri_ai_icerik';
    }

    // Get column info to preserve type
    $stmt = $pdo->query("DESCRIBE $targetTable");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $type = 'VARCHAR(50)'; // Default fallback
    foreach ($columns as $col) {
        if ($col['Field'] === 'telefon_numarasi') {
            $type = $col['Type'];
            break;
        }
    }

    echo "<h3>Tablo: $targetTable</h3>";
    echo "Kolon: telefon_numarasi ($type) -> id den sonrasına taşınıyor...<br>";

    $sql = "ALTER TABLE $targetTable MODIFY COLUMN telefon_numarasi $type AFTER id";
    $pdo->exec($sql);

    echo "<h3 style='color:green;'>İşlem Başarıyla Tamamlandı!</h3>";
    echo "<p>telefon_numarasi artık id kolonundan hemen sonra yer alıyor.</p>";

} catch (PDOException $e) {
    echo "<h3 style='color:red;'>Hata Oluştu:</h3> " . $e->getMessage();
}
?>