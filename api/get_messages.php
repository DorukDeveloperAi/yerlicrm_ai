<?php
require_once '../auth.php';
requireLogin();

$phone = $_GET['phone'] ?? '';

if (!$phone) {
    echo json_encode(['success' => false, 'message' => 'Phone number missing']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM tbl_icerik_bilgileri_ai WHERE telefon_numarasi = ? ORDER BY date ASC");
$stmt->execute([$phone]);
$messages = $stmt->fetchAll();

$html = '';
foreach ($messages as $msg) {
    // Check for system message (change log)
    // Check for system message (change log)
    if (!empty($msg['yapilan_degisiklik_notu'])) {
        $changer = !empty($msg['kullanici_bilgileri_adi']) ? htmlspecialchars($msg['kullanici_bilgileri_adi']) : '';
        $dateStr = date('d.m.Y H:i', (int) $msg['date']);

        $html .= '<div class="system-message"><span>';
        if ($changer) {
            $html .= '<div class="msg-sender">' . $changer . '</div>';
        }
        $html .= '<div class="msg-content">' . htmlspecialchars($msg['yapilan_degisiklik_notu']) . '</div>';
        $html .= '<small style="opacity:0.7; font-size:0.65rem; display:block; margin-top:4px;">' . $dateStr . '</small></span></div>';
        continue;
    }

    $isInfo = (!empty($msg['personel_mesaji']) && !empty($msg['gorusme_sonucu_text']));

    if ($isInfo) {
        $type = 'msg-center';
    } else {
        $type = (empty($msg['personel_mesaji']) && !empty($msg['musteri_mesaji'])) ? 'msg-in' : 'msg-out';
    }

    $content = !empty($msg['musteri_mesaji']) ? $msg['musteri_mesaji'] : $msg['personel_mesaji'];

    // Skip if no content (e.g., empty rows from other logic), though system msg handles one type of empty row
    if (empty($content) && empty($msg['gorusme_sonucu_text']))
        continue;

    // Determine sender name
    $senderName = '';
    if ($type === 'msg-out') {
        $senderName = !empty($msg['kullanici_bilgileri_adi']) ? $msg['kullanici_bilgileri_adi'] : (!empty($msg['satis_temsilcisi']) ? $msg['satis_temsilcisi'] : 'Sistem');
    } elseif ($type === 'msg-in') {
        $senderName = 'Müşteri';
    } elseif ($type === 'msg-center') {
        $senderName = !empty($msg['kullanici_bilgileri_adi']) ? $msg['kullanici_bilgileri_adi'] : 'Sistem';
    }

    $html .= '<div class="msg ' . $type . '">';
    if ($senderName) {
        $html .= '<div class="msg-sender">' . htmlspecialchars($senderName) . '</div>';
    }
    $html .= '<div class="msg-content">' . nl2br(htmlspecialchars($content)) . '</div>';
    $html .= '<div class="msg-meta">' . date('d.m.Y H:i', (int) $msg['date']) . '</div>';
    $html .= '</div>';
}

// Find last customer message timestamp
$lastCustomerDate = 0;
foreach (array_reverse($messages) as $msg) {
    if (!empty($msg['musteri_mesaji'])) {
        $lastCustomerDate = (int) $msg['date'];
        break;
    }
}

// Return as JSON for chat.php
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'html' => $html,
    'last_customer_date' => $lastCustomerDate,
    'now' => time()
]);
?>