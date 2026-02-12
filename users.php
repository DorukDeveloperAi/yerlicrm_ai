<?php
$pageTitle = 'Kullanıcı Yönetimi - YerliCRM';
$activePage = 'users';
include 'layout_header.php';

require_once 'config.php';
require_once 'auth.php';
requireLogin();

// Pagination settings
$records_per_page = 20;
$current_page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Fetch total records for pagination
$total_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 1");
$total_records = $total_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Fetch users for current page
$stmt = $pdo->prepare("SELECT * FROM users WHERE status = 1 ORDER BY id ASC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

// Fetch Data for Dropdowns
// 1. User Groups (tbl_ayarlar_kullanici_grup_bilgileri)
$user_groups = [];
try {
    $stmt = $pdo->query("SELECT baslik FROM tbl_ayarlar_kullanici_grup_bilgileri ORDER BY baslik ASC");
    if ($stmt)
        $user_groups = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) { /* Ignore if table missing */
}

// 2. Hospitals (tbl_icerik_bilgileri_ai)
$hospitals = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT hastane FROM tbl_icerik_bilgileri_ai WHERE hastane IS NOT NULL AND hastane != ''
ORDER BY hastane ASC");
    if ($stmt)
        $hospitals = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
}

// 3. Doctors (tbl_icerik_bilgileri_ai)
$doctors = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT doktor FROM tbl_icerik_bilgileri_ai WHERE doktor IS NOT NULL AND doktor != '' ORDER
BY doktor ASC");
    if ($stmt)
        $doctors = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
}

// 4. Campaigns (tbl_icerik_bilgileri_ai)
$campaigns = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT kampanya FROM tbl_icerik_bilgileri_ai WHERE kampanya IS NOT NULL AND kampanya != ''
ORDER BY kampanya ASC");
    if ($stmt)
        $campaigns = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
}

// 5. Complaint Topics (tbl_ayarlar_sikayet_konusu_bilgileri)
$complaint_topics = [];
try {
    $stmt = $pdo->query("SELECT baslik FROM tbl_ayarlar_sikayet_konusu_bilgileri ORDER BY baslik ASC");
    if ($stmt)
        $complaint_topics = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
}
?>
<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Kullanıcı Yönetimi</h1>
        <p class="text-sm text-gray-500 mt-1">Sistem kullanıcılarını ve yetkilerini buradan yönetin</p>
    </div>
    <button onclick="openModal()"
        class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl hover:bg-indigo-700 transition flex items-center gap-2 shadow-sm font-semibold">
        <i class="ph-bold ph-plus"></i> Yeni Kullanıcı
    </button>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100">
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">ID</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Kullanıcı
                        Adı</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Roller /
                        Gruplar</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Telefon 1
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Sensör
                        Kodu</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-widest">İşlemler
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4 text-sm text-gray-500 font-medium">#<?php echo $user['id']; ?></td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                </div>
                                <span
                                    class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                <?php echo htmlspecialchars($user['kullanici_grup_bilgileri'] ?: 'Grup Yok'); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?php echo htmlspecialchars($user['telefon_numarasi'] ?: '-'); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-600 leading-none">
                            <span
                                class="font-mono bg-gray-50 px-2 py-1 rounded border border-gray-200"><?php echo htmlspecialchars($user['sensor_kodu'] ?: '-'); ?></span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button onclick="editUser(<?php echo $user['id']; ?>)"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors"
                                    title="Düzenle">
                                    <i class="ph ph-pencil-simple"></i>
                                </button>
                                <button onclick="deleteUser(<?php echo $user['id']; ?>)"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors"
                                    title="Sil">
                                    <i class="ph ph-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Wrapper -->
    <?php if ($total_pages > 1): ?>
        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
            <p class="text-sm text-gray-500">
                Toplam <span class="font-bold text-gray-900"><?php echo $total_records; ?></span> kayıttan
                <span
                    class="font-bold text-gray-900"><?php echo $offset + 1; ?>-<?php echo min($offset + $records_per_page, $total_records); ?></span>
                arası gösteriliyor
            </p>
            <div class="flex gap-1">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?php echo $current_page - 1; ?>"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition-colors text-sm font-medium flex items-center gap-1">
                        <i class="ph ph-caret-left"></i> Önceki
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $current_page): ?>
                        <span class="px-3.5 py-1.5 rounded-lg bg-indigo-600 text-white text-sm font-bold"><?php echo $i; ?></span>
                    <?php else: ?>
                        <?php if ($i == 1 || $i == $total_pages || ($i >= $current_page - 1 && $i <= $current_page + 1)): ?>
                            <a href="?page=<?php echo $i; ?>"
                                class="px-3.5 py-1.5 rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition-colors text-sm font-medium"><?php echo $i; ?></a>
                        <?php elseif ($i == 2 || $i == $total_pages - 1): ?>
                            <span class="px-2 py-1.5 text-gray-400">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?>"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition-colors text-sm font-medium flex items-center gap-1">
                        Sonraki <i class="ph ph-caret-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
</div>

<!-- User Modal -->
<div id="userModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[95vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center sticky top-0 bg-white z-10">
            <h3 class="text-lg font-bold text-gray-800" id="modalTitle">Yeni Kullanıcı</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>

        <form id="userForm" onsubmit="event.preventDefault(); saveUser();"
            class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <input type="hidden" name="id" id="userId">

            <!-- Basic Info -->
            <div class="col-span-full">
                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Temel Bilgiler</h4>
            </div>

            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700">Kullanıcı Adı</label>
                <input type="text" name="username" id="username" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700">Şifre <span
                        class="text-xs text-gray-400 font-normal">(Boş bırakılırsa değişmez)</span></label>
                <input type="password" name="password" id="password"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700">Sensör Kodu</label>
                <input type="text" name="sensor_kodu" id="sensor_kodu"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <!-- Contact Info -->
            <div class="col-span-full mt-2">
                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">İletişim Bilgileri
                </h4>
            </div>

            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700">Telefon Numarası 1</label>
                <input type="text" name="telefon_numarasi" id="telefon_numarasi"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700">Telefon Numarası 2</label>
                <input type="text" name="telefon_numarasi_2" id="telefon_numarasi_2"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700">Telefon Numarası 3</label>
                <input type="text" name="telefon_numarasi_3" id="telefon_numarasi_3"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <!-- Responsibilities (Multi-Select with Select2) -->
            <div class="col-span-full mt-2">
                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Sorumluluk Alanları
                    (Çoklu Seçim)</h4>
            </div>

            <!-- User Groups -->
            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700">Kullanıcı Grupları</label>
                <select class="select2-multi w-full" name="kullanici_grup_bilgileri[]" multiple="multiple"
                    style="width: 100%;">
                    <?php foreach ($user_groups as $item): ?>
                        <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Hospitals -->
            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700">Sorumlu Olduğu Hastaneler</label>
                <select class="select2-multi w-full" name="sorumlu_oldugu_hastane[]" multiple="multiple"
                    style="width: 100%;">
                    <?php foreach ($hospitals as $item): ?>
                        <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Doctors -->
            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700">Sorumlu Olduğu Doktorlar</label>
                <select class="select2-multi w-full" name="sorumlu_oldugu_doktor[]" multiple="multiple"
                    style="width: 100%;">
                    <?php foreach ($doctors as $item): ?>
                        <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Campaigns -->
            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700">Sorumlu Olduğu Kampanyalar</label>
                <select class="select2-multi w-full" name="sorumlu_oldugu_kampanya[]" multiple="multiple"
                    style="width: 100%;">
                    <?php foreach ($campaigns as $item): ?>
                        <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Complaint Topics -->
            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700">Sorumlu Olduğu Şikayet Konuları</label>
                <select class="select2-multi w-full" name="sorumlu_oldugu_sikayet_konusu[]" multiple="multiple"
                    style="width: 100%;">
                    <?php foreach ($complaint_topics as $item): ?>
                        <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-span-full flex justify-end gap-3 mt-4 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeModal()"
                    class="px-4 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition">İptal</button>
                <button type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function () {
        // Initialize Select2
        $('.select2-multi').select2({
            placeholder: "Seçim yapınız...",
            allowClear: true,
            width: '100%' // Ensure full width
        });
    });

    function openModal() {
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = '';
        document.getElementById('modalTitle').innerText = 'Yeni Kullanıcı';

        // Clear Select2 values
        $('.select2-multi').val(null).trigger('change');

        document.getElementById('userModal').classList.remove('hidden');
        document.getElementById('userModal').classList.add('flex');
    }

    function closeModal() {
        document.getElementById('userModal').classList.add('hidden');
        document.getElementById('userModal').classList.remove('flex');
    }

    function setSelect2Values(name, valueString) {
        console.log(`Setting Select2 for ${name}:`, valueString);
        const $select = $(`select[name="${name}[]"]`);
        if ($select.length === 0) {
            console.warn(`Select element for ${name} not found!`);
            return;
        }

        if (!valueString) {
            $select.val(null).trigger('change');
            return;
        }

        // Split by comma and trim whitespace
        const values = valueString.split(',').map(s => s.trim()).filter(s => s !== '');
        console.log(`Parsed values for ${name}:`, values);

        $select.val(values).trigger('change');
    }

    function editUser(id) {
        fetch('api/get_user.php?id=' + id)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const user = data.user;
                    document.getElementById('userId').value = user.id;
                    document.getElementById('username').value = user.username;
                    document.getElementById('password').value = ''; // Reset password field

                    document.getElementById('telefon_numarasi').value = user.telefon_numarasi || '';
                    document.getElementById('telefon_numarasi_2').value = user.telefon_numarasi_2 || '';
                    document.getElementById('telefon_numarasi_3').value = user.telefon_numarasi_3 || '';
                    document.getElementById('sensor_kodu').value = user.sensor_kodu || '';

                    // Set Select2 values
                    setSelect2Values('kullanici_grup_bilgileri', user.kullanici_grup_bilgileri);
                    setSelect2Values('sorumlu_oldugu_hastane', user.sorumlu_oldugu_hastane);
                    setSelect2Values('sorumlu_oldugu_doktor', user.sorumlu_oldugu_doktor);
                    setSelect2Values('sorumlu_oldugu_kampanya', user.sorumlu_oldugu_kampanya);
                    setSelect2Values('sorumlu_oldugu_sikayet_konusu', user.sorumlu_oldugu_sikayet_konusu);

                    document.getElementById('modalTitle').innerText = 'Kullanıcı Düzenle';
                    document.getElementById('userModal').classList.remove('hidden');
                    document.getElementById('userModal').classList.add('flex');
                } else {
                    alert('Kullanıcı bilgileri alınamadı.');
                }
            });
    }

    function saveUser() {
        const form = document.getElementById('userForm');
        const formData = new FormData(form);

        fetch('api/save_user.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Kullanıcı başarıyla kaydedildi.');
                    location.reload();
                } else {
                    alert('Hata: ' + data.message);
                }
            });
    }

    function deleteUser(id) {
        if (!confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')) return;

        fetch('api/delete_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Hata: ' + data.message);
                }
            });
    }
</script>
<?php include 'layout_footer.php'; ?>