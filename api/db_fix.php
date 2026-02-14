<?php
require_once '../config.php';

echo "<h2>Veritabanı Güncelleme İşlemi</h2>";

try {
    // 1. whatsapp_gupshup_templates tablosunu kontrol et
    $cols = $pdo->query("SHOW COLUMNS FROM whatsapp_gupshup_templates")->fetchAll(PDO::FETCH_COLUMN);

    $missing = [];
    if (!in_array('source_number', $cols))
        $missing[] = "source_number VARCHAR(50) NULL";
    if (!in_array('image_url', $cols))
        $missing[] = "image_url VARCHAR(500) NULL";
    if (!in_array('gupshup_account_id', $cols))
        $missing[] = "gupshup_account_id INT NULL";

    if (!empty($missing)) {
        foreach ($missing as $colDef) {
            $colName = explode(' ', $colDef)[0];
            $pdo->exec("ALTER TABLE whatsapp_gupshup_templates ADD COLUMN $colDef");
            echo "Eklendi: <b>$colName</b><br>";
        }
    } else {
        echo "WhatsApp şablonları tablosu güncel.<br>";
    }

    // 2. tbl_icerik_bilgileri_ai tablosunu kontrol et
    $colsAI = $pdo->query("SHOW COLUMNS FROM tbl_icerik_bilgileri_ai")->fetchAll(PDO::FETCH_COLUMN);
    $missingAI = [];

    // Fields to be added (Yeni veya Silinecek olarak belirtilenler)
    $fieldsToAdd = [
        'kullanici_bilgileri_adi' => "VARCHAR(255) NULL",
        'whatsapp_name' => "VARCHAR(255) NULL",
        'whatsapp_phone_number' => "VARCHAR(50) NULL",
        'gupshup_messageId_giden' => "VARCHAR(100) NULL",
        'gupshup_message_sonucu' => "TEXT NULL",
        'gupshup_error_message' => "TEXT NULL",
        'gorusme_sonucu_text' => "VARCHAR(255) NULL",
        'sikayet_hastane' => "VARCHAR(255) NULL",
        'sikayet_bolum' => "VARCHAR(255) NULL",
        'sikayet_doktor' => "VARCHAR(255) NULL",
        'sikayet_konusu' => "VARCHAR(255) NULL",
        'sikayet_detayi' => "TEXT NULL",
        'sikayet_gorseli' => "VARCHAR(500) NULL",
        'denetci_id' => "INT NULL",
        'denetci_adi' => "VARCHAR(255) NULL",
        'denetci_ip_adresi' => "VARCHAR(50) NULL",
        'denetci_mesaj_tarihi' => "VARCHAR(50) NULL",
        'denetci_mesaj' => "TEXT NULL"
    ];

    // Add soru/cevap 1 to 10
    for ($i = 1; $i <= 10; $i++) {
        $fieldsToAdd["soru_$i"] = "TEXT NULL";
        $fieldsToAdd["cevap_$i"] = "TEXT NULL";
    }

    foreach ($fieldsToAdd as $colName => $colType) {
        if (!in_array($colName, $colsAI)) {
            $pdo->exec("ALTER TABLE tbl_icerik_bilgileri_ai ADD COLUMN $colName $colType");
            echo "Eklendi (AI Tablosu): <b>$colName</b><br>";
        } else {
            // Already exists, but ensure it is NULLable to avoid Integrity constraint violation
            $pdo->exec("ALTER TABLE tbl_icerik_bilgileri_ai MODIFY COLUMN $colName $colType");
            echo "Güncellendi (Nullable): <b>$colName</b><br>";
        }
    }

    echo "<br><h3 style='color:green;'>İşlem Başarıyla Tamamlandı!</h3>";
    echo "<p>Şimdi mesaj göndermeyi tekrar deneyebilirsiniz.</p>";
    echo "<a href='../chat.php'>Chat Ekranına Dön</a>";

} catch (PDOException $e) {
    echo "<h3 style='color:red;'>Hata Oluştu:</h3> " . $e->getMessage();
}
?>