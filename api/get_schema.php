<?php
require_once '../config.php';
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM icerik_bilgileri");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'columns' => array_column($columns, 'Field')]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>