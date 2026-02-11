<?php
require_once 'auth.php';
requireLogin();

// Pagination Settings
$limit = 50;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = $page < 1 ? 1 : $page;
$offset = ($page - 1) * $limit;

// Status Filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'empty'; // Default to empty

// Fetch statuses for the sidebar dropdown
$statuses = $pdo->query("SELECT * FROM tbl_ayarlar_gorusme_sonucu_bilgileri ORDER BY id ASC")->fetchAll();

// Fetch form data
$hospitals = $pdo->query("SELECT id, baslik FROM tbl_ayarlar_hastane_bilgileri ORDER BY baslik ASC")->fetchAll();
$departments = $pdo->query("SELECT id, baslik, ayarlar_hastane_bilgileri_id FROM tbl_ayarlar_bolum_bilgileri ORDER BY baslik ASC")->fetchAll();
$doctors = $pdo->query("SELECT id, baslik, ayarlar_hastane_bilgileri_id, ayarlar_bolum_bilgileri_id FROM tbl_ayarlar_doktor_bilgileri ORDER BY baslik ASC")->fetchAll();
$complaint_topics = $pdo->query("SELECT id, baslik FROM tbl_ayarlar_sikayet_konusu_bilgileri ORDER BY baslik ASC")->fetchAll();

// Fetch personnel and campaigns for sidebar filters
$all_personnel = $pdo->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll();
$all_campaigns = $pdo->query("SELECT id, baslik FROM tbl_ayarlar_kampanya_bilgileri ORDER BY baslik ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Ekranı - YerliCRM</title>
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/main.css">
</head>

<body
    style="height: 100vh; overflow: hidden; display: flex; justify-content: center; align-items: center; background-color: #f1f5f9; padding: 0.5rem 1.5rem;">
    <div class="chat-page">
        <!-- Sol: Müşteri Listesi -->
        <aside class="chat-sidebar">

            <div class="sidebar-controls">
                <div class="controls-row">
                    <select class="status-select" id="personnel-filter" onchange="filterByPersonnel(this.value)">
                        <option value="all">Personel</option>
                        <?php foreach ($all_personnel as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['username']); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select class="status-select" id="campaign-filter" onchange="filterByCampaign(this.value)">
                        <option value="all">Kampanya</option>
                        <?php foreach ($all_campaigns as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['baslik']); ?>">
                                <?php echo htmlspecialchars($c['baslik']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button class="btn-new-record" onclick="openNewRecordModal()" title="Yeni Kayıt Ekle">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                        </svg>
                    </button>
                </div>

                <select class="status-select" id="status-filter" onchange="filterByStatus(this.value)">
                    <option value="all">Sonuç Filtresi</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?php echo htmlspecialchars($s['baslik']); ?>" <?php echo $status_filter === $s['baslik'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['baslik']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="sidebar-tabs">
                    <button class="tab-btn active" id="tab-empty" onclick="setMainFilter('empty')">Yeni</button>
                    <button class="tab-btn" id="tab-all" onclick="setMainFilter('all')">Tümü</button>
                </div>
            </div>

            <div class="spinner-container" id="list-spinner" style="display: none;">
                <div class="spinner"></div>
            </div>
            <div class="contact-list" id="contact-list-container">
            </div>

            <div class="sidebar-footer">
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
        <main class="chat-main">
            <header class="sidebar-header" style="background: white;">
                <h3 id="chat-title">Müşteri Seçin</h3>
            </header>

            <div class="chat-messages" id="chat-box">
                <div style="text-align: center; margin-top: 5rem; color: var(--text-muted);">
                    Yazışmaları görüntülemek için sol taraftan bir müşteri seçin.
                </div>
            </div>

            <div class="chat-input-area" id="input-area" style="display: none;">
                <textarea id="message-text" placeholder="Mesajınızı yazın..."></textarea>
                <button class="btn-send" onclick="sendMessage()">
                    <svg viewBox="0 0 24 24">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                    </svg>
                </button>
            </div>

            <div class="interaction-container" id="interaction-area" style="display: none;">
                <form id="interaction-form" enctype="multipart/form-data">
                    <input type="hidden" name="phone" id="form-phone">
                    <div class="interaction-grid">
                        <!-- Top left: Note -->
                        <div class="field-group full-height" style="grid-row: span 4;">
                            <label>Görüşme Notu</label>
                            <textarea name="note" id="form-note"
                                placeholder="Görüşme notunu buraya yazın..."></textarea>
                        </div>

                        <div class="field-group">
                            <label>Kampanya / Talep Türü</label>
                            <select name="kampanya" id="form-campaign" onchange="toggleComplaintFields(this.value)">
                                <option value="">Seçiniz</option>
                                <option value="Teşekkür, Şikayet, Öneri">Teşekkür, Şikayet, Öneri</option>
                                <?php foreach ($all_campaigns as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['baslik']); ?>">
                                        <?php echo htmlspecialchars($c['baslik']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field-group">
                            <label>Görüşme Sonucu</label>
                            <select name="status_id">
                                <option value="">Görüşme Sonucu Seçiniz</option>
                                <?php foreach ($statuses as $s): ?>
                                    <option value="<?php echo $s['baslik']; ?>">
                                        <?php echo htmlspecialchars($s['baslik']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field-group">
                            <label>Tekrar Arama Tarihi</label>
                            <input type="date" name="callback_date">
                        </div>

                        <div class="field-group">
                            <label>Lead Puanlama</label>
                            <select name="lead_score">
                                <option value="">Puanlama</option>
                                <option value="1">1 Yıldız</option>
                                <option value="2">2 Yıldız</option>
                                <option value="3">3 Yıldız</option>
                                <option value="4">4 Yıldız</option>
                                <option value="5">5 Yıldız</option>
                            </select>
                        </div>

                        <!-- Şikayet fields - initially hidden -->
                        <div class="field-group complaint-field hidden">
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

                        <div class="field-group complaint-field hidden">
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

                        <div class="field-group complaint-field hidden">
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

                        <!-- Bottom row: Topic, Detail, File -->
                        <div class="field-group complaint-field hidden">
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

                        <div class="field-group complaint-field hidden" style="grid-column: span 2;">
                            <label>Şikayet Detayı</label>
                            <input type="text" name="complaint_detail" placeholder="Şikayet detayı...">
                        </div>

                        <div class="field-group complaint-field hidden">
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
        <aside class="chat-details" id="detail-box">
            <div style="text-align: center; color: var(--text-muted); margin-top: 5rem;">
                Detaylar burada görünecek.
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

    <script>
        let currentPhone = '';

        function toggleComplaintFields(val) {
            const fields = document.querySelectorAll('.complaint-field');
            const showList = ['Teşekkür', 'Şikayet', 'Öneri'];
            // Check if selected value contains any of the showList keywords
            const shouldShow = showList.some(keyword => val.includes(keyword));

            if (shouldShow) {
                fields.forEach(f => f.classList.remove('hidden'));
            } else {
                fields.forEach(f => f.classList.add('hidden'));
            }
        }

        function loadConversation(phone, element) {
            currentPhone = phone;
            document.querySelectorAll('.contact-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            document.getElementById('chat-title').innerText = phone;
            document.getElementById('input-area').style.display = 'flex';
            document.getElementById('interaction-area').style.display = 'block';
            document.getElementById('form-phone').value = phone;

            // Reset form and hide complaint fields
            const form = document.getElementById('interaction-form');
            form.reset();
            toggleComplaintFields('');

            fetch('api/get_messages.php?phone=' + phone)
                .then(response => response.text())
                .then(html => {
                    const box = document.getElementById('chat-box');
                    box.innerHTML = html;
                    box.scrollTop = box.scrollHeight;
                });

            fetch('api/get_details.php?phone=' + phone)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('detail-box').innerHTML = html;
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
        let currentStatus = '<?php echo $status_filter; ?>';
        let currentPersonnel = 'all';
        let currentCampaign = 'all';

        function loadCustomers(page = 1, status = currentStatus, personnel = currentPersonnel, campaign = currentCampaign) {
            currentPage = page;
            currentStatus = status;
            currentPersonnel = personnel;
            currentCampaign = campaign;
            const container = document.getElementById('contact-list-container');
            const spinner = document.getElementById('list-spinner');
            const pagination = document.getElementById('pagination-container');
            const info = document.getElementById('pagination-info');

            if (spinner) spinner.style.display = 'flex';

            fetch(`api/get_customers.php?page=${page}&status=${encodeURIComponent(status)}&personnel=${personnel}&campaign=${encodeURIComponent(campaign)}`)
                .then(r => r.json())
                .then(data => {
                    if (spinner) spinner.style.display = 'none';
                    if (data.success) {
                        container.innerHTML = data.html;
                        pagination.innerHTML = data.pagination_html;
                        info.innerText = `Sayfa ${data.page} / ${data.total_pages}`;
                        document.getElementById('record-info').innerText = `${data.start}...${data.end} arası kayıtlar gösteriliyor (Toplam: ${data.total_records})`;
                    }
                });
        }

        function filterByStatus(status) {
            // Remove active state from main tabs if a specific result status is selected
            if (status !== 'all' && status !== 'empty') {
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            }
            loadCustomers(1, status, currentPersonnel, currentCampaign);
        }

        function setMainFilter(status) {
            // Update Tab UI
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            if (status === 'all') document.getElementById('tab-all').classList.add('active');
            if (status === 'empty') document.getElementById('tab-empty').classList.add('active');

            // Reset status dropdown to default
            document.getElementById('status-filter').value = 'all';

            filterByStatus(status);
        }

        function filterByPersonnel(personnel) {
            loadCustomers(1, currentStatus, personnel, currentCampaign);
        }

        function filterByCampaign(campaign) {
            loadCustomers(1, currentStatus, currentPersonnel, campaign);
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
                    if(document.querySelector("button[onclick*='iptal']")) {
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
            loadCustomers(page, currentStatus, currentPersonnel, currentCampaign);
        }

        function openNewRecordModal() {
            document.getElementById('newRecordModal').style.display = 'flex';
        }

        function closeNewRecordModal() {
            document.getElementById('newRecordModal').style.display = 'none';
        }

        // Initial load
        window.onload = () => loadCustomers(1, currentStatus, currentPersonnel, currentCampaign);
    </script>
</body>

</html>