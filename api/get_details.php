<?php
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
?>
<div class="detail-tabs">
    <button class="detail-tab-btn active" onclick="switchDetailTab('info', this)">Detay</button>
    <button class="detail-tab-btn" onclick="switchDetailTab('appointment', this)">Online Randevu</button>
</div>

<div class="detail-tab-content">
    <div id="detail-pane-info" class="detail-tab-pane active">
        <div class="detail-section">
            <h4>Müşteri Bilgileri</h4>
            <div class="detail-item">
                <span class="detail-label">Adı Soyadı</span>
                <span class="detail-value">
                    <?php echo htmlspecialchars($detail['musteri_adi_soyadi'] ?: 'Belirtilmemiş'); ?>
                </span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Telefon</span>
                <span class="detail-value">
                    <?php echo htmlspecialchars($detail['telefon_numarasi']); ?>
                </span>
            </div>
            <div class="detail-item">
                <span class="detail-label">E-posta</span>
                <span class="detail-value">
                    <?php echo htmlspecialchars($detail['email_adresi'] ?: 'Belirtilmemiş'); ?>
                </span>
            </div>
        </div>

        <div class="detail-section">
            <h4>Talep Özet</h4>
            <div class="detail-item">
                <span class="detail-label">Hastane/Bölüm</span>
                <span class="detail-value">
                    <?php echo htmlspecialchars(($detail['hastane'] ?: '-') . ' / ' . ($detail['bolum'] ?: '-')); ?>
                </span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Doktor</span>
                <span class="detail-value">
                    <?php echo htmlspecialchars($detail['doktor'] ?: '-'); ?>
                </span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Kampanya</span>
                <span class="detail-value">
                    <?php echo htmlspecialchars($detail['kampanya'] ?: '-'); ?>
                </span>
            </div>
        </div>

        <div class="detail-section">
            <h4>Sistem Bilgileri</h4>
            <div class="detail-item">
                <span class="detail-label">Lead ID</span>
                <span class="detail-value">
                    <?php echo htmlspecialchars($detail['lead_id'] ?: '-'); ?>
                </span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Geliş Kaynağı</span>
                <span class="detail-value">
                    <?php echo htmlspecialchars($detail['geldigi_yer'] ?: '-'); ?>
                </span>
            </div>
        </div>
    </div>

    <div id="detail-pane-appointment" class="detail-tab-pane">
        <div class="detail-section">
            <h4>Online Randevu Alma</h4>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">
                Online randevu alma modülü yüklenebilir.
            </p>
        </div>
        <div class="detail-section">
            <h4>İptal Etme</h4>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">
                Randevu iptal modülü yüklenebilir.
            </p>
        </div>
    </div>
</div>