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
        $stmt = $pdo->prepare("SELECT content, gupshup_id, source_number, image_url, variables FROM whatsapp_gupshup_templates WHERE id = ?");
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
    $success = true; // Default to true for now to allow local testing
    $error_msg = '';

    if (defined('GUPSHUP_API_KEY') && !empty(GUPSHUP_API_KEY)) {
        // Implement GupShup API call logic here
        // For template messages, GupShup usually requires sending the template ID and potentially parameters.
        // Example logic (Conceptual):
        /*
        $ch = curl_init('https://api.gupshup.io/wa/api/v1/template/msg');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Cache-Control: no-cache',
            'Content-Type: application/x-www-form-urlencoded',
            'apikey: ' . GUPSHUP_API_KEY
        ]);

        $postBody = [
            'source' => GUPSHUP_SOURCE_NUMBER,
            'destination' => $phone,
            'template' => json_encode([
                'id' => $gupshup_template_id,
                'params' => [] // Add params if template is dynamic
            ]),
            'appname' => GUPSHUP_APP_NAME
        ];

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postBody));
        $response = curl_exec($ch);
        $resData = json_decode($response, true);
        if ($resData['status'] !== 'submitted') {
            $success = false;
            $error_msg = $resData['message'] ?? 'API hatası';
        }
        curl_close($ch);
        */
    }
    // ------------------------

    if (!$success) {
        echo json_encode(['success' => false, 'message' => 'Mesaj gönderilemedi: ' . $error_msg]);
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
        $newRecord['musteri_mesaji'] = null; // Explicitly empty customer message
        $newRecord['personel_mesaji'] = $message;
        $newRecord['date'] = time();
        $newRecord['user_id'] = $_SESSION['user_id'];
        $newRecord['ip_adresi'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $newRecord['status'] = 1;

        // Build dynamic INSERT query
        $columns = array_keys($newRecord);
        $placeholders = array_map(function ($c) {
            return ":$c"; }, $columns);

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
