<?php
require_once '../config.php';
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Tablolar:</h3><ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";

    $targetTable = 'tbl_icerik_bilgileri_ai'; // Default to check
    if (in_array('tbl_icerik_bilgileri_ai_icerik', $tables)) {
        $targetTable = 'tbl_icerik_bilgileri_ai_icerik';
    }

    echo "<h3>$targetTable Kolon Sırası:</h3>";
    $stmt = $pdo->query("DESCRIBE $targetTable");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo $e->getMessage();
}
?>