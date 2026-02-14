<?php
require_once 'auth.php';
requireLogin();

try {
    // tbl_icerik_bilgileri_ai tablosundaki denetçi alanlarını nullable yapalım
    // Böylece kod tarafında bir aksilik olsa bile integrity hatası almayız
    $stmt = $pdo->prepare("
        ALTER TABLE tbl_icerik_bilgileri_ai 
        MODIFY COLUMN denetci_id INT(11) NULL DEFAULT 0,
        MODIFY COLUMN denetci_adi VARCHAR(255) NULL DEFAULT '',
        MODIFY COLUMN denetci_mesaj TEXT NULL,
        MODIFY COLUMN denetci_mesaj_tarihi INT(11) NULL DEFAULT 0,
        MODIFY COLUMN denetci_ip_adresi VARCHAR(50) NULL DEFAULT '';
    ");
    $stmt->execute();

    echo "Veritabanı şeması başarıyla güncellendi. Denetçi alanları artık NULL kabul ediyor.";
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>