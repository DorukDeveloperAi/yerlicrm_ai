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
            if ($variablesMap && !empty($variablesMap) && $phone) {
                // Fetch latest customer data
                $cStmt = $pdo->prepare("
                SELECT t1.*, u.username as rep_name 
                FROM tbl_icerik_bilgileri_ai t1 
                LEFT JOIN users u ON t1.user_id = u.id 
                WHERE t1.telefon_numarasi = ? 
                ORDER BY t1.id DESC LIMIT 1
            ");
                $cStmt->execute([$phone]);
                $customer = $cStmt->fetch();

                if ($customer) {
                    foreach ($variablesMap as $placeholder => $field) {
                        $val = $customer[$field] ?? '';
                        $message = str_replace($placeholder, $val, $message);
                    }
                }
            }
            // ----------------------------------

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

    // Append message to table
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $stmt = $pdo->prepare("INSERT INTO tbl_icerik_bilgileri_ai (telefon_numarasi, personel_mesaji, date, user_id, status, ip_adresi) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $phone,
        $message,
        time(),
        $_SESSION['user_id'],
        1,
        $ip
    ]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Gönderim hatası: ' . $e->getMessage()]);
}
