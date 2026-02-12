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
    if (!empty($msg['yapilan_degisiklik_notu'])) {
        $html .= '<div class="system-message"><span>' . htmlspecialchars($msg['yapilan_degisiklik_notu']) . '</span></div>';
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

    // Reverting to original behavior for timestamp (assuming date is int)
    $date = date('d.m.Y H:i', (int) $msg['date']);

    $html .= '<div class="msg ' . $type . '">';
    $html .= htmlspecialchars($content);
    $html .= '<span class="msg-meta">' . $date . '</span>';
    $html .= '</div>';
}

echo $html;
