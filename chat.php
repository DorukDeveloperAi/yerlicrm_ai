<?php
require_once 'auth.php';
requireLogin();

// Pagination Settings (Basics only, specific lists handled via AJAX)
$limit = 50;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = $page < 1 ? 1 : $page;

// Basic Statuses for sidebar (These are small/fast, can stay or move, keeping for now as they are core to sidebar UI)
$statuses = $pdo->query("SELECT * FROM tbl_ayarlar_gorusme_sonucu_bilgileri ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Ekranı - YerliCRM</title>
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <script src="https://unpkg.com/phosphor-icons"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --bg-main: #f8fafc;
            --sidebar-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }

        body,
        html {
            margin: 0;
            padding: 0;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main);
        }

        .chat-page {
            display: flex;
            height: 100vh;
            width: 100vw;
            background: white;
            box-shadow: none;
            border-radius: 0;
            margin: 0;
        }



        /* Drawer Sidebar */
        .menu-drawer {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100%;
            background: white;
            z-index: 1001;
            box-shadow: 10px 0 30px rgba(0, 0, 0, 0.05);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            padding: 2rem 1.5rem;
        }

        .menu-drawer.active {
            left: 0;
        }

        .drawer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(4px);
            z-index: 100;
            display: none;
        }

        .drawer-overlay.active {
            display: block;
        }

        .drawer-header {
            margin-bottom: 2.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .drawer-logo {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary), #818cf8);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .chat-main {
            display: flex;
            flex-direction: column;
            flex: 1;
            background: white;
            height: 100vh;
            overflow: hidden;
            border-left: 1px solid var(--border);
            border-right: 1px solid var(--border);
        }

        .chat-sidebar {
            width: 380px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            background: white;
            height: 100vh;
            border-right: 1px solid var(--border);
        }

        .chat-box {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .interaction-area {
            background: white;
            border-top: 1px solid var(--border);
            padding: 1rem 1.5rem;
        }

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid var(--border);
            background: #f8fafc;
        }

        .sidebar-search-footer {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .sidebar-search-footer input {
            flex: 1;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .sidebar-search-footer input:focus {
            border-color: var(--primary);
        }

        .btn-search-bul {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-search-bul:hover {
            opacity: 0.9;
        }

        .drawer-nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .drawer-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 0.75rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .drawer-link:hover,
        .drawer-link.active {
            background: #f1f5f9;
            color: var(--primary);
        }

        .drawer-link i {
            font-size: 1.25rem;
        }

        .drawer-footer {
            margin-top: auto;
            border-top: 1px solid var(--border);
            padding-top: 1.5rem;
        }

        .spinner-container {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.7);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .spinner {
            width: 30px;
            height: 30px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        100% {
            transform: rotate(360deg);
        }
        }

        /* User Profile Widget - Icon Only */
        .user-profile-widget {
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            z-index: 1002;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
            background: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            color: var(--text-muted);
        }

        .user-profile-widget:hover {
            color: var(--primary);
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .user-profile-icon {
            font-size: 1.5rem;
        }
    </style>
</head>

<body>




    <!-- Drawer Menu -->
    <div class="drawer-overlay" id="drawerOverlay" onclick="toggleDrawer()"></div>
    <div class="menu-drawer" id="menuDrawer">
        <div class="drawer-header">
            <div class="drawer-logo">
                <i class="ph-bold ph-lightning"></i>
            </div>
            <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0;">YerliCRM</h2>
        </div>

        <nav class="drawer-nav">
            <a href="index.php" class="drawer-link">
                <i class="ph ph-house"></i> Dashboard
            </a>
            <a href="chat.php" class="drawer-link active">
                <i class="ph ph-chat-circle"></i> Canlı Destek
            </a>
            <a href="users.php" class="drawer-link">
                <i class="ph ph-users"></i> Personeller
            </a>
            <a href="whatsapp_templates.php" class="drawer-link">
                <i class="ph ph-whatsapp-logo"></i> WhatsApp Şablonları
            </a>
            <a href="#" class="drawer-link">
                <i class="ph ph-chart-line"></i> Raporlar
            </a>
            <div style="margin: 1rem 0; height: 1px; background: var(--border);"></div>
            <a href="logout.php" class="drawer-link" style="color: #ef4444;">
                <i class="ph ph-sign-out"></i> Çıkış Yap
            </a>
        </nav>

        <div class="drawer-footer">
            <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0;">&copy; 2026 YerliCRM v2.0</p>
        </div>
    </div>
    <div class="chat-page">
        <!-- Sol: Müşteri Listesi -->
        <aside class="chat-sidebar">

            <div class="sidebar-controls">
                <div class="sidebar-filter-grid">
                    <select class="status-select" id="personnel-filter" onchange="filterByPersonnel(this.value)">
                        <option value="all">Personel</option>
                    </select>

                    <select class="status-select" id="campaign-filter" onchange="filterByCampaign(this.value)">
                        <option value="all">Kampanya</option>
                    </select>

                    <select class="status-select" id="status-filter" onchange="filterByStatus(this.value)">
                        <option value="all">Sonuç</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?php echo htmlspecialchars($s['baslik']); ?>" <?php echo (isset($status_filter) && $status_filter === $s['baslik']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['baslik']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button class="btn-new-record" onclick="openNewRecordModal()" title="Yeni Kayıt Ekle">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                        </svg>
                    </button>
                </div>

            </div>

            <div class="sidebar-tabs">
                <button class="tab-btn active" id="tab-empty" onclick="setMainFilter('empty')">Yeni</button>
                <button class="tab-btn" id="tab-all" onclick="setMainFilter('all')">Tümü</button>
            </div>

            <div class="spinner-container" id="list-spinner" style="display: none;">
                <div class="spinner"></div>
            </div>
            <div class="contact-list" id="contact-list-container">
            </div>

            <div class="sidebar-footer">
                <div class="sidebar-search-footer">
                    <input type="text" id="sidebar-search" placeholder="Müşteri Ara (İsim / Tel)"
                        onkeyup="if(event.key==='Enter') performSearch()">
                    <button class="btn-search-bul" onclick="performSearch()">Bul</button>
                </div>
                <div class="contact-pagination" id="pagination-container">
                </div>
                <div id="record-info"
                    style="font-size: 0.8rem; color: #64748b; margin-top: 0.5rem; text-align: center;">
                    Yükleniyor...
                </div>
                <div id="pagination-info"
                    style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.2rem; text-align: center;">
                    Sayfa ... / ...
                </div>
            </div>
        </aside>

        <!-- Orta: Yazışmalar -->
        <main class="chat-main" style="position: relative;">
            <div class="spinner-container" id="convo-spinner" style="display: none;">
                <div class="spinner"></div>
                <p style="margin-top: 1rem; color: var(--primary); font-weight: 500;">Yükleniyor...</p>
            </div>
            <header class="sidebar-header"
                style="background: white; display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #f3f4f6;">
                <h3 id="chat-title" style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0;">Müşteri
                    Seçin</h3>
                <div id="chat-meta" style="text-align: right; display: none;">
                    <div id="meta-name" style="font-size: 0.9rem; font-weight: 600; color: #334155;"></div>
                    <div id="meta-campaign" style="font-size: 0.75rem; color: #64748b;"></div>
                </div>
            </header>

            <div class="chat-messages" id="chat-box">
                <div style="text-align: center; margin-top: 5rem; color: var(--text-muted);">
                    Yazışmaları görüntülemek için sol taraftan bir müşteri seçin.
                </div>
            </div>

            <div id="templates-container" style="display: none; padding: 0 1rem;">
                <!-- Template buttons will be injected here -->
            </div>
            <div class="chat-input-area" id="input-area" style="display: none;">
                <textarea id="message-text" placeholder="WhatsApp mesajınızı yazın..."></textarea>
                <div class="input-actions flex items-center gap-2">
                    <button type="button" class="btn-templates-toggle" onclick="toggleTemplates()" title="Şablonlar">
                        <i class="ph-bold ph-envelope-simple"></i>
                    </button>
                    <button class="btn-send" onclick="sendMessage()" title="Mesaj Gönder">
                        <i class="ph ph-paper-plane-right"></i>
                    </button>
                </div>
            </div>

            <div class="interaction-container" id="interaction-area" style="display: none;">
                <form id="interaction-form" enctype="multipart/form-data">
                    <input type="hidden" name="phone" id="form-phone">
                    <div class="interaction-grid-two-rows">
                        <!-- Left Pillar: Görüşme Notu (Row span 2) -->
                        <div class="field-group note-field-group">
                            <label>Görüşme Notu</label>
                            <textarea name="note" id="form-note" placeholder="Not..." rows="5"></textarea>
                        </div>

                        <!-- Mid Pillar: Görüşme Sonucu -->
                        <div class="field-group">
                            <label>Görüşme Sonucu</label>
                            <select name="status_id" onchange="toggleComplaintFields(this.value)">
                                <option value="">Sonuç Seçiniz</option>
                                <?php foreach ($statuses as $s): ?>
                                    <option value="<?php echo $s['baslik']; ?>">
                                        <?php echo htmlspecialchars($s['baslik']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Right Pillar: Lead Puanlama -->
                        <div class="field-group">
                            <label>Lead Puanlama</label>
                            <select name="lead_score">
                                <option value="">Puan</option>
                                <option value="1">1 Yıldız</option>
                                <option value="2">2 Yıldız</option>
                                <option value="3">3 Yıldız</option>
                                <option value="4">4 Yıldız</option>
                                <option value="5">5 Yıldız</option>
                            </select>
                        </div>

                        <!-- Mid Pillar: Tekrar Arama (Row 2) -->
                        <div class="field-group">
                            <label>Tekrar Arama</label>
                            <input type="date" name="callback_date">
                        </div>
                    </div>

                    <!-- Complaint Section (Separate below grid) -->
                    <div id="complaint-section" class="complaint-grid hidden">
                        <div class="field-group complaint-field">
                            <label>Şikayet Şube</label>
                            <select name="complaint_hospital" id="comp-hosp">
                                <option value="">Şube Seçiniz</option>
                                <?php foreach ($hospitals as $h): ?>
                                    <option value="<?php echo $h['baslik']; ?>">
                                        <?php echo htmlspecialchars($h['baslik']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field-group complaint-field">
                            <label>Şikayet Bölüm</label>
                            <select name="complaint_dept">
                                <option value="">Bölüm Seçiniz</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?php echo $d['baslik']; ?>">
                                        <?php echo htmlspecialchars($d['baslik']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field-group complaint-field">
                            <label>Şikayet Doktor</label>
                            <select name="complaint_doctor">
                                <option value="">Doktor Seçiniz</option>
                                <?php foreach ($doctors as $doc): ?>
                                    <option value="<?php echo $doc['baslik']; ?>">
                                        <?php echo htmlspecialchars($doc['baslik']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field-group complaint-field">
                            <label>Şikayet Konusu</label>
                            <select name="complaint_topic">
                                <option value="">Konu Seçiniz</option>
                                <?php foreach ($complaint_topics as $ct): ?>
                                    <option value="<?php echo $ct['baslik']; ?>">
                                        <?php echo htmlspecialchars($ct['baslik']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field-group complaint-field" style="grid-column: span 2;">
                            <label>Şikayet Detayı</label>
                            <input type="text" name="complaint_detail" placeholder="Şikayet detayı...">
                        </div>

                        <div class="field-group complaint-field">
                            <label>Şikayet Görseli</label>
                            <input type="file" name="complaint_image">
                        </div>
                    </div>

                    <div class="interaction-footer">
                        <button type="button" class="btn-inspector" onclick="saveInteraction('inspector')">Denetçi
                            Mesajı</button>
                        <button type="button" class="btn-update" onclick="saveInteraction('update')">Bilgileri
                            Düzenle</button>
                    </div>
                </form>
            </div>
        </main>

        <!-- Sağ: Detaylar -->
        <aside class="chat-details">
            <div
                style="padding: 0.25rem; display: flex; justify-content: flex-end; align-items: center; border-bottom: 1px solid #f3f4f6; background: white;">
                <div class="user-profile-widget" onclick="toggleDrawer()" title="Menü ve Ayarlar"
                    style="position: static; box-shadow: none; width: 32px; height: 32px; border: 1px solid #e2e8f0; margin: 0;">
                    <i class="ph-fill ph-gear" style="font-size: 1.2rem;"></i>
                </div>
            </div>
            <div id="detail-box" style="flex: 1; overflow-y: auto;">
                <div style="text-align: center; color: var(--text-muted); margin-top: 5rem;">
                    Detaylar burada görünecek.
                </div>
            </div>
        </aside>
    </div>

    <!-- Yeni Kayıt Modal -->
    <div id="newRecordModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Yeni Kayıt Ekle</h3>
                <button class="modal-close" onclick="closeNewRecordModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert-preparing">
                    <svg viewBox="0 0 24 24" width="32" height="32" fill="currentColor">
                        <path
                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
                    </svg>
                    <p>Hazırlanıyor...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editDetailModal" class="modal-overlay" style="display: none;">
        <div class="modal-content" style="width: 350px;">
            <div class="modal-header">
                <h3 id="editModalTitle">Düzenle</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body" style="padding: 1.5rem; text-align: left;">
                <input type="hidden" id="editPhone">
                <input type="hidden" id="editField">

                <div class="detail-form-group">
                    <label class="detail-label-sm" id="editLabel">Değer</label>
                    <div id="editInputContainer">
                        <!-- Dynamic Input -->
                    </div>
                </div>

                <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem;">
                    <button onclick="closeEditModal()" class="btn-change"
                        style="flex: 1; background: #f1f5f9; color: #64748b; justify-content: center;">İptal</button>
                    <button onclick="saveDetailChange()" class="btn-change"
                        style="flex: 1; justify-content: center;">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global Data for Edit Modal (Fetched via AJAX)
        let detailData = {
            campaigns: [],
            requests: [],
            hospitals: [],
            departments: [],
            doctors: [],
            personnel: []
        };

        // Load Modal Options Content
        async function loadModalOptions() {
            try {
                const response = await fetch('api/get_modal_options.php');
                const result = await response.json();
                if (result.success) {
                    detailData = result.data;
                    populateSidebarFilters();
                }
            } catch (error) {
                console.error('Error loading options:', error);
            }
        }

        function populateSidebarFilters() {
            const pSelect = document.getElementById('personnel-filter');
            const cSelect = document.getElementById('campaign-filter');

            detailData.personnel.forEach(p => {
                const opt = new Option(p.username, p.id);
                pSelect.add(opt);
            });

            detailData.campaigns.forEach(c => {
                const opt = new Option(c, c);
                cSelect.add(opt);
            });
        }

        function openEditModal(field, currentValue, label, options = null) {
            document.getElementById('editDetailModal').style.display = 'flex';
            document.getElementById('editModalTitle').innerText = label + ' Düzenle';
            document.getElementById('editLabel').innerText = label;
            document.getElementById('editField').value = field;
            // Phone is retrieved from global currentPhone in chat.php
            document.getElementById('editPhone').value = currentPhone;

            const container = document.getElementById('editInputContainer');
            container.innerHTML = '';

            let inputHtml = '';

            // Helper to build select
            const buildSelect = (opts, isRep = false) => {
                let html = '<select id="editValue" class="form-select">';
                html += '<option value="">Seçiniz</option>';
                opts.forEach(opt => {
                    const val = isRep ? opt.id : opt;
                    const text = isRep ? opt.username : opt;
                    const selected = String(val) === String(currentValue) ? 'selected' : '';
                    html += `<option value="${val}" ${selected}>${text}</option>`;
                });
                // Keep current if not in list
                if (currentValue && !opts.some(o => (isRep ? o.id : o) == currentValue)) {
                    // For reps, we don't have the username if not in list, so just show ID or skip
                    if (!isRep) html += `<option value="${currentValue}" selected>${currentValue}</option>`;
                }
                html += '</select>';
                return html;
            };

            if (field === 'kampanya') inputHtml = buildSelect(detailData.campaigns);
            else if (field === 'talep_icerik') inputHtml = buildSelect(detailData.requests);
            else if (field === 'hastane') inputHtml = buildSelect(detailData.hospitals);
            else if (field === 'bolum') inputHtml = buildSelect(detailData.departments);
            else if (field === 'doktor') inputHtml = buildSelect(detailData.doctors);
            else if (field === 'user_id' && options) {
                inputHtml = buildSelect(options, true);
            }
            else if (field === 'dogum_haftasi') {
                inputHtml = `<div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="number" id="editValue" class="form-input" value="${currentValue}" placeholder="Örn: 11" style="flex: 1;">
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Hafta</span>
                </div>`;
            }

            container.innerHTML = inputHtml;
        }

        function closeEditModal() {
            document.getElementById('editDetailModal').style.display = 'none';
        }

        function saveDetailChange() {
            const phone = document.getElementById('editPhone').value;
            const field = document.getElementById('editField').value;
            const value = document.getElementById('editValue').value;

            const formData = new FormData();
            formData.append('phone', phone);
            formData.append('field', field);
            formData.append('value', value);

            fetch('api/update_detail.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Başarıyla güncellendi.');
                        closeEditModal();
                        // Reload details
                        loadConversation(phone, document.querySelector('.contact-item.active'));
                    } else {
                        alert('Hata: ' + data.message);
                    }
                })
                .catch(err => alert('Bir hata oluştu.'));
        }

        let currentPhone = '';

        function toggleComplaintFields(val) {
            const section = document.getElementById('complaint-section');
            const showList = ['Teşekkür', 'Şikayet', 'Öneri'];
            // Check if selected value contains any of the showList keywords
            const shouldShow = showList.some(keyword => val.includes(keyword));

            if (shouldShow) {
                section.classList.remove('hidden');
            } else {
                section.classList.add('hidden');
            }
        }

        function loadConversation(phone, name, campaign, element) {
            currentPhone = phone;
            document.querySelectorAll('.contact-item').forEach(el => el.classList.remove('active'));
            if (element) element.classList.add('active');

            const spinner = document.getElementById('convo-spinner');
            if (spinner) spinner.style.display = 'flex';

            document.getElementById('chat-title').innerText = phone;
            document.getElementById('chat-meta').style.display = 'block';
            document.getElementById('meta-name').innerText = name || 'İsimsiz';
            document.getElementById('meta-campaign').innerText = campaign || '';

            document.getElementById('input-area').style.display = 'flex';
            document.getElementById('interaction-area').style.display = 'block';
            loadTemplates();
            document.getElementById('templates-container').style.display = 'none'; // Hide by default
            scrollToBottom();
            document.getElementById('form-phone').value = phone;

            // Reset form and hide complaint fields
            const form = document.getElementById('interaction-form');
            form.reset();
            toggleComplaintFields('');

            const fetchMessages = fetch('api/get_messages.php?phone=' + phone)
                .then(response => response.json())
                .then(data => {
                    const box = document.getElementById('chat-box');
                    box.innerHTML = data.html;
                    box.scrollTop = box.scrollHeight;

                    // 24-Hour Rule Implementation
                    const messageInput = document.getElementById('message-text');
                    const templateContainer = document.getElementById('templates-container');
                    const sendBtn = document.querySelector('.btn-send');
                    const now = data.now;
                    const lastCustomerDate = data.last_customer_date;
                    const hoursPassed = (now - lastCustomerDate) / 3600;

                    const btnSend = document.querySelector('.btn-send');
                    const btnTemplateToggle = document.querySelector('.btn-templates-toggle');
                    const within24h = !(lastCustomerDate > 0 && hoursPassed > 24);

                    if (!within24h) {
                        messageInput.disabled = true;
                        messageInput.placeholder = "24 saat kuralı: Sadece şablon mesajı gönderilebilir";
                        btnSend.style.display = 'none';
                        btnTemplateToggle.style.display = 'flex';
                        btnTemplateToggle.classList.add('pulse-primary');
                    } else {
                        messageInput.disabled = false;
                        messageInput.placeholder = "WhatsApp mesajınızı yazın...";
                        btnSend.style.display = 'flex';
                        btnTemplateToggle.style.display = 'none';
                        btnTemplateToggle.classList.remove('pulse-primary');
                        // Ensure templates container is hidden when switching back to normal chat
                        document.getElementById('templates-container').style.display = 'none';
                    }
                });


            const fetchDetails = fetch('api/get_details.php?phone=' + phone)
                .then(response => response.text())
                .then(html => {
                    const detailBox = document.getElementById('detail-box');
                    detailBox.innerHTML = html;

                    // Auto-open complaint fields if campaign matches
                    const campaignVal = detailBox.querySelector('#current-campaign-val')?.innerText.trim();
                    if (campaignVal === 'Teşekkür, Şikayet, Öneri') {
                        toggleComplaintFields('Teşekkür, Şikayet, Öneri');
                    }
                });

            Promise.all([fetchMessages, fetchDetails]).finally(() => {
                if (spinner) spinner.style.display = 'none';
            });
        }

        function sendMessage() {
            const textInput = document.getElementById('message-text');
            const text = textInput.value.trim();
            if (!text || !currentPhone) return;

            const formData = new FormData();
            formData.append('phone', currentPhone);
            formData.append('message', text);

            fetch('api/send_message.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        textInput.value = '';
                        const activeItem = document.querySelector('.contact-item.active');
                        loadConversation(currentPhone, activeItem);
                    } else {
                        alert('Hata: ' + data.message);
                    }
                });
        }

        function saveInteraction(type) {
            const form = document.getElementById('interaction-form');
            const formData = new FormData(form);
            formData.append('type', type);

            // Fetch campaign from detail pane
            const campaignSpan = document.getElementById('current-campaign-val');
            if (campaignSpan) {
                const campaignVal = campaignSpan.innerText.trim();
                if (campaignVal !== '-') {
                    formData.append('kampanya', campaignVal);
                }
            }

            fetch('api/save_interaction.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Bilgiler başarıyla kaydedildi.');
                    } else {
                        alert('Hata: ' + data.message);
                    }
                });
        }

        document.getElementById('message-text').addEventListener('keypress', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        let currentPage = 1;
        let currentStatus = 'empty';
        let currentPersonnel = 'all';
        let currentCampaign = 'all';
        let currentSearch = '';

        let searchTimeout;
        function performSearch() {
            const val = document.getElementById('sidebar-search').value;
            currentSearch = val;
            loadCustomers(1, currentStatus, currentPersonnel, currentCampaign, val);
        }

        function loadCustomers(page = 1, status = currentStatus, personnel = currentPersonnel, campaign = currentCampaign, search = currentSearch) {
            currentPage = page;
            currentStatus = status;
            currentPersonnel = personnel;
            currentCampaign = campaign;
            currentSearch = search;
            const container = document.getElementById('contact-list-container');
            const spinner = document.getElementById('list-spinner');
            const pagination = document.getElementById('pagination-container');
            const info = document.getElementById('pagination-info');

            if (spinner) spinner.style.display = 'flex';

            fetch(`api/get_customers.php?page=${page}&status=${encodeURIComponent(status)}&personnel=${personnel}&campaign=${encodeURIComponent(campaign)}&search=${encodeURIComponent(search)}`)
                .then(r => r.json())
                .then(data => {
                    if (spinner) spinner.style.display = 'none';
                    if (data.success) {
                        container.innerHTML = data.html;
                        pagination.innerHTML = data.pagination_html;
                        info.innerText = `Sayfa ${data.page} / ${data.total_pages}`;
                        document.getElementById('record-info').innerText = `${data.start}...${data.end} arası kayıtlar gösteriliyor (Toplam: ${data.total_records})`;
                        applyProfileColors();
                    }
                });
        }

        function filterByStatus(status) {
            // Remove active state from main tabs if a specific result status is selected
            if (status !== 'all' && status !== 'empty') {
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            }
            loadCustomers(1, status, currentPersonnel, currentCampaign, currentSearch);
        }

        function setMainFilter(status) {
            // Update Tab UI
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            if (status === 'all') document.getElementById('tab-all').classList.add('active');
            if (status === 'empty') document.getElementById('tab-empty').classList.add('active');

            // Reset search when switching tabs if needed, or keep it? User said "tüm müşteri listesinde arayıp getirecek"
            // Let's keep the search term but apply the filter
            loadCustomers(1, status, currentPersonnel, currentCampaign, currentSearch);
            document.getElementById('status-filter').value = 'all';

            filterByStatus(status);
        }

        function filterByPersonnel(personnel) {
            loadCustomers(1, currentStatus, personnel, currentCampaign, currentSearch);
        }

        function filterByCampaign(campaign) {
            loadCustomers(1, currentStatus, currentPersonnel, campaign, currentSearch);
        }

        function switchDetailTab(tabId, btn) {
            // Hide all panes
            document.querySelectorAll('.detail-tab-pane').forEach(pane => pane.classList.remove('active'));
            // Remove active class from all buttons
            document.querySelectorAll('.detail-tab-btn').forEach(b => b.classList.remove('active'));

            // Show target pane
            const target = document.getElementById('detail-pane-' + tabId);
            if (target) target.classList.add('active');

            // Add active class to clicked button
            if (btn) btn.classList.add('active');
        }

        function copyLeadLink(elementId) {
            const copyText = document.getElementById(elementId);
            copyText.select();
            copyText.setSelectionRange(0, 99999); /* For mobile devices */
            navigator.clipboard.writeText(copyText.value).then(() => {
                alert("Link kopyalandı: " + copyText.value);
            });
        }

        function saveAppointment() {
            const form = document.getElementById('appointment-form');
            const formData = new FormData(form);

            fetch('api/save_appointment.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        // Reset, switch to list or refresh
                        loadConversation(currentCustomerId);
                        // Optional: Switch to cancel tab to see it
                        if (document.querySelector("button[onclick*='iptal']")) {
                            document.querySelector("button[onclick*='iptal']").click();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Bir hata oluştu.');
                });
        }

        function cancelAppointment(id) {
            if (!confirm('Randevuyu iptal etmek istediğinize emin misiniz?')) return;

            const formData = new FormData();
            formData.append('id', id);

            fetch('api/cancel_appointment.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        // Refresh details to update list
                        loadConversation(currentCustomerId);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Bir hata oluştu.');
                });
        }

        function goToPage(page) {
            loadCustomers(page, currentStatus, currentPersonnel, currentCampaign, currentSearch);
        }

        function openNewRecordModal() {
            document.getElementById('newRecordModal').style.display = 'flex';
        }

        function closeNewRecordModal() {
            document.getElementById('newRecordModal').style.display = 'none';
        }

        // Generate a deterministic color based on string
        function getProfileColor(name) {
            if (!name) return 'linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%)';
            const colors = [
                'linear-gradient(135deg, #6366f1 0%, #818cf8 100%)', // Indigo
                'linear-gradient(135deg, #ec4899 0%, #f472b6 100%)', // Pink
                'linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%)', // Violet
                'linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%)', // Amber
                'linear-gradient(135deg, #10b981 0%, #34d399 100%)', // Emerald
                'linear-gradient(135deg, #ef4444 0%, #f87171 100%)', // Red
                'linear-gradient(135deg, #0ea5e9 0%, #38bdf8 100%)'  // Sky
            ];
            let hash = 0;
            for (let i = 0; i < name.length; i++) {
                hash = name.charCodeAt(i) + ((hash << 5) - hash);
            }
            const index = Math.abs(hash) % colors.length;
            return colors[index];
        }

        function toggleTemplates() {
            const container = document.getElementById('templates-container');
            // If display is empty or none, show it. Otherwise hide it.
            const isHidden = !container.style.display || container.style.display === 'none';
            container.style.display = isHidden ? 'block' : 'none';

            if (isHidden) {
                container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        function scrollToBottom() {
            const box = document.getElementById('chat-box');
            box.scrollTop = box.scrollHeight;
        }

        function applyProfileColors() {
            document.querySelectorAll('.profile-circle').forEach(el => {
                const name = el.getAttribute('data-name');
                el.style.background = getProfileColor(name);
                el.style.color = 'white';
                el.style.textShadow = '0 1px 2px rgba(0,0,0,0.1)';
            });
        }

        function loadTemplates() {
            fetch('api/get_templates.php')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('templates-container');
                    if (data.success && data.templates.length > 0) {
                        container.innerHTML = `<div class="template-selector-title">Hazır Şablonlar</div>` +
                            data.templates.map(t => `
                            <button class="btn-template flex items-center gap-2" onclick="sendWhatsAppTemplate('${t.id}')">
                                ${t.image_url ? '<i class="ph ph-image-square text-indigo-500"></i>' : ''}
                                ${t.title}
                            </button>
                        `).join('');
                    } else if (!data.success) {
                        console.error('Template loading failed:', data.message);
                    }
                });
        }

        function sendWhatsAppTemplate(templateId) {
            if (!currentPhone) {
                alert('Önce bir görüşme seçmelisiniz.');
                return;
            }
            if (!confirm('Bu şablon mesajı gönderilsin mi?')) return;

            // Show loading state
            const container = document.getElementById('templates-container');
            const originalContent = container.innerHTML;
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center p-8 text-indigo-600">
                    <i class="ph ph-circle-notch ph-spin text-4xl mb-2"></i>
                    <div class="font-medium">Mesaj Gönderiliyor...</div>
                </div>
            `;

            const formData = new FormData();
            formData.append('phone', currentPhone);
            formData.append('template_id', templateId);
            formData.append('type', 'template');

            const activeItem = document.querySelector('.contact-item.active');

            fetch('api/send_message.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        // Success state
                        container.innerHTML = `
                            <div class="flex flex-col items-center justify-center p-8 text-emerald-600">
                                <i class="ph ph-check-circle text-4xl mb-2"></i>
                                <div class="font-medium text-center">Başarıyla Gönderildi</div>
                            </div>
                        `;
                        // Auto-close templates after 1.5s
                        setTimeout(() => {
                            container.style.display = 'none';
                            container.innerHTML = originalContent;
                            loadConversation(currentPhone, null, null, activeItem);
                        }, 1500);
                    } else {
                        // Error state
                        container.innerHTML = `
                            <div class="flex flex-col items-center justify-center p-8 text-rose-600">
                                <i class="ph ph-x-circle text-4xl mb-2"></i>
                                <div class="font-medium text-center mb-1">Gönderim Başarısız</div>
                                <div class="text-xs text-center opacity-75">${data.message}</div>
                                <button class="mt-3 text-xs underline" onclick="loadTemplates()">Şablonlara Dön</button>
                            </div>
                        `;
                        console.error('Full Error:', data);
                    }
                })
                .catch(err => {
                    container.innerHTML = `
                        <div class="flex flex-col items-center justify-center p-8 text-rose-600">
                            <i class="ph ph-warning text-4xl mb-2"></i>
                            <div class="font-medium text-center">Sorgu Hatası</div>
                            <button class="mt-3 text-xs underline" onclick="loadTemplates()">Tekrar Dene</button>
                        </div>
                    `;
                });
        }

        // Initial load
        window.onload = () => {
            loadModalOptions();
            loadCustomers(1, currentStatus, currentPersonnel, currentCampaign, currentSearch);
        };
    </script>
    <script>
        function toggleDrawer() {
            document.getElementById('menuDrawer').classList.toggle('active');
            document.getElementById('drawerOverlay').classList.toggle('active');
        }
    </script>
</body>

</html>