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
// Fetch form data (Commented out as tables do not exist and variables appear unused)
// $hospitals = $pdo->query("SELECT id, baslik FROM tbl_ayarlar_hastane_bilgileri ORDER BY baslik ASC")->fetchAll();
// $departments = $pdo->query("SELECT id, baslik, ayarlar_hastane_bilgileri_id FROM tbl_ayarlar_bolum_bilgileri ORDER BY baslik ASC")->fetchAll();
// $doctors = $pdo->query("SELECT id, baslik, ayarlar_hastane_bilgileri_id, ayarlar_bolum_bilgileri_id FROM tbl_ayarlar_doktor_bilgileri ORDER BY baslik ASC")->fetchAll();
// $complaint_topics = $pdo->query("SELECT id, baslik FROM tbl_ayarlar_sikayet_konusu_bilgileri ORDER BY baslik ASC")->fetchAll();

// Fetch personnel and campaigns for sidebar filters
$all_personnel = $pdo->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll();
$all_campaigns = $pdo->query("SELECT DISTINCT kampanya as baslik FROM tbl_icerik_bilgileri_ai WHERE kampanya IS NOT NULL AND kampanya != '' ORDER BY kampanya ASC")->fetchAll();

// Fetch data for Edit Modal (Global Scope)
$modal_hospitals = $pdo->query("SELECT DISTINCT hastane FROM tbl_icerik_bilgileri_ai WHERE hastane IS NOT NULL AND hastane != '' ORDER BY hastane ASC")->fetchAll(PDO::FETCH_COLUMN);
$modal_departments = $pdo->query("SELECT DISTINCT bolum FROM tbl_icerik_bilgileri_ai WHERE bolum IS NOT NULL AND bolum != '' ORDER BY bolum ASC")->fetchAll(PDO::FETCH_COLUMN);
$modal_doctors = $pdo->query("SELECT DISTINCT doktor FROM tbl_icerik_bilgileri_ai WHERE doktor IS NOT NULL AND doktor != '' ORDER BY doktor ASC")->fetchAll(PDO::FETCH_COLUMN);
$modal_requests = $pdo->query("SELECT DISTINCT talep_icerik FROM tbl_icerik_bilgileri_ai WHERE talep_icerik IS NOT NULL AND talep_icerik != '' ORDER BY talep_icerik ASC")->fetchAll(PDO::FETCH_COLUMN);
$modal_campaigns = array_column($all_campaigns, 'baslik');
?>
<?php
$pageTitle = 'Chat Ekranı - YerliCRM';
$activePage = 'chat';
include 'layout_header.php';
?>

<style>
    /* Adjust chat page for layout integration */
    .chat-page {
        height: calc(100vh - 120px) !important;
        /* Adjust for header and padding */
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        box-shadow: none !important;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
    }

    .main-content {
        padding: 1.5rem !important;
        overflow: hidden !important;
        height: 100vh;
    }
</style>
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
            <div id="record-info" style="font-size: 0.8rem; color: #64748b; margin-top: 0.5rem; text-align: center;">
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
                        <textarea name="note" id="form-note" placeholder="Görüşme notunu buraya yazın..."></textarea>
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
    // Global Data for Edit Modal
    const detailData = {
        campaigns: <?php echo json_encode($modal_campaigns); ?>,
        requests: <?php echo json_encode($modal_requests); ?>,
        hospitals: <?php echo json_encode($modal_hospitals); ?>,
        departments: <?php echo json_encode($modal_departments); ?>,
        doctors: <?php echo json_encode($modal_doctors); ?>
    };

    function openEditModal(field, currentValue, label) {
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
        const buildSelect = (options) => {
            let html = '<select id="editValue" class="form-select">';
            html += '<option value="">Seçiniz</option>';
            options.forEach(opt => {
                const selected = opt === currentValue ? 'selected' : '';
                html += `<option value="${opt}" ${selected}>${opt}</option>`;
            });
            // Keep current if not in list
            if (currentValue && !options.includes(currentValue)) {
                html += `<option value="${currentValue}" selected>${currentValue}</option>`;
            }
            html += '</select>';
            return html;
        };

        if (field === 'kampanya') inputHtml = buildSelect(detailData.campaigns);
        else if (field === 'talep_icerik') inputHtml = buildSelect(detailData.requests);
        else if (field === 'hastane') inputHtml = buildSelect(detailData.hospitals);
        else if (field === 'bolum') inputHtml = buildSelect(detailData.departments);
        else if (field === 'doktor') inputHtml = buildSelect(detailData.doctors);
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
<?php include 'layout_footer.php'; ?>