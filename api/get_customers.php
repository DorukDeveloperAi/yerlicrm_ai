<?php
require_once '../auth.php';
requireLogin();

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

$where_sql = implode(" AND ", $where_clauses);

// Count total unique customers satisfying filters on their LATEST record
// Using JOIN with GROUP BY for better general performance on 750k+ records
$count_query = "
    SELECT COUNT(*) 
    FROM tbl_icerik_bilgileri_ai t1
    JOIN (
        SELECT MAX(id) as last_id 
        FROM tbl_icerik_bilgileri_ai 
        GROUP BY telefon_numarasi
    ) t2 ON t1.id = t2.last_id
    WHERE $where_sql";
$stmt_count = $pdo->prepare($count_query);
$stmt_count->execute($params);
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
        GROUP BY telefon_numarasi
    ) t2 ON t1.id = t2.last_id
    LEFT JOIN users u ON t1.user_id = u.id
    WHERE $where_sql";

$query .= " ORDER BY t1.date DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll();

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
        <div class="col-avatar">
            <?php echo $getInitials($c['musteri_adi_soyadi']); ?>
        </div>
        <div class="col-info">
            <div class="contact-details-wrapper">
                <span class="contact-name">
                    <?php echo htmlspecialchars($c['musteri_adi_soyadi'] ?: 'İsimsiz Müşteri'); ?>
                </span>
                <span class="rep-name">
                    <?php echo htmlspecialchars($c['rep_name'] ?: 'Atanmamış'); ?>
                </span>
            </div>
            <div class="col-msg">
                <?php echo htmlspecialchars($snippet); ?>
            </div>
        </div>
        <div class="col-meta">
            <div class="meta-row">
                <span class="meta-date" title="Son İşlem">
                    <?php echo $last_date; ?>
                </span>
            </div>
            <div class="meta-row">
                <span class="meta-campaign">
                    <?php echo htmlspecialchars($c['kampanya'] ?: '-'); ?>
                </span>
            </div>
            <?php if (!empty($c['musteri_mesaji'])): ?>
                <div class="status-icon" title="Yeni Müşteri Mesajı">

                    <i class="ph ph-chat-circle-dots"></i>
                </div>
            <?php endif; ?>
        </div>
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