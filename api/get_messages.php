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
        $changer = !empty($msg['kullanici_bilgileri_adi']) ? htmlspecialchars($msg['kullanici_bilgileri_adi']) : 'Sistem';
        $dateStr = date('H:i', (int) $msg['date']);

        $html .= '<div class="system-message"><span>';
        $html .= '<div class="msg-content">' . htmlspecialchars($msg['yapilan_degisiklik_notu']) . '</div>';
        $html .= '<div class="msg-footer" style="justify-content:center; gap:8px; opacity:0.7; font-size:0.65rem; margin-top:4px;">';
        $html .= '<span>' . $changer . '</span>';
        $html .= '<span>' . $dateStr . '</span>';
        $html .= '</div>';
        $html .= '</span></div>';
        continue;
    }

    $isInfo = (!empty($msg['personel_mesaji']) && !empty($msg['gorusme_sonucu_text']));
    $content = !empty($msg['musteri_mesaji']) ? $msg['musteri_mesaji'] : $msg['personel_mesaji'];

    if ($isInfo) {
        $senderName = !empty($msg['kullanici_bilgileri_adi']) ? $msg['kullanici_bilgileri_adi'] : 'Sistem';
        $html .= '<div class="msg-center">';
        $html .= '<div class="msg-content" style="font-weight:600;">' . nl2br(htmlspecialchars($content)) . '</div>';
        $html .= '<div class="msg-footer" style="justify-content:center; gap:8px; opacity:0.7; font-size:0.65rem; margin-top:4px;">';
        $html .= '<span>' . htmlspecialchars($senderName) . '</span>';
        $html .= '<span>' . date('H:i', (int) $msg['date']) . '</span>';
        $html .= '</div>';
        $html .= '</div>';
        continue;
    }

    $type = (empty($msg['personel_mesaji']) && !empty($msg['musteri_mesaji'])) ? 'msg-in' : 'msg-out';

    if (empty($content))
        continue;

    // Determine sender name
    $senderName = '';
    if ($type === 'msg-out') {
        $senderName = !empty($msg['kullanici_bilgileri_adi']) ? $msg['kullanici_bilgileri_adi'] : (!empty($msg['satis_temsilcisi']) ? $msg['satis_temsilcisi'] : 'Sistem');
    } elseif ($type === 'msg-in') {
        $senderName = 'Müşteri';
    }

    $html .= '<div class="msg ' . $type . '">';
    $html .= '<div class="msg-content">' . nl2br(htmlspecialchars($content)) . '</div>';

    $html .= '<div class="msg-footer">';
    if ($type === 'msg-out') {
        $html .= '<span class="msg-sender-small">' . htmlspecialchars($senderName) . '</span>';
    }
    $html .= '<span class="msg-meta">' . date('H:i', (int) $msg['date']) . '</span>';
    $html .= '</div>';

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