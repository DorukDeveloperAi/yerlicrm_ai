<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();
include 'check_users_data.php';
requireLogin();

// Fetch summary stats
$total_records = $pdo->query("SELECT count(DISTINCT telefon_numarasi) FROM tbl_icerik_bilgileri_ai")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel - YerliCRM</title>
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Force CSS reload -->
    <link rel="stylesheet" href="assets/css/main.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/phosphor-icons"></script>
</head>

<body class="bg-gray-50 text-gray-800 font-sans antialiased">
    <div class="dashboard-container">
        <header class="navbar">
            <div style="display: flex; align-items: center; gap: 2rem;">
                <h2>YerliCRM</h2>
                <a href="index.php" style="text-decoration: none; color: var(--primary); font-weight: 600;">Panel</a>
                <a href="chat.php" style="text-decoration: none; color: var(--text-muted); font-weight: 600;">Chat
                    Ekranı</a>
                <a href="users.php" style="text-decoration: none; color: var(--text-muted); font-weight: 600;">Kullanıcı
                    Yönetimi</a>
                </nav>
            </div>
            <div style="display: flex; align-items: center; gap: 1.5rem;">
                <span style="font-weight: 600; font-size: 0.9rem;">Merhaba,
                    <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="btn-logout">Çıkış Yap</a>
            </div>
        </header>

        <main>
            <div class="welcome-card">
                <h1 style="margin-bottom: 0.5rem;">CRM Paneline Hoş Geldiniz</h1>
                <p>Verilerinizi yönetmek ve analiz etmek için sol menüdeki araçları kullanabilirsiniz.</p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div
                    style="background: white; padding: 2rem; border-radius: 16px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                    <h3
                        style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 0.5rem;">
                        Toplam İçerik</h3>
                    <p style="font-size: 2rem; font-weight: 700; color: var(--primary);">
                        <?php echo number_format($total_records, 0, ',', '.'); ?>
                    </p>
                </div>

                <div
                    style="background: white; padding: 2rem; border-radius: 16px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                    <h3
                        style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; margin-bottom: 0.5rem;">
                        Durum</h3>
                    <p style="font-size: 2rem; font-weight: 700; color: #10b981;">Aktif</p>
                </div>
            </div>
        </main>
    </div>
</body>

</html>