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

// Ensure appointments table exists
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

// Fetch active appointments
$appointments_stmt = $pdo->prepare("
    SELECT r.*, u.username as doctor_name 
    FROM tbl_randevular r 
    LEFT JOIN users u ON r.doctor_id = u.id 
    WHERE r.customer_phone = :phone AND r.status = 'active' 
    ORDER BY r.appointment_date ASC
");
$appointments_stmt->execute([':phone' => $detail['telefon_numarasi']]);
$active_appointments = $appointments_stmt->fetchAll();

// Fetch doctors for the dropdown
$doctors_stmt = $pdo->query("SELECT id, username FROM users WHERE role = 'doctor' OR role IS NULL ORDER BY username ASC"); // Assuming doctors are users
$doctors = $doctors_stmt->fetchAll();
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
    <button class="detail-tab-btn active" onclick="switchDetailTab('genel', this)">Genel Bilgileri</button>
    <button class="detail-tab-btn" onclick="switchDetailTab('online', this)">Online Randevu</button>
    <button class="detail-tab-btn" onclick="switchDetailTab('iptal', this)">Randevu İptal</button>
    <button class="detail-tab-btn" onclick="switchDetailTab('sikayet', this)">Şikayet Bilgileri</button>
</div>

<div class="detail-tab-content">
    <!-- ... (Genel tab content remains same) ... -->
    <div id="detail-pane-genel" class="detail-tab-pane active">
        <!-- Campaign -->
        <div class="detail-form-group">
            <select class="form-select">
                <option value="">Teşekkür, Şikayet, Öneri</option>
                <option value="<?php echo htmlspecialchars($detail['kampanya']); ?>" selected><?php echo htmlspecialchars($detail['kampanya']); ?></option>
            </select>
        </div>
        <!-- ... (Rest of Genel tab) ... -->

        <!-- Hospital -->
        <div class="detail-form-group">
            <select class="form-select">
                <option value="">Hastane Seçiniz</option>
                <option value="<?php echo htmlspecialchars($detail['hastane']); ?>" selected><?php echo htmlspecialchars($detail['hastane']); ?></option>
            </select>
        </div>

        <!-- Department -->
        <div class="detail-form-group">
            <select class="form-select">
                <option value="">Bölüm Seçiniz</option>
                <option value="<?php echo htmlspecialchars($detail['bolum']); ?>" selected><?php echo htmlspecialchars($detail['bolum']); ?></option>
            </select>
        </div>

        <!-- Doctor -->
        <div class="detail-form-group">
            <select class="form-select">
                <option value="">Doktor Seçiniz</option>
                <option value="<?php echo htmlspecialchars($detail['doktor']); ?>" selected><?php echo htmlspecialchars($detail['doktor']); ?></option>
            </select>
        </div>

        <hr class="detail-divider">

        <!-- Sales Rep -->
        <div class="detail-form-group">
            <label class="detail-label-sm">Satış Temsilcisi</label>
            <select class="form-select">
                <option value="">Satış Temsilcisi Seçiniz</option>
                <?php foreach ($reps as $rep): ?>
                    <option value="<?php echo $rep['id']; ?>" <?php echo $detail['user_id'] == $rep['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($rep['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
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
            <strong>Tekrar Arama Tarihi :</strong> <?php echo $detail['tekrar_arama_tarihi'] ? date('d/m/Y H:i', strtotime($detail['tekrar_arama_tarihi'])) : '-'; ?>
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
            <input type="text" class="form-input" value="https://dorukcrm.com.tr/LEAD<?php echo $detail['id']; ?>" readonly id="lead-link-<?php echo $detail['id']; ?>">
            <button class="btn-copy" onclick="copyLeadLink('lead-link-<?php echo $detail['id']; ?>')">Kopyala</button>
        </div>
    </div>

    <div id="detail-pane-online" class="detail-tab-pane">
        <div class="detail-section">
            <h4>Randevu Oluştur</h4>
            <form id="appointment-form" onsubmit="event.preventDefault(); saveAppointment();">
                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($detail['telefon_numarasi']); ?>">
                
                <div class="detail-form-group">
                    <label class="detail-label-sm">Hizmet / Poliklinik</label>
                    <select class="form-select" name="service" required>
                        <option value="">Seçiniz</option>
                        <option value="Saç Ekimi">Saç Ekimi</option>
                        <option value="Diş Tedavisi">Diş Tedavisi</option>
                        <option value="Plastik Cerrahi">Plastik Cerrahi</option>
                        <option value="Göz Tedavisi">Göz Tedavisi</option>
                    </select>
                </div>

                <div class="detail-form-group">
                    <label class="detail-label-sm">Doktor</label>
                    <select class="form-select" name="doctor_id" required>
                        <option value="">Doktor Seçiniz</option>
                         <?php foreach ($doctors as $doc): ?>
                            <option value="<?php echo $doc['id']; ?>"><?php echo htmlspecialchars($doc['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="detail-form-group">
                    <label class="detail-label-sm">Tarih</label>
                    <input type="date" class="form-input" name="date" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="detail-form-group">
                    <label class="detail-label-sm">Saat</label>
                    <select class="form-select" name="time" required>
                        <option value="">Saat Seçiniz</option>
                        <?php 
                        $start = strtotime('09:00');
                        $end = strtotime('18:00');
                        for ($i = $start; $i <= $end; $i += 1800) { // 30 min intervals
                            echo '<option value="' . date('H:i', $i) . '">' . date('H:i', $i) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" class="btn-copy" style="background-color: var(--primary);">Randevu Oluştur</button>
            </form>
        </div>
    </div>

    <div id="detail-pane-iptal" class="detail-tab-pane">
        <div class="detail-section">
            <h4>Aktif Randevular</h4>
            <?php if (count($active_appointments) > 0): ?>
                <?php foreach ($active_appointments as $appt): ?>
                    <div class="detail-item" style="display: block; margin-bottom: 1rem; border-bottom: 1px solid #eee; padding-bottom: 1rem;">
                        <div style="font-weight: 600; color: var(--text-main);">
                            <?php echo date('d.m.Y H:i', strtotime($appt['appointment_date'])); ?>
                        </div>
                        <div style="font-size: 0.9rem; color: #64748b;">
                            <?php echo htmlspecialchars($appt['service']); ?> - <?php echo htmlspecialchars($appt['doctor_name']); ?>
                        </div>
                        <button class="btn-copy" style="background-color: #ef4444; margin-top: 0.5rem;" onclick="cancelAppointment(<?php echo $appt['id']; ?>)">İptal Et</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Aktif randevu bulunmamaktadır.</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="detail-pane-sikayet" class="detail-tab-pane">
        <div class="detail-section">
            <h4>Şikayet Detayları</h4>
            <?php if (!empty($detail['sikayet_konusu']) || !empty($detail['sikayet_detayi'])): ?>
                <div class="detail-item">
                    <span class="detail-label">Konu</span>
                    <span class="detail-value"><?php echo htmlspecialchars($detail['sikayet_konusu'] ?: '-'); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Detay</span>
                    <span class="detail-value"><?php echo nl2br(htmlspecialchars($detail['sikayet_detayi'] ?: '-')); ?></span>
                </div>
                
                <?php if (!empty($detail['sikayet_hastane']) || !empty($detail['sikayet_doktor'])): ?>
                <hr class="detail-divider">
                <div class="detail-item">
                    <span class="detail-label">İlgili Birim/Kişi</span>
                    <span class="detail-value">
                        <?php 
                        $related = [];
                        if($detail['sikayet_hastane']) $related[] = $detail['sikayet_hastane'];
                        if($detail['sikayet_bolum']) $related[] = $detail['sikayet_bolum'];
                        if($detail['sikayet_doktor']) $related[] = $detail['sikayet_doktor'];
                        echo htmlspecialchars(implode(' / ', $related));
                        ?>
                    </span>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Bu müşteriye ait aktif bir şikayet kaydı bulunmamaktadır.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
