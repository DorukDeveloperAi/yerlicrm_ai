<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$configPath = dirname(__DIR__) . '/config.php';
require_once $configPath;

echo "--- Searching records with campaign 'DoSpa Thermal' ---\n";

try {
    $stmt = $pdo->prepare("SELECT id, telefon_numarasi, kampanya, hastane, bolum, doktor, satis_temsilcisi, basvuru_tarihi FROM icerik_bilgileri WHERE kampanya LIKE ? LIMIT 5");
    $stmt->execute(['%DoSpa Thermal%']);
    $rows = $stmt->fetchAll();

    if ($rows) {
        foreach ($rows as $row) {
            echo "ID: " . $row['id'] . "\n";
            echo "Phone: " . $row['telefon_numarasi'] . "\n";
            echo "Campaign: " . $row['kampanya'] . "\n";
            echo "Hospital: " . $row['hastane'] . "\n";
            echo "Dept: " . $row['bolum'] . "\n";
            echo "Doctor: " . $row['doktor'] . "\n";
            echo "Rep: " . $row['satis_temsilcisi'] . "\n";
            echo "Date: " . $row['basvuru_tarihi'] . "\n";
            echo "--------------------------\n";
        }
    } else {
        echo "No records found with that campaign.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
