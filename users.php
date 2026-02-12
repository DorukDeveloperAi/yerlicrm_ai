<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();

// Fetch summary statsers
$users = $pdo->query("SELECT * FROM users WHERE status = 1 ORDER BY id ASC")->fetchAll();

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
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi - YerliCRM</title>
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/main.css">
    <script src="https://unpkg.com/phosphor-icons"></script>

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- jQuery (Required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        .user-table th,
        .user-table td {
            text-align: left;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .user-table th {
            background-color: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .user-table td {
            color: #334155;
            font-size: 0.9rem;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .btn-edit {
            background-color: #eff6ff;
            color: #3b82f6;
        }

        .btn-edit:hover {
            background-color: #dbeafe;
        }

        .btn-delete {
            background-color: #fef2f2;
            color: #ef4444;
        }

        .btn-delete:hover {
            background-color: #fee2e2;
        }

        /* Select2 Custom Styling to match Tailwind */
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            min-height: 42px;
            padding: 4px;
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            border-radius: 0.25rem;
            padding: 2px 8px;
            margin-top: 4px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #1e40af;
            margin-right: 6px;
            border-right: 1px solid #bfdbfe;
        }

        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: #3b82f6;
            color: white;
        }

        /* Fix modal z-index issue with Select2 */
        .select2-container {
            z-index: 9999;
        }

        .select2-dropdown {
            z-index: 99999;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen p-6">

    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Kullanıcı Yönetimi</h1>
            <div class="flex gap-2">
                <a href="index.php"
                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">Geri Dön</a>
                <button onclick="openModal()"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                    <i class="ph ph-plus"></i> Yeni Kullanıcı
                </button>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full user-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kullanıcı Adı</th>
                            <th>Roller / Gruplar</th>
                            <th>Telefon 1</th>
                            <th>Sensör Kodu</th>
                            <th class="text-right">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>#
                                    <?php echo $user['id']; ?>
                                </td>
                                <td class="font-medium">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($user['kullanici_grup_bilgileri'] ?: '-'); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($user['telefon_numarasi'] ?: '-'); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($user['sensor_kodu'] ?: '-'); ?>
                                </td>
                                <td class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <button onclick="editUser(<?php echo $user['id']; ?>)" class="btn-action btn-edit"
                                            title="Düzenle">
                                            <i class="ph ph-pencil-simple"></i>
                                        </button>
                                        <button onclick="deleteUser(<?php echo $user['id']; ?>)"
                                            class="btn-action btn-delete" title="Sil">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
</body>

</html>