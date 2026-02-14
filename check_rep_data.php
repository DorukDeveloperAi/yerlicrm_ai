<?php
require_once 'config.php';
$stmt = $pdo->query("SELECT satis_temsilcisi, COUNT(*) as count FROM icerik_bilgileri WHERE satis_temsilcisi IS NOT NULL AND satis_temsilcisi != '' GROUP BY satis_temsilcisi LIMIT 10");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results);
?>