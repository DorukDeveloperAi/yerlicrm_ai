<?php
require_once 'config.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS whatsapp_gupshup_templates (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        title VARCHAR(255) NOT NULL, 
        content TEXT NOT NULL, 
        gupshup_id VARCHAR(255), 
        status INT DEFAULT 1, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Add some sample templates
    $pdo->exec("INSERT INTO whatsapp_gupshup_templates (title, content) VALUES 
        ('Selamlama', 'Merhaba, size nasıl yardımcı olabilirim?'),
        ('Bilgi Talebi', 'İstediğiniz bilgileri hazırlıyoruz, en kısa sürede döneceğiz.')
    ON DUPLICATE KEY UPDATE title=title;");

    echo "Table created and seeded successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>