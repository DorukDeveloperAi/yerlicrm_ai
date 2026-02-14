<?php
require_once 'auth.php';
requireLogin();

try {
    // lead_kodu artık kullanılmayacağı için hem ana tabloda hem log tablosunda nullable yapalım
    // Veya varsayılan değer ekleyelim. En temizi NULL kabul etmesidir.

    // Log tablosu için
    $stmt1 = $pdo->prepare("ALTER TABLE tbl_icerik_bilgileri_ai MODIFY COLUMN lead_kodu VARCHAR(255) NULL DEFAULT NULL");
    $stmt1->execute();

    // Ana tablo için (ne olur ne olmaz)
    $stmt2 = $pdo->prepare("ALTER TABLE icerik_bilgileri MODIFY COLUMN lead_kodu VARCHAR(255) NULL DEFAULT NULL");
    $stmt2->execute();

    echo "Veritabanı başarıyla güncellendi: lead_kodu artık NULL kabul ediyor.";
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>