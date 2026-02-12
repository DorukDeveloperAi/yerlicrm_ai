<?php
require_once 'config.php';

try {
    echo "Starting database optimization...<br>";

    // 1. Add indexes to tbl_icerik_bilgileri_ai
    $indexes = [
        "idx_phone" => "telefon_numarasi",
        "idx_date" => "date",
        "idx_user" => "user_id",
        "idx_kampanya" => "kampanya(100)",
        "idx_hastane" => "hastane(100)",
        "idx_bolum" => "bolum(100)",
        "idx_doktor" => "doktor(100)",
        "idx_talep" => "talep_icerik(150)"
    ];

    foreach ($indexes as $name => $col) {
        // Check if index exists
        $exists = $pdo->query("SHOW INDEX FROM tbl_icerik_bilgileri_ai WHERE Key_name = '$name'")->fetch();
        if (!$exists) {
            echo "Adding index $name... ";
            $pdo->exec("ALTER TABLE tbl_icerik_bilgileri_ai ADD INDEX $name ($col)");
            echo "Done.<br>";
        } else {
            echo "Index $name already exists.<br>";
        }
    }

    echo "Optimization completed successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>