<?php
require_once 'auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $pageTitle ?? 'YerliCRM'; ?>
    </title>
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/modern_ui.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/phosphor-icons"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        :root {
            --primary: #4F46E5;
            --primary-light: #EEF2FF;
            --sidebar-bg: #0F172A;
            --sidebar-hover: #1E293B;
            --sidebar-active: #334155;
            --text-muted: #64748B;
            --bg-main: #F8FAFC;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main);
        }

        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            transition: all 0.3s;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            color: #F8FAFC;
        }

        .sidebar-brand {
            padding: 2rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .sidebar-brand i {
            color: var(--primary);
            font-size: 1.75rem;
        }

        .sidebar-nav {
            flex-grow: 1;
            padding: 0 0.75rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            border-radius: 0.75rem;
            text-decoration: none;
            color: #94A3B8;
            font-weight: 500;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
        }

        .nav-item:hover {
            background-color: var(--sidebar-hover);
            color: white;
        }

        .nav-item.active {
            background-color: var(--primary);
            color: white;
        }

        .nav-item i {
            font-size: 1.25rem;
        }

        .main-content {
            margin-left: 260px;
            padding: 2rem;
            min-height: 100vh;
        }

        .top-nav {
            margin-left: 260px;
            padding: 1rem 2rem;
            background: white;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 1.5rem;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: #F1F5F9;
            border-radius: 100px;
            font-weight: 600;
            font-size: 0.875rem;
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="ph-bold ph-rocket-launch"></i>
            <span>YerliCRM</span>
        </div>

        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item <?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                <i class="ph ph-house"></i>
                <span>Panel</span>
            </a>
            <a href="users.php" class="nav-item <?php echo $activePage == 'users' ? 'active' : ''; ?>">
                <i class="ph ph-users"></i>
                <span>Kullanıcı Yönetimi</span>
            </a>
            <a href="chat.php" class="nav-item <?php echo $activePage == 'chat' ? 'active' : ''; ?>">
                <i class="ph ph-chats"></i>
                <span>Chat Ekranı</span>
            </a>
            <a href="whatsapp_templates.php"
                class="nav-item <?php echo $activePage == 'whatsapp_templates' ? 'active' : ''; ?>">
                <i class="ph ph-whatsapp-logo"></i>
                <span>WhatsApp Şablonları</span>
            </a>
        </nav>

        <div class="p-4 mt-auto">
            <a href="logout.php" class="nav-item text-red-400 hover:text-red-300">
                <i class="ph ph-sign-out"></i>
                <span>Çıkış Yap</span>
            </a>
        </div>
    </aside>

    <header class="top-nav">
        <div class="user-pill">
            <i class="ph-fill ph-user-circle text-gray-400 text-xl"></i>
            <span>
                <?php echo htmlspecialchars($_SESSION['username'] ?? 'Kullanıcı'); ?>
            </span>
        </div>
    </header>

    <main class="main-content">