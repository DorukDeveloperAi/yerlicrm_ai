<?php
$pageTitle = 'GupShup Hesapları';
$activePage = 'gupshup_accounts';
require_once 'layout_header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">GupShup Hesapları</h1>
        <p class="text-gray-500 text-sm">WhatsApp şablonları için farklı GupShup hesaplarını yönetin.</p>
    </div>
    <button onclick="openModal()" class="btn-primary flex items-center gap-2">
        <i class="ph-bold ph-plus"></i> Yeni Hesap Ekle
    </button>
</div>

<!-- Accounts List -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-left border-collapse">
        <thead class="bg-gray-50 border-bottom border-gray-100">
            <tr>
                <th class="px-6 py-4 text-sm font-semibold text-gray-600">Hesap Adı</th>
                <th class="px-6 py-4 text-sm font-semibold text-gray-600">Telefon Numarası</th>
                <th class="px-6 py-4 text-sm font-semibold text-gray-600">App Name</th>
                <th class="px-6 py-4 text-sm font-semibold text-gray-600">İşlemler</th>
            </tr>
        </thead>
        <tbody id="accountsList" class="divide-y divide-gray-100">
            <!-- Loaded via JS -->
        </tbody>
    </table>
</div>

<!-- Account Modal -->
<div id="accountModal"
    class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[2000] items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800" id="modalTitle">Yeni Hesap Ekle</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>
        <form id="accountForm" onsubmit="saveAccount(event)" class="p-6 space-y-4">
            <input type="hidden" name="id" id="accountId">

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Hesap Takma Adı</label>
                <input type="text" name="name" id="accountName" required
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                    placeholder="Örn: YerliCRM Ana Hesap">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Source Telefon Numarası</label>
                <input type="text" name="phone_number" id="accountPhone" required
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                    placeholder="Örn: 905551234567">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">GupShup API Key</label>
                <input type="text" name="api_key" id="accountApiKey" required
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                    placeholder="API anahtarınızı buraya yapıştırın">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">App Name</label>
                <input type="text" name="app_name" id="accountAppName" required
                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                    placeholder="GupShup üzerindeki uygulama adınız">
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" onclick="closeModal()"
                    class="flex-1 px-4 py-2 border border-gray-200 text-gray-600 font-semibold rounded-xl hover:bg-gray-50 transition-all">
                    İptal
                </button>
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all">
                    Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let accounts = [];

    function loadAccounts() {
        fetch('api/get_gupshup_accounts.php')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    accounts = data.accounts;
                    renderAccounts();
                }
            });
    }

    function renderAccounts() {
        const list = document.getElementById('accountsList');
        if (accounts.length === 0) {
            list.innerHTML = `
            <tr>
                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                    Henüz bir GupShup hesabı eklenmemiş.
                </td>
            </tr>
        `;
            return;
        }

        list.innerHTML = accounts.map(acc => `
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 font-medium text-gray-800">${acc.name}</td>
            <td class="px-6 py-4 text-gray-600 font-mono text-sm">${acc.phone_number}</td>
            <td class="px-6 py-4 text-gray-600">${acc.app_name}</td>
            <td class="px-6 py-4">
                <div class="flex gap-2">
                    <button onclick="editAccount(${acc.id})" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all" title="Düzenle">
                        <i class="ph ph-pencil-simple text-lg"></i>
                    </button>
                    <button onclick="deleteAccount(${acc.id})" class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition-all" title="Sil">
                        <i class="ph ph-trash text-lg"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    }

    function openModal(id = null) {
        const modal = document.getElementById('accountModal');
        const form = document.getElementById('accountForm');
        const title = document.getElementById('modalTitle');

        form.reset();
        document.getElementById('accountId').value = '';

        if (id) {
            const acc = accounts.find(a => a.id == id);
            if (acc) {
                title.innerText = 'Hesabı Düzenle';
                document.getElementById('accountId').value = acc.id;
                document.getElementById('accountName').value = acc.name;
                document.getElementById('accountPhone').value = acc.phone_number;
                document.getElementById('accountApiKey').value = acc.api_key;
                document.getElementById('accountAppName').value = acc.app_name;
            }
        } else {
            title.innerText = 'Yeni Hesap Ekle';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        const modal = document.getElementById('accountModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function editAccount(id) {
        openModal(id);
    }

    function saveAccount(e) {
        e.preventDefault();
        const formData = new FormData(e.target);

        fetch('api/save_gupshup_account.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeModal();
                    loadAccounts();
                } else {
                    alert(data.message);
                }
            });
    }

    function deleteAccount(id) {
        if (!confirm('Bu hesabı silmek istediğinize emin misiniz? Bu hesaba bağlı şablonlar gönderilemeyebilir.')) return;

        const formData = new FormData();
        formData.append('id', id);

        fetch('api/delete_gupshup_account.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    loadAccounts();
                } else {
                    alert(data.message);
                }
            });
    }

    loadAccounts();
</script>

<?php require_once 'layout_footer.php'; ?>