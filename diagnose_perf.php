<?php
require_once 'config.php';
$count = $pdo->query('SELECT count(*) FROM tbl_icerik_bilgileri_ai')->fetchColumn();
echo "Total records: $count\n";

echo "\nIndexes:\n";
$indices = $pdo->query('SHOW INDEX FROM tbl_icerik_bilgileri_ai')->fetchAll(PDO::FETCH_ASSOC);
foreach ($indices as $idx) {
    echo "{$idx['Key_name']} - {$idx['Column_name']}\n";
}

echo "\nQuery Execution Time (Main Sidebar Dropdowns):\n";
$start = microtime(true);
$pdo->query("SELECT DISTINCT kampanya FROM tbl_icerik_bilgileri_ai WHERE kampanya IS NOT NULL AND kampanya != ''")->fetchAll();
$end = microtime(true);
echo "SELECT DISTINCT kampanya: " . ($end - $start) . "s\n";
?>