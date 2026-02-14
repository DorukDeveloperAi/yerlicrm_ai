<?php
require_once '../config.php';
try {
    $stmt = $pdo->query("DESCRIBE tbl_icerik_bilgileri_ai");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td></tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo $e->getMessage();
}
?>