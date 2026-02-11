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
    $isInfo = (!empty($msg['personel_mesaji']) && !empty($msg['gorusme_sonucu_text']));

    if ($isInfo) {
        $type = 'msg-center';
    } else {
        $type = (empty($msg['personel_mesaji']) && !empty($msg['musteri_mesaji'])) ? 'msg-in' : 'msg-out';
    }

    $content = !empty($msg['musteri_mesaji']) ? $msg['musteri_mesaji'] : $msg['personel_mesaji'];
    $date = date('d.m.Y H:i', $msg['date']);

    $html .= '<div class="msg ' . $type . '">';
    $html .= htmlspecialchars($content);
    $html .= '<span class="msg-meta">' . $date . '</span>';
    $html .= '</div>';
}

echo $html;
