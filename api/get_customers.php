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

$where_clauses = ["1=1"];
$params = [];

if ($status_filter === 'empty') {
    $where_clauses[] = "(gorusme_sonucu_text IS NULL OR gorusme_sonucu_text = '' OR gorusme_sonucu_text = 'Yönlendirildi')";
} elseif ($status_filter !== 'all') {
    $where_clauses[] = "gorusme_sonucu_text = ?";
    $params[] = $status_filter;
}

if ($personnel_filter !== 'all') {
    $where_clauses[] = "user_id = ?";
    $params[] = (int) $personnel_filter;
}

if ($campaign_filter !== 'all') {
    $where_clauses[] = "kampanya = ?";
    $params[] = $campaign_filter;
}

if (!empty($search_query)) {
    $where_clauses[] = "(musteri_adi_soyadi LIKE ? OR telefon_numarasi LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

try {
    $where_sql = implode(" AND ", $where_clauses);
    $where_sql_aliased = implode(" AND ", array_map(function ($c) {
        // Replace column names with aliased versions for the outer WHERE clause
        return str_replace(
            ['musteri_adi_soyadi', 'telefon_numarasi', 'user_id', 'kampanya', 'gorusme_sonucu_text'],
            ['t1.musteri_adi_soyadi', 't1.telefon_numarasi', 't1.user_id', 't1.kampanya', 't1.gorusme_sonucu_text'],
            $c
        );
    }, $where_clauses));

    // Count total unique customers satisfying filters on their LATEST record
    // Pre-filter inside subquery if searching to drastically reduce working set
    $sub_where = $where_sql; // Subquery operates on tbl_icerik_bilgileri_ai directly, no alias needed here
    $sub_params = $params;

    $count_query = "
        SELECT COUNT(*) 
        FROM tbl_icerik_bilgileri_ai t1
        JOIN (
            SELECT MAX(id) as last_id 
            FROM tbl_icerik_bilgileri_ai 
            WHERE $sub_where
            GROUP BY telefon_numarasi
        ) t2 ON t1.id = t2.last_id
        WHERE $where_sql_aliased"; // Use aliased WHERE for the outer query
    $stmt_count = $pdo->prepare($count_query);
    $stmt_count->execute(array_merge($sub_params, $params));
    $total_unique = $stmt_count->fetchColumn();

    $total_pages = ceil($total_unique / $limit);

    // Data Query using JOIN with GROUP BY
    // This fetches first_date and last_id in one scan
    $query = "
        SELECT t1.*, 
               t2.first_date,
               u.username as rep_name
        FROM tbl_icerik_bilgileri_ai t1
        JOIN (
            SELECT MIN(date) as first_date, MAX(id) as last_id 
            FROM tbl_icerik_bilgileri_ai 
            WHERE $sub_where
            GROUP BY telefon_numarasi
        ) t2 ON t1.id = t2.last_id
        LEFT JOIN users u ON t1.user_id = u.id
        WHERE $where_sql_aliased"; // Use aliased WHERE for the outer query

    $query .= " ORDER BY t1.date DESC LIMIT $limit OFFSET $offset";

    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge($sub_params, $params));
    $customers = $stmt->fetchAll();
} catch (Exception $e) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Sorgu hatası: ' . $e->getMessage()]));
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
        $snippet = !empty($c['musteri_mesaji']) ? $c['musteri_mesaji'] : $c['personel_mesaji'];
        $last_date = $formatDate($c['date']);
        $first_date = $formatDate($c['first_date']);
        $access_date = $formatDate($c['ilk_erisim_tarihi']);
        ?>
        <div class="contact-item <?php echo !empty($c['musteri_mesaji']) ? 'has-unread' : ''; ?>"
            onclick="loadConversation('<?php echo $c['telefon_numarasi']; ?>', '<?php echo addslashes($c['musteri_adi_soyadi'] ?: 'İsimsiz'); ?>', '<?php echo addslashes($c['kampanya'] ?: 'Kampanya Yok'); ?>', this)">
            <div class="contact-item-main">
                <div class="profile-circle" data-name="<?php echo htmlspecialchars($c['musteri_adi_soyadi']); ?>">
                    <?php echo $getInitials($c['musteri_adi_soyadi']); ?>
                </div>
                <div class="contact-details">
                    <div class="contact-top-row">
                        <span class="contact-name">
                            <?php echo htmlspecialchars($c['musteri_adi_soyadi'] ?: 'İsimsiz Müşteri'); ?>
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
            <?php if (!empty($c['musteri_mesaji'])): ?>
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