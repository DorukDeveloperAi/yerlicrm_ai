<?php
require_once '../auth.php';
requireLogin();

try {
    $stmt = $pdo->query("SELECT * FROM gupshup_accounts ORDER BY name ASC");
    $accounts = $stmt->fetchAll();
    echo json_encode(['success' => true, 'accounts' => $accounts]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
