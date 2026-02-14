<?php
require_once 'auth.php';
requireLogin();

try {
    // Kullanıcı talebi: icerik_bilgileri tablosuna yeni alan eklenmeyecek.
    // Sadece tbl_icerik_bilgileri_ai (log) tablosundaki kısıtlamaları esnetiyoruz.

    function safeModifyColumn($pdo, $table, $column)
    {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($stmt->fetch()) {
            $pdo->exec("ALTER TABLE `$table` MODIFY COLUMN `$column` VARCHAR(255) NULL DEFAULT NULL");
            return "[$table] '$column' alanı NULL kabul edecek şekilde güncellendi.<br>";
        }
        return "[$table] '$column' alanı bulunamadı, atlandı.<br>";
    }

    // Sadece log tablosu güncelleniyor
    echo safeModifyColumn($pdo, 'tbl_icerik_bilgileri_ai', 'lead_kodu');
    echo safeModifyColumn($pdo, 'tbl_icerik_bilgileri_ai', 'lead_id');

    echo "<br>İşlem tamamlandı. Ana tabloya (icerik_bilgileri) herhangi bir alan eklenmedi.";
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>