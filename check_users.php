<?php
require_once 'config.php';
$stmt = $pdo->query("SELECT id, username FROM users LIMIT 20");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results);
?>