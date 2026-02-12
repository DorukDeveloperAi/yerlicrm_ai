<?php
$pageTitle = 'Panel - YerliCRM';
$activePage = 'dashboard';
include 'layout_header.php';

// Fetch summary stats
$total_records = $pdo->query("SELECT count(DISTINCT telefon_numarasi) FROM tbl_icerik_bilgileri_ai")->fetchColumn();
?>

<div class="welcome-card mb-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">CRM Paneline Hoş Geldiniz</h1>
    <p class="text-gray-500">Verilerinizi yönetmek ve analiz etmek için sol menüdeki araçları kullanabilirsiniz.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
                <i class="ph-bold ph-users-three text-2xl"></i>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Toplam İçerik</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_records, 0, ',', '.'); ?>
                </p>
            </div>
        </div>
        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
            <div class="bg-indigo-500 h-full w-2/3"></div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center">
                <i class="ph-bold ph-broadcast text-2xl"></i>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Sistem Durumu</h3>
                <p class="text-2xl font-bold text-emerald-600">Aktif</p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-sm text-emerald-600 font-medium">
            <i class="ph-bold ph-check-circle"></i>
            <span>Tüm servisler normal çalışıyor</span>
        </div>
    </div>
</div>

<?php include 'layout_footer.php'; ?>