<?php
require_once '../auth.php';
requireLogin();

// Disable error display for JSON response consistency
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {

    $type = $_POST['type'] ?? 'text';
    $templateId = $_POST['template_id'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $message = $_POST['message'] ?? null;

    // --- Fetch Latest Customer Data for Context ---
    $customerData = null;
    if ($phone) {
        $cStmt = $pdo->prepare("
            SELECT t1.*, u.username as rep_name 
            FROM tbl_icerik_bilgileri_ai t1 
            LEFT JOIN users u ON t1.user_id = u.id 
            WHERE t1.telefon_numarasi = ? 
            ORDER BY t1.id DESC LIMIT 1
        ");
        $cStmt->execute([$phone]);
        $customerData = $cStmt->fetch();
    }

    if ($type === 'template' && $templateId) {
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_gupshup_templates WHERE id = ?");
        $stmt->execute([$templateId]);
        $template = $stmt->fetch();
        if ($template) {
            $message = $template['content'] ?? '';
            $gupshup_template_id = $template['gupshup_id'] ?? '';
            $source_number = $template['source_number'] ?? null;
            $image_url = $template['image_url'] ?? null;
            $variablesMap = isset($template['variables']) ? json_decode($template['variables'], true) : null;

            // --- Variable Replacement Logic ---
            if ($variablesMap && !empty($variablesMap) && $customerData) {
                foreach ($variablesMap as $placeholder => $field) {
                    $val = $customerData[$field] ?? '';
                    $message = str_replace($placeholder, $val, $message);
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Şablon bulunamadı.']);
            exit;
        }
    }

    if (!$phone || !$message) {
        echo json_encode(['success' => false, 'message' => 'Eksik bilgi.']);
        exit;
    }

    // --- GupShup API Call ---
    $success = true;
    $status_msg = 'Gönderildi';
    $api_response = null;

    // --- Load GupShup Credentials ---
    $api_key = defined('GUPSHUP_API_KEY') ? GUPSHUP_API_KEY : '';
    $src_num = defined('GUPSHUP_SOURCE_NUMBER') ? GUPSHUP_SOURCE_NUMBER : '';
    $app_name = defined('GUPSHUP_APP_NAME') ? GUPSHUP_APP_NAME : '';

    // If template has specific account, use it. Otherwise, if no global credentials, try to pick first account.
    $target_account_id = $template['gupshup_account_id'] ?? null;

    if (empty($api_key) && !$target_account_id) {
        $accStmt = $pdo->query("SELECT * FROM gupshup_accounts ORDER BY id ASC LIMIT 1");
        $account = $accStmt->fetch();
        if ($account) {
            $target_account_id = $account['id'];
        }
    }

    if ($target_account_id) {
        $accStmt = $pdo->prepare("SELECT * FROM gupshup_accounts WHERE id = ?");
        $accStmt->execute([$target_account_id]);
        $account = $accStmt->fetch();
        if ($account) {
            $api_key = $account['api_key'];
            $app_name = $account['app_name'];
            // Strictly use the phone number from the account table
            if (!empty($account['phone_number'])) {
                $src_num = $account['phone_number'];
            }
        }
    }

    if (!empty($api_key)) {
        $endpoint = 'https://api.gupshup.io/wa/api/v1/msg';
        $postBody = [
            'channel' => 'whatsapp',
            'source' => $src_num,
            'destination' => $phone,
            'appname' => $app_name
        ];

        if ($type === 'template' && !empty($gupshup_template_id)) {
            $endpoint = 'https://api.gupshup.io/wa/api/v1/template/msg';

            // Prepare params in order {{1}}, {{2}}, ...
            $params = [];
            if ($variablesMap && is_array($variablesMap)) {
                // Extract keys like {{1}}, {{2}} and sort them
                $keys = array_keys($variablesMap);
                sort($keys); // Ensures {{1}} comes before {{2}}
                foreach ($keys as $k) {
                    $field = $variablesMap[$k];
                    $params[] = (string) ($customerData[$field] ?? '');
                }
            }

            $templateObj = [
                'id' => $gupshup_template_id,
                'params' => $params
            ];

            // Add Header Values if there's an image
            if (!empty($image_url)) {
                $templateObj['headerValues'] = [
                    'media' => [
                        'type' => 'image',
                        'url' => $image_url
                    ]
                ];
            }

            $postBody['template'] = json_encode($templateObj);
        } else {
            // For regular text messages, GupShup expects a JSON object in the 'message' field
            $postBody['message'] = json_encode([
                'type' => 'text',
                'text' => $message
            ]);
        }

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Cache-Control: no-cache',
            'Content-Type: application/x-www-form-urlencoded',
            'apikey: ' . $api_key
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postBody));

        $response = curl_exec($ch);
        $resData = json_decode($response, true);
        $api_response = $resData;

        // GupShup success status is usually 'submitted' or 'accepted'
        if (isset($resData['status']) && ($resData['status'] === 'submitted' || $resData['status'] === 'accepted')) {
            $success = true;
            $status_msg = 'Başarıyla iletildi (GupShup: ' . $resData['status'] . ')';
        } else {
            $success = false;
            $status_msg = $resData['message'] ?? ($resData['error']['message'] ?? 'Bilinmeyen API hatası');
        }
        curl_close($ch);
    }
    // ------------------------

    if (!$success) {
        echo json_encode(['success' => false, 'message' => 'GupShup Hatası: ' . $status_msg, 'api_raw' => $api_response]);
        exit;
    }

    // --- Append message to table (Dynamically clone latest record) ---
    if ($customerData) {
        $newRecord = $customerData;

        // Remove ID and other internal fields to allow auto-increment and fresh data
        unset($newRecord['id']);

        // --- Column Mapping Updates based on Image ---

        // Yeni (New) fields
        $newRecord['user_id'] = $_SESSION['user_id'];
        $newRecord['ip_adresi'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $newRecord['date'] = time();
        $newRecord['kullanici_bilgileri_adi'] = $customerData['rep_name'] ?? ''; // From user join
        $newRecord['whatsapp_name'] = $app_name;
        $newRecord['whatsapp_phone_number'] = $src_num;
        $newRecord['gupshup_messageId_giden'] = $resData['messageId'] ?? '';
        $newRecord['gupshup_message_sonucu'] = json_encode($resData);
        $newRecord['gupshup_error_message'] = $success ? '' : $status_msg;
        $newRecord['gorusme_sonucu_text'] = ($type === 'template') ? 'Şablon: ' . ($template['title'] ?? '') : 'Metin Mesajı';

        // Silinecek (To be cleared) fields
        $newRecord['musteri_mesaji'] = '';
        $newRecord['personel_mesaji'] = $message;
        $newRecord['yapilan_degisiklik_notu'] = '';
        $newRecord['dosya'] = '';
        $newRecord['dosya_adi'] = '';
        $newRecord['type'] = '';
        $newRecord['contentType'] = '';
        $newRecord['gupshup_id_gelen'] = '';

        // Clear Sikayet fields
        $newRecord['sikayet_hastane'] = '';
        $newRecord['sikayet_bolum'] = '';
        $newRecord['sikayet_doktor'] = '';
        $newRecord['sikayet_konusu'] = '';
        $newRecord['sikayet_detayi'] = '';
        $newRecord['sikayet_gorseli'] = '';

        // Clear Soru/Cevap fields
        for ($i = 1; $i <= 10; $i++) {
            $newRecord["soru_$i"] = '';
            $newRecord["cevap_$i"] = '';
        }

        // Clear Audit/Denetci fields
        $newRecord['denetci_id'] = 0;
        $newRecord['denetci_adi'] = null;
        $newRecord['denetci_ip_adresi'] = null;
        $newRecord['denetci_mesaj_tarihi'] = null;
        $newRecord['denetci_mesaj'] = null;

        // Removing rep_name from record as it's not a real column
        unset($newRecord['rep_name']);

        // 1. Insert into log table (tbl_icerik_bilgileri_ai)
        $columns = array_keys($newRecord);
        $placeholders = array_map(function ($c) {
            return ":$c";
        }, $columns);

        $sql = "INSERT INTO tbl_icerik_bilgileri_ai (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($newRecord);

        // 2. Update master table (icerik_bilgileri)
        $updateMasterSql = "UPDATE icerik_bilgileri 
                            SET son_mesaj_yeri = 'personel_mesaji', 
                                son_islem_tarihi = ?, 
                                satis_temsilcisi = ?,
                                gorusme_sonucu_text = ?
                            WHERE telefon_numarasi = ?";
        $stmtMaster = $pdo->prepare($updateMasterSql);
        $stmtMaster->execute([
            time(),
            $_SESSION['user_id'],
            ($type === 'template' ? 'Şablon: ' . ($template['title'] ?? '') : 'Metin Mesajı'),
            $phone
        ]);
    } else {
        // Fallback for brand new numbers (should rarely happen in chat)
        $stmt = $pdo->prepare("INSERT INTO tbl_icerik_bilgileri_ai (telefon_numarasi, personel_mesaji, date, user_id, status, ip_adresi) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $phone,
            $message,
            time(),
            $_SESSION['user_id'],
            1,
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);

        // Also create/update master record
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM icerik_bilgileri WHERE telefon_numarasi = ?");
        $stmtCheck->execute([$phone]);
        if ($stmtCheck->fetchColumn() > 0) {
            $pdo->prepare("UPDATE icerik_bilgileri SET son_mesaj_yeri = 'personel_mesaji', son_islem_tarihi = ?, satis_temsilcisi = ? WHERE telefon_numarasi = ?")
                ->execute([time(), $_SESSION['user_id'], $phone]);
        } else {
            $pdo->prepare("INSERT INTO icerik_bilgileri (telefon_numarasi, son_mesaj_yeri, son_islem_tarihi, satis_temsilcisi) VALUES (?, 'personel_mesaji', ?, ?)")
                ->execute([$phone, time(), $_SESSION['user_id']]);
        }
    }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Gönderim hatası: ' . $e->getMessage()]);
}
