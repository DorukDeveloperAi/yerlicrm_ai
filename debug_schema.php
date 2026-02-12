<?php
require_once 'config.php';
$output = "Sample Data from tbl_icerik_bilgileri_ai (Latest 3):\n";
$samples = $pdo->query("SELECT id, telefon_numarasi, date, tekrar_arama_tarihi, geldigi_yer, channel FROM tbl_icerik_bilgileri_ai ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
foreach ($samples as $s) {
    $output .= "ID: {$s['id']} | Phone: {$s['telefon_numarasi']} | Date: '{$s['date']}' | Callback: '{$s['tekrar_arama_tarihi']}' | Source: '{$s['geldigi_yer']}' | Channel: '{$s['channel']}'\n";
}
file_put_contents('sample_data.txt', $output);
echo "Dumped to sample_data.txt";
?>