<?php
require_once 'config.php';
$stmt = $pdo->query("SHOW TABLES LIKE 'tbl_randevular'");
echo $stmt->rowCount() > 0 ? "Exists" : "Not Exists";
?>