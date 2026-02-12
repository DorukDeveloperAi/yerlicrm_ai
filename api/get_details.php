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

// 1. Get Application Date (very first interaction)
$stmt_app = $pdo->prepare("SELECT MIN(date) FROM tbl_icerik_bilgileri_ai WHERE telefon_numarasi = ?");
$stmt_app->execute([$phone]);
$basvuru_tarihi = $stmt_app->fetchColumn();

// 2. Handle First Access Date tracking in the main table
if (empty($detail['ilk_erisim_tarihi'])) {
    $now = date('Y-m-d H:i:s');
    $stmt_upd = $pdo->prepare("UPDATE tbl_icerik_bilgileri_ai SET ilk_erisim_tarihi = ? WHERE telefon_numarasi = ? AND ilk_erisim_tarihi IS NULL");
    $stmt_upd->execute([$now, $phone]);
    $ilk_erisim_tarihi = $now;
} else {
    $ilk_erisim_tarihi = $detail['ilk_erisim_tarihi'];
}

// Essential info for the sidebar is fetched above ($detail, $basvuru_tarihi, $ilk_erisim_tarihi)

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

// Helper to format dates correctly regardless of string or timestamp
function formatDetailDate($val)
{
    if (!$val)
        return '-';
    if (is_numeric($val))
        return date('d/m/Y H:i', $val);
    $ts = strtotime($val);
    return ($ts && $ts > 0) ? date('d/m/Y H:i', $ts) : '-';
}

// Fetch all Q&A pairs
$qa_pairs = [];
for ($i = 1; $i <= 10; $i++) {
    if (!empty($detail["soru_$i"]) || !empty($detail["cevap_$i"])) {
        $qa_pairs[] = [
            'q' => $detail["soru_$i"] ?: "Soru $i",
            'a' => $detail["cevap_$i"] ?: '-'
        ];
    }
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

        <!-- Branch (Branş) if different or extra -->
        <?php if (!empty($detail['brans']) && $detail['brans'] !== $detail['bolum']): ?>
            <div class="detail-form-group">
                <label class="detail-label-sm">Branş</label>
                <div class="detail-value-row">
                    <span class="detail-value-text"><?php echo htmlspecialchars($detail['brans']); ?></span>
                </div>
            </div>
        <?php endif; ?>

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

        <!-- Dates Section -->
        <div class="detail-form-group">
            <label class="detail-label-sm">Başvuru Tarihi</label>
            <div class="detail-value-row">
                <span class="detail-value-text"><?php echo formatDetailDate($basvuru_tarihi); ?></span>
            </div>
        </div>

        <div class="detail-form-group">
            <label class="detail-label-sm">İlk Erişim Tarihi</label>
            <div class="detail-value-row">
                <span class="detail-value-text"><?php echo formatDetailDate($ilk_erisim_tarihi); ?></span>
            </div>
        </div>

        <div class="detail-form-group">
            <label class="detail-label-sm">Son İşlem Tarihi</label>
            <div class="detail-value-row">
                <span class="detail-value-text"><?php echo formatDetailDate($detail['date']); ?></span>
            </div>
        </div>

        <hr class="detail-divider">

        <!-- Application Channels -> Geldiği Yer -->
        <div class="detail-form-group">
            <label class="detail-label-sm">Geldiği Yer</label>
            <div class="channel-list">
                <div style="font-weight: 600; color: var(--primary);">
                    <?php echo htmlspecialchars($detail['geldigi_yer'] ?: '-'); ?>
                </div>
            </div>
        </div>

        <?php if (!empty($qa_pairs)): ?>
            <hr class="detail-divider">
            <label class="detail-label-sm">Form Soruları</label>
            <?php foreach ($qa_pairs as $pair): ?>
                <div class="detail-info-row"
                    style="margin-bottom: 0.5rem; padding-left: 0.5rem; border-left: 2px solid #e2e8f0;">
                    <div style="font-weight: 600; font-size: 0.7rem; color: #64748b; margin-bottom: 2px;">
                        <?php echo htmlspecialchars($pair['q']); ?>
                    </div>
                    <div style="font-size: 0.75rem;"><?php echo htmlspecialchars($pair['a']); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

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