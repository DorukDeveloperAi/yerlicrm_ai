<?php
require '/var/www/html/config.php';

function inspectTable($pdo, $tableName)
{
    echo "--- $tableName ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $tableName");
        while ($row = $stmt->fetch()) {
            echo "Field: {$row['Field']}, Type: {$row['Type']}, Null: {$row['Null']}, Key: {$row['Key']}, Default: {$row['Default']}, Extra: {$row['Extra']}\n";
        }
    } catch (Exception $e) {
        echo "Error inspecting $tableName: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

inspectTable($pdo, 'tbl_icerik_bilgileri_ai_yedek2');
inspectTable($pdo, 'icerik_bilgileri');
