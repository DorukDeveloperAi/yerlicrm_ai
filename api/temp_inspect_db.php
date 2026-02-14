<?php
require_once 'd:\antigravity\yerlicrm\src\config.php';
function inspectTable($pdo, $tableName)
{
    echo "--- $tableName ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $tableName");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "Field: {$row['Field']}, Type: {$row['Type']}\n";
        }
    } catch (Exception $e) {
        echo "Error inspecting $tableName: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

inspectTable($pdo, 'tbl_icerik_bilgileri_ai');
inspectTable($pdo, 'icerik_bilgileri');
?>