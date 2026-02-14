<?php
/**
 * Migration script to move unique phone numbers from tbl_icerik_bilgileri_ai_yedek2 to icerik_bilgileri
 */

require '/var/www/html/config.php';

echo "Migration started...\n";

try {
    // 1. Get total count of unique phone numbers from source table that don't exist in target table
    $countQuery = "
        SELECT COUNT(DISTINCT s.telefon_numarasi) as total_count
        FROM tbl_icerik_bilgileri_ai_yedek2 s
        LEFT JOIN icerik_bilgileri t ON s.telefon_numarasi = t.telefon_numarasi
        WHERE t.telefon_numarasi IS NULL
        AND s.telefon_numarasi IS NOT NULL
        AND s.telefon_numarasi != ''
    ";
    $totalCount = $pdo->query($countQuery)->fetch()['total_count'];

    echo "Total unique records to migrate: $totalCount\n";

    if ($totalCount > 0) {
        $batchSize = 1000;
        $processed = 0;

        while ($processed < $totalCount) {
            // Fetch unique phone numbers first
            $phoneQuery = "
                SELECT DISTINCT s.telefon_numarasi
                FROM tbl_icerik_bilgileri_ai_yedek2 s
                LEFT JOIN icerik_bilgileri t ON s.telefon_numarasi = t.telefon_numarasi
                WHERE t.telefon_numarasi IS NULL
                AND s.telefon_numarasi IS NOT NULL
                AND s.telefon_numarasi != ''
                LIMIT $batchSize
            ";
            $phones = $pdo->query($phoneQuery)->fetchAll(PDO::FETCH_COLUMN);

            if (empty($phones))
                break;

            $placeholders = implode(',', array_fill(0, count($phones), '?'));

            // Get the latest record for each of these phones
            $recordQuery = "
                SELECT s.* 
                FROM tbl_icerik_bilgileri_ai_yedek2 s
                JOIN (
                    SELECT MAX(id) as max_id
                    FROM tbl_icerik_bilgileri_ai_yedek2
                    WHERE telefon_numarasi IN ($placeholders)
                    GROUP BY telefon_numarasi
                ) latest ON s.id = latest.max_id
            ";

            $stmt = $pdo->prepare($recordQuery);
            $stmt->execute($phones);
            $recordsToMigrate = $stmt->fetchAll();

            $pdo->beginTransaction();

            $insertQuery = "
                INSERT INTO icerik_bilgileri (
                    satis_temsilcisi, status, basvuru_tarihi, ilk_erisim_tarihi, son_islem_tarihi,
                    tekrar_arama_tarihi, telefon_numarasi, musteri_adi_soyadi, email_adresi,
                    geldigi_yer, kampanya, talep_icerik, lead_puanlama, whatsapp_number,
                    manychat_link, hastane, bolum, brans, doktor, dogum_haftasi
                ) VALUES (
                    :satis_temsilcisi, :status, :basvuru_tarihi, :ilk_erisim_tarihi, :son_islem_tarihi,
                    :tekrar_arama_tarihi, :telefon_numarasi, :musteri_adi_soyadi, :email_adresi,
                    :geldigi_yer, :kampanya, :talep_icerik, :lead_puanlama, :whatsapp_number,
                    :manychat_link, :hastane, :bolum, :brans, :doktor, :dogum_haftasi
                )
            ";

            $insertStmt = $pdo->prepare($insertQuery);

            foreach ($recordsToMigrate as $row) {
                $insertStmt->execute([
                    ':satis_temsilcisi' => $row['satis_temsilcisi'] ?? '',
                    ':status' => $row['status'] ?? 1,
                    ':basvuru_tarihi' => $row['date'] ?? time(),
                    ':ilk_erisim_tarihi' => $row['date'] ?? time(),
                    ':son_islem_tarihi' => $row['date'] ?? time(),
                    ':tekrar_arama_tarihi' => $row['tekrar_arama_tarihi'] ?? 0,
                    ':telefon_numarasi' => $row['telefon_numarasi'],
                    ':musteri_adi_soyadi' => $row['musteri_adi_soyadi'] ?? '',
                    ':email_adresi' => $row['email_adresi'] ?? '',
                    ':geldigi_yer' => $row['geldigi_yer'] ?? '',
                    ':kampanya' => $row['kampanya'] ?? '',
                    ':talep_icerik' => $row['talep_icerik'] ?? '',
                    ':lead_puanlama' => $row['lead_puanlama'] ?? '',
                    ':whatsapp_number' => $row['channel_telefon_numarasi'] ?? $row['telefon_numarasi'],
                    ':manychat_link' => $row['manychat_link'] ?? '',
                    ':hastane' => $row['hastane'] ?? '',
                    ':bolum' => $row['bolum'] ?? '',
                    ':brans' => $row['brans'] ?? '',
                    ':doktor' => $row['doktor'] ?? '',
                    ':dogum_haftasi' => $row['dogum_haftasi'] ?? ''
                ]);
            }

            $pdo->commit();
            $processed += count($recordsToMigrate);
            echo "Processed $processed / $totalCount records...\n";

            // Avoid tight loop memory creeping
            unset($recordsToMigrate);
            unset($phones);
        }
        echo "Successfully migrated total $processed records.\n";
    } else {
        echo "No new records to migrate.\n";
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error during migration: " . $e->getMessage() . "\n";
}

echo "Migration finished.\n";
