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
        $stmt = $pdo->prepare("SELECT content, gupshup_id, gupshup_account_id, source_number, image_url, variables FROM whatsapp_gupshup_templates WHERE id = ?");
        $stmt->execute([$templateId]);
        $template = $stmt->fetch();
        if ($template) {
            $message = $template['content'];
            $gupshup_template_id = $template['gupshup_id'];
            $source_number = $template['source_number'];
            $image_url = $template['image_url'];
            $variablesMap = $template['variables'] ? json_decode($template['variables'], true) : null;

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

    if (!empty($template['gupshup_account_id'])) {
        $accStmt = $pdo->prepare("SELECT * FROM gupshup_accounts WHERE id = ?");
        $accStmt->execute([$template['gupshup_account_id']]);
        $account = $accStmt->fetch();
        if ($account) {
            $api_key = $account['api_key'];
            $src_num = $account['phone_number'];
            $app_name = $account['app_name'];
        }
    }

    // Override with template-specific source number if provided manually
    if (!empty($source_number)) {
        $src_num = $source_number;
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

            $postBody['template'] = json_encode([
                'id' => $gupshup_template_id,
                'params' => $params
            ]);
        } else {
            $postBody['message'] = $message;
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

        // Remove ID to allow auto-increment
        unset($newRecord['id']);

        // Remove join data (rep_name) if it exists from the SELECT query
        unset($newRecord['rep_name']);

        // Update mandatory and message fields
        $newRecord['musteri_mesaji'] = ''; // Empty string instead of null to satisfy NOT NULL constraint
        $newRecord['personel_mesaji'] = $message;
        $newRecord['date'] = time();
        $newRecord['user_id'] = $_SESSION['user_id'];
        $newRecord['ip_adresi'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $newRecord['status'] = 1;

        // Build dynamic INSERT query
        $columns = array_keys($newRecord);
        $placeholders = array_map(function ($c) {
            return ":$c";
        }, $columns);

        $sql = "INSERT INTO tbl_icerik_bilgileri_ai (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($newRecord);
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
    }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Gönderim hatası: ' . $e->getMessage()]);
}
