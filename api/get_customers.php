<?php
require_once '../auth.php';
requireLogin();

// Disable error display for JSON response consistency
ini_set('display_errors', 0);
error_reporting(E_ALL);

$limit = 50;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = $page < 1 ? 1 : $page;
$offset = ($page - 1) * $limit;

$status_filter = $_GET['status'] ?? 'all';
$personnel_filter = $_GET['personnel'] ?? 'all';
$campaign_filter = $_GET['campaign'] ?? 'all';
$search_query = $_GET['search'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$where_clauses = ["1=1"];
$params = [];

if (!empty($start_date)) {
    $where_clauses[] = "DATE(t1.basvuru_tarihi) >= ?";
    $params[] = $start_date;
}
if (!empty($end_date)) {
    $where_clauses[] = "DATE(t1.basvuru_tarihi) <= ?";
    $params[] = $end_date;
}

if (empty($search_query)) {
    if ($status_filter === 'empty') {
        $where_clauses[] = "t1.son_mesaj_yeri = 'musteri_mesaji'";
    } elseif ($status_filter !== 'all') {
        $where_clauses[] = "t1.gorusme_sonucu_text = ?";
        $params[] = $status_filter;
    }
}

if ($personnel_filter !== 'all') {
    $where_clauses[] = "t1.satis_temsilcisi = ?";
    $params[] = (int) $personnel_filter;
}

if ($campaign_filter !== 'all') {
    $where_clauses[] = "t1.kampanya = ?";
    $params[] = $campaign_filter;
}

if (!empty($search_query)) {
    $where_clauses[] = "(t1.musteri_adi_soyadi LIKE ? OR t1.telefon_numarasi LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

try {
    $where_sql = implode(" AND ", $where_clauses);

    // Count total unique customers satisfying filters
    $count_query = "SELECT COUNT(*) FROM icerik_bilgileri t1 WHERE $where_sql";
    $stmt_count = $pdo->prepare($count_query);
    $stmt_count->execute($params);
    $total_unique = $stmt_count->fetchColumn();

    $total_pages = ceil($total_unique / $limit);

    // Sort Logic: "Yeni" tab (empty) sorts by application date, others by last transaction
    $order_by = ($status_filter === 'empty') ? "t1.basvuru_tarihi DESC" : "t1.son_islem_tarihi DESC";

    // Data Query
    $query = "
        SELECT t1.*, 
               t1.basvuru_tarihi as first_date,
               t1.son_islem_tarihi as date,
               COALESCE(u.username, t1.satis_temsilcisi) as rep_name
        FROM icerik_bilgileri t1
        LEFT JOIN users u ON t1.satis_temsilcisi = CAST(u.id AS CHAR) COLLATE utf8mb4_unicode_ci
        WHERE $where_sql
        ORDER BY $order_by 
        LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
} catch (Throwable $e) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Sorgu hatası: ' . $e->getMessage()]));
}

// Fallback for mbstring if missing
if (!function_exists('mb_substr')) {
    function mb_substr($str, $start, $len = null, $encoding = null)
    {
        return ($len === null) ? substr($str, $start) : substr($str, $start, $len);
    }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper($str, $encoding = null)
    {
        return strtoupper($str);
    }
}

// HTML Output Generation
ob_start();
?>
<div class="contact-list-inner">
    <?php foreach ($customers as $c): ?>
        <?php
        $formatDate = function ($val) {
            if (!$val)
                return '-';
            if (is_numeric($val))
                return date('d/m/Y H:i', $val);
            $ts = strtotime($val);
            return $ts ? date('d/m/Y H:i', $ts) : '-';
        };
        $getInitials = function ($name) {
            if (!$name)
                return '?';
            $parts = explode(' ', $name);
            $initials = '';
            foreach ($parts as $p) {
                if (!empty($p))
                    $initials .= mb_substr($p, 0, 1, 'UTF-8');
            }
            return mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'), 'UTF-8');
        };
        $snippet = !empty($c['son_mesaj']) ? $c['son_mesaj'] : '';
        $last_date = $formatDate($c['date']);
        $first_date = $formatDate($c['first_date']);
        $access_date = $formatDate($c['ilk_erisim_tarihi']);
        ?>
        <div class="contact-item <?php echo ($c['son_mesaj_yeri'] === 'musteri_mesaji') ? 'has-unread' : ''; ?>"
            onclick="loadConversation('<?php echo $c['telefon_numarasi']; ?>', '<?php echo addslashes($c['musteri_adi_soyadi'] ?: 'Bilinmiyor'); ?>', '<?php echo addslashes($c['kampanya'] ?: 'Kampanya Yok'); ?>', this)">
            <div class="contact-item-main">
                <div class="profile-circle"
                    data-name="<?php echo htmlspecialchars($c['musteri_adi_soyadi'] ?: 'Bilinmiyor'); ?>">
                    <?php echo $getInitials($c['musteri_adi_soyadi'] ?: 'Bilinmiyor'); ?>
                </div>
                <div class="contact-details">
                    <div class="contact-top-row">
                        <span class="contact-name">
                            <?php echo htmlspecialchars($c['musteri_adi_soyadi'] ?: 'Bilinmiyor'); ?>
                        </span>
                        <span class="contact-time" title="Son Etkileşim">
                            <?php echo $last_date; ?>
                        </span>
                    </div>
                    <div class="contact-mid-row">
                        <span class="contact-snippet">
                            <?php echo htmlspecialchars($snippet); ?>
                        </span>
                    </div>
                    <div class="contact-bottom-row">
                        <span class="contact-rep">
                            <i class="ph ph-user"></i> <?php echo htmlspecialchars($c['rep_name'] ?: 'Atanmamış'); ?>
                        </span>
                        <span class="contact-campaign">
                            <i class="ph ph-tag"></i> <?php echo htmlspecialchars($c['kampanya'] ?: 'Genel'); ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php if ($c['son_mesaj_yeri'] === 'musteri_mesaji'): ?>
                <div class="unread-badge"></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php
$html = ob_get_clean();

// Pagination HTML Generation
ob_start();
?>
<?php
// First and Back
$first_disabled = ($page <= 1) ? 'disabled' : '';
$first_onclick = ($page <= 1) ? 'void(0)' : 'goToPage(1)';
$back_onclick = ($page <= 1) ? 'void(0)' : 'goToPage(' . ($page - 1) . ')';
?>
<a href="javascript:<?php echo $first_onclick; ?>" class="btn-page <?php echo $first_disabled; ?>">İlk</a>
<a href="javascript:<?php echo $back_onclick; ?>" class="btn-page <?php echo $first_disabled; ?>">Geri</a>

<?php
$start_range = max(1, $page - 2);
$end_range = min($total_pages, $page + 2);

if ($start_range > 1)
    echo '<span class="pagination-dots">...</span>';

for ($i = $start_range; $i <= $end_range; $i++): ?>
    <a href="javascript:void(0)" onclick="goToPage(<?php echo $i; ?>)"
        class="btn-page <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
<?php endfor; ?>

<?php if ($end_range < $total_pages)
    echo '<span class="pagination-dots">...</span>'; ?>

<?php
// Next and Last
$last_disabled = ($page >= $total_pages) ? 'disabled' : '';
$next_onclick = ($page >= $total_pages) ? 'void(0)' : 'goToPage(' . ($page + 1) . ')';
$last_onclick = ($page >= $total_pages) ? 'void(0)' : 'goToPage(' . $total_pages . ')';
?>
<a href="javascript:<?php echo $next_onclick; ?>" class="btn-page <?php echo $last_disabled; ?>">İleri</a>
<a href="javascript:<?php echo $last_onclick; ?>" class="btn-page <?php echo $last_disabled; ?>">Son</a>
<?php
$pagination_html = ob_get_clean();

$start = $total_unique > 0 ? $offset + 1 : 0;
$end = min($offset + $limit, $total_unique);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'html' => $html,
    'pagination_html' => $pagination_html,
    'page' => $page,
    'total_pages' => $total_pages,
    'total_records' => $total_unique,
    'start' => $start,
    'end' => $end
]);
?>