<?php
require_once 'config.php';

try {
    // 1. icerik_bilgileri tablosunda satis_temsilcisi kolonunu VARCHAR yap
    $pdo->exec("ALTER TABLE icerik_bilgileri MODIFY COLUMN satis_temsilcisi VARCHAR(255) NULL");
    echo "icerik_bilgileri modified.\n";

    // 2. tbl_icerik_bilgileri_ai tablosunda satis_temsilcisi kolonu yoksa ekle, varsa VARCHAR yap
    // Not: Bu tablo zaten user_id (işlem yapan kişi) içeriyor, satis_temsilcisi ise verinin kendisi.
    $stmt = $pdo->query("SHOW COLUMNS FROM tbl_icerik_bilgileri_ai LIKE 'satis_temsilcisi'");
    if ($stmt->fetch()) {
        $pdo->exec("ALTER TABLE tbl_icerik_bilgileri_ai MODIFY COLUMN satis_temsilcisi VARCHAR(255) NULL");
        echo "tbl_icerik_bilgileri_ai modified.\n";
    } else {
        $pdo->exec("ALTER TABLE tbl_icerik_bilgileri_ai ADD COLUMN satis_temsilcisi VARCHAR(255) NULL AFTER user_id");
        echo "tbl_icerik_bilgileri_ai: satis_temsilcisi column added.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>