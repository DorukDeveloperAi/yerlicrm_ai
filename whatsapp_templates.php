<?php
$pageTitle = 'WhatsApp Şablon Yönetimi - YerliCRM';
$activePage = 'whatsapp_templates';
include 'layout_header.php';

require_once 'config.php';
require_once 'auth.php';
requireLogin();

// Pagination settings
$records_per_page = 20;
$current_page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Fetch total records for pagination
$total_stmt = $pdo->query("SELECT COUNT(*) FROM whatsapp_gupshup_templates WHERE status = 1");
$total_records = $total_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Fetch templates for current page
$stmt = $pdo->prepare("SELECT * FROM whatsapp_gupshup_templates WHERE status = 1 ORDER BY title ASC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$templates = $stmt->fetchAll();
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">WhatsApp Şablon Yönetimi</h1>
        <p class="text-sm text-gray-500 mt-1">Chat ekranında kullanılacak hazır mesaj şablonlarını yönetin</p>
    </div>
    <button onclick="openTemplateModal()"
        class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl hover:bg-indigo-700 transition flex items-center gap-2 shadow-sm font-semibold whitespace-nowrap">
        <i class="ph-bold ph-plus"></i> Yeni Şablon Ekle
    </button>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100">
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">ID</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Görsel
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">GupShup ID
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Kaynak No
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Şablon
                        Başlığı</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Oluşturma
                    </th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-widest">İşlemler
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($templates)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="ph ph-chat-circle-dots text-4xl mb-3 opacity-20 block"></i>
                            Henüz şablon eklenmemiş.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($templates as $t): ?>
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4 text-sm text-gray-500 font-medium">#<?php echo $t['id']; ?></td>
                        <td class="px-6 py-4">
                            <?php if ($t['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($t['image_url']); ?>"
                                    class="w-10 h-10 rounded object-cover border border-gray-200"
                                    onerror="this.src='https://placehold.co/40?text=%3F'">
                            <?php else: ?>
                                <div class="w-10 h-10 rounded bg-gray-100 flex items-center justify-center text-gray-400">
                                    <i class="ph ph-image-square"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs font-mono bg-indigo-50 text-indigo-700 px-2 py-1 rounded">
                                <?php echo htmlspecialchars($t['gupshup_id'] ?: '-'); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-600">
                            <?php echo htmlspecialchars($t['source_number'] ?: '-'); ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($t['title']); ?>
                            </div>
                            <div class="text-xs text-gray-400 truncate max-w-xs">
                                <?php echo htmlspecialchars($t['content']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?php echo date('d.m.Y H:i', strtotime($t['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button onclick="editTemplate(<?php echo $t['id']; ?>)"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors"
                                    title="Düzenle">
                                    <i class="ph ph-pencil-simple"></i>
                                </button>
                                <button onclick="deleteTemplate(<?php echo $t['id']; ?>)"
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

<!-- Template Modal -->
<div id="templateModal"
    class="fixed inset-0 bg-black/50 hidden z-[1100] flex justify-center items-start overflow-y-auto p-4 sm:pt-16 sm:pb-10">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[80vh]">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white">
            <h3 class="text-lg font-bold text-gray-800 truncate pr-4" id="modalTitle">Yeni Şablon</h3>
            <button onclick="closeTemplateModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="ph-bold ph-x text-xl"></i>
            </button>
        </div>

        <form id="templateForm" onsubmit="event.preventDefault(); saveTemplate();"
            class="p-6 space-y-6 overflow-y-auto flex-1">
            <input type="hidden" name="id" id="templateId">

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="text-sm font-bold text-gray-700">GupShup Template ID</label>
                    <input type="text" name="gupshup_id" id="templateGupShupId" placeholder="Örn: welcome_msg_01"
                        class="w-full border-1.5 border-gray-300 rounded-xl px-4 py-3 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-600 outline-none transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-bold text-gray-700">Kaynak Numara</label>
                    <input type="text" name="source_number" id="templateSourceNumber" placeholder="90850..."
                        class="w-full border-1.5 border-gray-300 rounded-xl px-4 py-3 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-600 outline-none transition-all">
                </div>
            </div>
            <p class="text-xs text-gray-400">GupShup panelindeki benzersiz şablon ismi ve bu şablonun bağlı olduğu
                kaynak numara.</p>

            <div class="space-y-2">
                <label class="text-sm font-bold text-gray-700">Görsel URL (Opsiyonel)</label>
                <input type="text" name="image_url" id="templateImageUrl" placeholder="https://domain.com/image.jpg"
                    class="w-full border-1.5 border-gray-300 rounded-xl px-4 py-3 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-600 outline-none transition-all">
                <p class="text-xs text-gray-400">Media şablonları için başlık görseli URL'ini buraya yazın.</p>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-bold text-gray-700">Şablon Başlığı</label>
                <input type="text" name="title" id="templateTitle" required placeholder="Örn: Selamlama Mesajı"
                    class="w-full border-1.5 border-gray-300 rounded-xl px-4 py-3 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-600 outline-none transition-all">
                <p class="text-xs text-gray-400">Şablonu kolayca tanıyabilmeniz için bir başlık verin.</p>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-bold text-gray-700">Mesaj İçeriği</label>
                <textarea name="content" id="templateContent" required rows="6"
                    placeholder="WhatsApp mesaj içeriğini buraya yazın..."
                    class="w-full border-1.5 border-gray-300 rounded-xl px-4 py-3 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-600 outline-none transition-all resize-none"></textarea>
                <p class="text-xs text-gray-400">Not: {{name}} gibi değişkenler şu an desteklenmemektedir, düz metin
                    kullanın.</p>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeTemplateModal()"
                    class="px-6 py-2.5 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition font-semibold">İptal</button>
                <button type="submit"
                    class="px-8 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition font-bold shadow-sm">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openTemplateModal() {
        document.getElementById('templateForm').reset();
        document.getElementById('templateId').value = '';
        document.getElementById('modalTitle').innerText = 'Yeni Şablon Ekle';
        document.getElementById('templateModal').classList.remove('hidden');
        document.getElementById('templateModal').classList.add('flex');
    }

    function closeTemplateModal() {
        document.getElementById('templateModal').classList.add('hidden');
        document.getElementById('templateModal').classList.remove('flex');
    }

    function editTemplate(id) {
        fetch('api/get_template.php?id=' + id)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const t = data.template;
                    document.getElementById('templateId').value = t.id;
                    document.getElementById('templateGupShupId').value = t.gupshup_id || '';
                    document.getElementById('templateSourceNumber').value = t.source_number || '';
                    document.getElementById('templateImageUrl').value = t.image_url || '';
                    document.getElementById('templateTitle').value = t.title;
                    document.getElementById('templateContent').value = t.content;
                    document.getElementById('modalTitle').innerText = 'Şablonu Düzenle';
                    document.getElementById('templateModal').classList.remove('hidden');
                    document.getElementById('templateModal').classList.add('flex');
                } else {
                    alert('Şablon bilgileri alınamadı.');
                }
            });
    }

    function saveTemplate() {
        const form = document.getElementById('templateForm');
        const formData = new FormData(form);

        fetch('api/save_template.php', {
            method: 'POST',
            body: formData
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

    function deleteTemplate(id) {
        if (!confirm('Bu şablonu silmek istediğinize emin misiniz?')) return;

        fetch('api/delete_template.php', {
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