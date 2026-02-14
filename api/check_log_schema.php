<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$configPath = dirname(__DIR__) . '/config.php';
require_once $configPath;

echo "--- Describing tbl_icerik_bilgileri_ai ---\n";

try {
    $stmt = $pdo->query("DESCRIBE tbl_icerik_bilgileri_ai");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        printf("%-25s %-20s %-10s\n", $col['Field'], $col['Type'], $col['Null']);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
