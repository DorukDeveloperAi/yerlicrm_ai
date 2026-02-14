<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$configPath = dirname(__DIR__) . '/config.php';
require_once $configPath;

echo "--- Checking for duplicate phone numbers in icerik_bilgileri ---\n";

try {
    $sql = "SELECT telefon_numarasi, COUNT(*) as c FROM icerik_bilgileri GROUP BY telefon_numarasi HAVING c > 1 LIMIT 10";
    $stmt = $pdo->query($sql);
    $dupes = $stmt->fetchAll();

    if ($dupes) {
        foreach ($dupes as $d) {
            echo "Phone: " . $d['telefon_numarasi'] . " (Count: " . $d['c'] . ")\n";
            // Show details for one
            $s = $pdo->prepare("SELECT id, kampanya, hastane, bolum FROM icerik_bilgileri WHERE telefon_numarasi = ? ORDER BY id DESC");
            $s->execute([$d['telefon_numarasi']]);
            $rows = $s->fetchAll();
            foreach ($rows as $r) {
                echo "  ID: {$r['id']}, Campaign: {$r['kampanya']}, Hospital: {$r['hastane']}, Dept: {$r['bolum']}\n";
            }
            echo "---\n";
        }
    } else {
        echo "No duplicate phone numbers found in icerik_bilgileri.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
