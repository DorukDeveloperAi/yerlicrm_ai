<?php
require_once '../config.php';
require_once '../auth.php';
requireLogin();

$phone = $_GET['phone'] ?? '';

if (!$phone) {
    echo "Lütfen bir müşteri seçin.";
    exit;
}

// Get the latest record for details
$stmt = $pdo->prepare("SELECT * FROM tbl_icerik_bilgileri_ai WHERE telefon_numarasi = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$phone]);
$detail = $stmt->fetch();

if (!$detail) {
    echo "Detay bulunamadı.";
    exit;
}

// Ensure appointments table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS tbl_randevular (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_phone VARCHAR(20),
        service VARCHAR(100),
        doctor_id INT,
        appointment_date DATETIME,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (customer_phone),
        INDEX (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
} catch (Exception $e) {
    // Log error or ignore
}

// Fetch active appointments
$active_appointments = [];
try {
    $appointments_stmt = $pdo->prepare("
        SELECT r.*, u.username as doctor_name 
        FROM tbl_randevular r 
        LEFT JOIN users u ON r.doctor_id = u.id 
        WHERE r.customer_phone = :phone AND r.status = 'active' 
        ORDER BY r.appointment_date ASC
    ");
    $appointments_stmt->execute([':phone' => $detail['telefon_numarasi']]);
    $active_appointments = $appointments_stmt->fetchAll();
} catch (Exception $e) {
    // Fallback if table doesn't exist or query fails
}

// Fetch doctors for the dropdown
$doctors = [];
try {
    $doctors_stmt = $pdo->query("SELECT id, username FROM users WHERE role = 'doctor' OR role IS NULL ORDER BY username ASC");
    $doctors = $doctors_stmt->fetchAll();
} catch (Exception $e) {
    // Fallback
}

// Fetch campaigns for the dropdown
$campaigns = [];
try {
    $campaigns_stmt = $pdo->query("SELECT DISTINCT kampanya FROM tbl_icerik_bilgileri_ai WHERE kampanya IS NOT NULL AND kampanya != '' ORDER BY kampanya ASC");
    $campaigns = $campaigns_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // Fallback
}

// Fetch hospitals
$hospitals = [];
try {
    $hospitals_stmt = $pdo->query("SELECT DISTINCT hastane FROM tbl_icerik_bilgileri_ai WHERE hastane IS NOT NULL AND hastane != '' ORDER BY hastane ASC");
    $hospitals = $hospitals_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // Fallback
}

// Fetch request contents (talep_icerik)
$requests = [];
try {
    $requests_stmt = $pdo->query("SELECT DISTINCT talep_icerik FROM tbl_icerik_bilgileri_ai WHERE talep_icerik IS NOT NULL AND talep_icerik != '' ORDER BY talep_icerik ASC");
    $requests = $requests_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // Fallback
}

// Fetch departments (bolum)
$departments = [];
try {
    $departments_stmt = $pdo->query("SELECT DISTINCT bolum FROM tbl_icerik_bilgileri_ai WHERE bolum IS NOT NULL AND bolum != '' ORDER BY bolum ASC");
    $departments = $departments_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // Fallback
}

// Fetch doctors (doktor)
$doctors_list = [];
try {
    $doctors_list_stmt = $pdo->query("SELECT DISTINCT doktor FROM tbl_icerik_bilgileri_ai WHERE doktor IS NOT NULL AND doktor != '' ORDER BY doktor ASC");
    $doctors_list = $doctors_list_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // Fallback
}

// Fetch sales representatives filtered by campaign
$customer_campaign = $detail['kampanya'] ?? '';
$reps_data = [];
if ($customer_campaign) {
    // Fetch users where their responsible campaigns include the customer's campaign
    $stmt_reps = $pdo->prepare("SELECT id, username FROM users WHERE status = 1 AND sorumlu_oldugu_kampanya LIKE ? ORDER BY username ASC");
    $stmt_reps->execute(['%' . $customer_campaign . '%']);
    $reps_data = $stmt_reps->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Fallback: fetch all active users if no campaign is set
    $reps_data = $pdo->query("SELECT id, username FROM users WHERE status = 1 ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);
}

// Find current rep name
$current_rep_name = '-';
if ($detail['user_id']) {
    $stmt_rep = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt_rep->execute([$detail['user_id']]);
    $current_rep_name = $stmt_rep->fetchColumn() ?: '-';
}
?>
<div class="detail-header">
    <div class="profile-img"></div>
    <div class="profile-info">
        <h3><?php echo htmlspecialchars($detail['musteri_adi_soyadi'] ?: 'İsimsiz Müşteri'); ?></h3>
        <p><?php echo htmlspecialchars($detail['email_adresi'] ?: 'E-posta Yok'); ?></p>
        <p class="phone-row">
            <i class="ph-phone"></i>
            <?php echo htmlspecialchars($detail['telefon_numarasi']); ?>
        </p>
    </div>
</div>

<div class="detail-tabs">
    <button class="detail-tab-btn active" onclick="switchDetailTab('genel', this)">Detay</button>
    <button class="detail-tab-btn" onclick="switchDetailTab('online', this)">Online Randevu</button>
    <button class="detail-tab-btn" onclick="switchDetailTab('iptal', this)">Randevu İptal</button>
    <button class="detail-tab-btn" onclick="switchDetailTab('sikayet', this)">Şikayet Bilgileri</button>
</div>

<div class="detail-tab-content">
    <!-- ... (Genel tab content remains same) ... -->
    <div id="detail-pane-genel" class="detail-tab-pane active">

        <div style="display: flex; gap: 0.5rem;">
            <!-- Campaign -->
            <div class="detail-form-group" style="flex: 1;">
                <label class="detail-label-sm">Kampanya</label>
                <div class="detail-value-row">
                    <span class="detail-value-text"><?php echo htmlspecialchars($detail['kampanya'] ?: '-'); ?></span>
                    <button class="btn-edit-icon" title="Değiştir"
                        onclick="openEditModal('kampanya', '<?php echo addslashes($detail['kampanya']); ?>', 'Kampanya')">
                        <i class="ph ph-pencil-simple"></i>
                    </button>
                </div>
            </div>

            <!-- Request Content (Talep İçerik) -->
            <div class="detail-form-group" style="flex: 1;">
                <label class="detail-label-sm">Talep İçerik</label>
                <div class="detail-value-row">
                    <span
                        class="detail-value-text"><?php echo htmlspecialchars($detail['talep_icerik'] ?: '-'); ?></span>
                    <button class="btn-edit-icon" title="Değiştir"
                        onclick="openEditModal('talep_icerik', '<?php echo addslashes($detail['talep_icerik']); ?>', 'Talep İçerik')">
                        <i class="ph ph-pencil-simple"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Hospital -->
        <div class="detail-form-group">
            <label class="detail-label-sm">Hastane</label>
            <div class="detail-value-row">
                <span class="detail-value-text"><?php echo htmlspecialchars($detail['hastane'] ?: '-'); ?></span>
                <button class="btn-edit-icon" title="Değiştir"
                    onclick="openEditModal('hastane', '<?php echo addslashes($detail['hastane']); ?>', 'Hastane')">
                    <i class="ph ph-pencil-simple"></i>
                </button>
            </div>
        </div>

        <!-- Department -->
        <div class="detail-form-group">
            <label class="detail-label-sm">Bölüm</label>
            <div class="detail-value-row">
                <span class="detail-value-text"><?php echo htmlspecialchars($detail['bolum'] ?: '-'); ?></span>
                <button class="btn-edit-icon" title="Değiştir"
                    onclick="openEditModal('bolum', '<?php echo addslashes($detail['bolum']); ?>', 'Bölüm')">
                    <i class="ph ph-pencil-simple"></i>
                </button>
            </div>
        </div>

        <!-- Doctor -->
        <div class="detail-form-group">
            <label class="detail-label-sm">Doktor</label>
            <div class="detail-value-row">
                <span class="detail-value-text"><?php echo htmlspecialchars($detail['doktor'] ?: '-'); ?></span>
                <button class="btn-edit-icon" title="Değiştir"
                    onclick="openEditModal('doktor', '<?php echo addslashes($detail['doktor']); ?>', 'Doktor')">
                    <i class="ph ph-pencil-simple"></i>
                </button>
            </div>
        </div>

        <hr class="detail-divider">

        <!-- Birth Week -->
        <div class="detail-form-group">
            <label class="detail-label-sm">Doğum Haftası</label>
            <div class="detail-value-row">
                <span class="detail-value-text">
                    <?php echo htmlspecialchars($detail['dogum_haftasi'] ? $detail['dogum_haftasi'] . '. Haftası' : '-'); ?>
                </span>
                <button class="btn-edit-icon" title="Değiştir"
                    onclick="openEditModal('dogum_haftasi', '<?php echo htmlspecialchars($detail['dogum_haftasi'] ?? ''); ?>', 'Doğum Haftası')">
                    <i class="ph ph-pencil-simple"></i>
                </button>
            </div>
        </div>

        <hr class="detail-divider">

        <!-- Sales Rep -->
        <div class="detail-form-group">
            <label class="detail-label-sm">Satış Temsilcisi</label>
            <div class="detail-value-row">
                <span class="detail-value-text"><?php echo htmlspecialchars($current_rep_name); ?></span>
                <button class="btn-edit-icon" title="Değiştir"
                    onclick='openEditModal("user_id", "<?php echo $detail["user_id"]; ?>", "Satış Temsilcisi", <?php echo json_encode($reps_data); ?>)'>
                    <i class="ph ph-pencil-simple"></i>
                </button>
            </div>
        </div>

        <hr class="detail-divider">

        <!-- Dates -->
        <div class="detail-info-row">
            <strong>Başvuru Tarihi :</strong> <?php echo date('d/m/Y H:i', strtotime($detail['date'])); ?>
        </div>
        <div class="detail-info-row">
            <strong>Son Güncelleme Tarihi :</strong> <?php echo date('d/m/Y H:i'); // Placeholder for update time ?>
        </div>
        <div class="detail-info-row">
            <strong>Tekrar Arama Tarihi :</strong>
            <?php echo $detail['tekrar_arama_tarihi'] ? date('d/m/Y H:i', strtotime($detail['tekrar_arama_tarihi'])) : '-'; ?>
        </div>

        <hr class="detail-divider">

        <!-- Application Channels -->
        <div class="detail-form-group">
            <label class="detail-label-sm">Başvuru Kanalları</label>
            <div class="channel-list">
                <div><?php echo htmlspecialchars($detail['geldigi_yer'] ?: 'Form'); ?></div>
            </div>
        </div>

        <hr class="detail-divider">

        <!-- Lead Link -->
        <div class="detail-form-group">
            <label class="detail-label-sm">Lead Link</label>
            <input type="text" class="form-input" value="https://dorukcrm.com.tr/LEAD<?php echo $detail['id']; ?>"
                readonly id="lead-link-<?php echo $detail['id']; ?>">
            <button class="btn-copy" onclick="copyLeadLink('lead-link-<?php echo $detail['id']; ?>')">Kopyala</button>
        </div>
    </div>

    <div id="detail-pane-online" class="detail-tab-pane">
        <div class="detail-section" style="text-align: center; padding: 2rem; color: #94a3b8;">
            <i class="ph ph-wrench" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
            <p>Hazırlanıyor...</p>
        </div>
    </div>

    <div id="detail-pane-iptal" class="detail-tab-pane">
        <div class="detail-section" style="text-align: center; padding: 2rem; color: #94a3b8;">
            <i class="ph ph-wrench" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
            <p>Hazırlanıyor...</p>
        </div>
    </div>

    <div id="detail-pane-sikayet" class="detail-tab-pane">
        <div class="detail-section" style="text-align: center; padding: 2rem; color: #94a3b8;">
            <i class="ph ph-wrench" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
            <p>Hazırlanıyor...</p>
        </div>
    </div>
</div>
<style>
    .btn-edit-icon {
        background: none;
        border: none;
        cursor: pointer;
        color: #94a3b8;
        padding: 4px;
        border-radius: 4px;
        transition: color 0.2s, background 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-edit-icon:hover {
        color: #6366f1;
        background: #e0e7ff;
    }
</style>