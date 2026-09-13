<?php
require_once __DIR__ . '/../includes/config.php';

$db = getDB();
$from = $_POST['from_date'] ?? date('Y-m-01');
$to = $_POST['to_date'] ?? date('Y-m-d');
$page = (int)($_POST['page'] ?? 1);
$search = $_POST['search'] ?? '';
$sort = $_POST['sort'] ?? 'newest';
$limit = 10;
$offset = ($page - 1) * $limit;

// ==================== 1. KPI ====================

// Doanh thu - chỉ tính đơn hoan_thanh
$stmt = $db->prepare("SELECT COALESCE(SUM(tong_thanh_toan),0) as total 
                      FROM donhang 
                      WHERE trang_thai = 'hoan_thanh' 
                      AND DATE(ngay_dat) BETWEEN :from AND :to");
$stmt->execute([':from' => $from, ':to' => $to]);
$doanhthu = $stmt->fetch()['total'];

// Tổng đơn hàng
$stmt = $db->prepare("SELECT COUNT(*) as total FROM donhang WHERE DATE(ngay_dat) BETWEEN :from AND :to");
$stmt->execute([':from' => $from, ':to' => $to]);
$tongdon = $stmt->fetch()['total'];

// Đơn chờ xác nhận (cho_xac_nhan)
$stmt = $db->prepare("SELECT COUNT(*) as total 
                      FROM donhang 
                      WHERE trang_thai = 'cho_xac_nhan' 
                      AND DATE(ngay_dat) BETWEEN :from AND :to");
$stmt->execute([':from' => $from, ':to' => $to]);
$don_chua_xuly = $stmt->fetch()['total'];

// Đơn hủy (da_huy)
$stmt = $db->prepare("SELECT COUNT(*) as total 
                      FROM donhang 
                      WHERE trang_thai = 'da_huy' 
                      AND DATE(ngay_dat) BETWEEN :from AND :to");
$stmt->execute([':from' => $from, ':to' => $to]);
$don_huy = $stmt->fetch()['total'];

// Tỷ lệ hủy
$tyle_huy = $tongdon > 0 ? round(($don_huy / $tongdon) * 100, 1) : 0;

// Khách hàng (vai_tro = 0)
$stmt = $db->prepare("SELECT COUNT(*) as total 
                      FROM users 
                      WHERE vai_tro = 0 
                      AND DATE(ngay_tao) BETWEEN :from AND :to");
$stmt->execute([':from' => $from, ':to' => $to]);
$khach_moi = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE vai_tro = 0");
$stmt->execute();
$total_khach = $stmt->fetch()['total'];

// ==================== SO SÁNH KỲ TRƯỚC ====================
$prev_from = date('Y-m-d', strtotime($from . ' -1 month'));
$prev_to = date('Y-m-d', strtotime($to . ' -1 month'));

// Doanh thu kỳ trước
$stmt = $db->prepare("SELECT COALESCE(SUM(tong_thanh_toan),0) as total 
                      FROM donhang 
                      WHERE trang_thai = 'hoan_thanh' 
                      AND DATE(ngay_dat) BETWEEN :from AND :to");
$stmt->execute([':from' => $prev_from, ':to' => $prev_to]);
$prev_doanhthu = $stmt->fetch()['total'];

// Đơn hàng kỳ trước
$stmt = $db->prepare("SELECT COUNT(*) as total FROM donhang WHERE DATE(ngay_dat) BETWEEN :from AND :to");
$stmt->execute([':from' => $prev_from, ':to' => $prev_to]);
$prev_tongdon = $stmt->fetch()['total'];

// Khách mới kỳ trước
$stmt = $db->prepare("SELECT COUNT(*) as total 
                      FROM users 
                      WHERE vai_tro = 0 
                      AND DATE(ngay_tao) BETWEEN :from AND :to");
$stmt->execute([':from' => $prev_from, ':to' => $prev_to]);
$prev_khach_moi = $stmt->fetch()['total'];

// Đơn hủy kỳ trước
$stmt = $db->prepare("SELECT COUNT(*) as total 
                      FROM donhang 
                      WHERE trang_thai = 'da_huy' 
                      AND DATE(ngay_dat) BETWEEN :from AND :to");
$stmt->execute([':from' => $prev_from, ':to' => $prev_to]);
$prev_don_huy = $stmt->fetch()['total'];
$prev_tyle_huy = $prev_tongdon > 0 ? round(($prev_don_huy / $prev_tongdon) * 100, 1) : 0;

// Tính phần trăm thay đổi
$doanhthu_percent = $prev_doanhthu > 0 ? round((($doanhthu - $prev_doanhthu) / $prev_doanhthu) * 100, 1) : ($doanhthu > 0 ? 100 : 0);
$tongdon_percent = $prev_tongdon > 0 ? round((($tongdon - $prev_tongdon) / $prev_tongdon) * 100, 1) : ($tongdon > 0 ? 100 : 0);
$khachmoi_percent = $prev_khach_moi > 0 ? round((($khach_moi - $prev_khach_moi) / $prev_khach_moi) * 100, 1) : ($khach_moi > 0 ? 100 : 0);
$tylehuy_percent = $prev_tyle_huy > 0 ? round((($tyle_huy - $prev_tyle_huy) / $prev_tyle_huy) * 100, 1) : 0;

$kpi = [
    'doanhthu' => formatPrice($doanhthu),
    'tongdon' => number_format($tongdon, 0, ',', '.'),
    'don_chua_xuly' => $don_chua_xuly,
    'khachhang' => number_format($total_khach, 0, ',', '.'),
    'khach_moi' => $khach_moi,
    'tylehuy' => $tyle_huy,
    'don_huy' => $don_huy,
    'sosanh_percent' => abs($doanhthu_percent),
    'sosanh_class' => $doanhthu_percent >= 0 ? 'comparison-up' : 'comparison-down'
];

// ==================== SO SÁNH KỲ TRƯỚC HTML ====================
$comparison_html = '
<div class="mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span><i class="fas fa-chart-line me-2 text-success"></i>Doanh thu</span>
        <span class="' . ($doanhthu_percent >= 0 ? 'text-success' : 'text-danger') . ' fw-bold">
            ' . ($doanhthu_percent >= 0 ? '↑' : '↓') . ' ' . abs($doanhthu_percent) . '%
        </span>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span><i class="fas fa-truck me-2 text-primary"></i>Đơn hàng</span>
        <span class="' . ($tongdon_percent >= 0 ? 'text-success' : 'text-danger') . ' fw-bold">
            ' . ($tongdon_percent >= 0 ? '↑' : '↓') . ' ' . abs($tongdon_percent) . '%
        </span>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span><i class="fas fa-user-plus me-2 text-warning"></i>Khách mới</span>
        <span class="' . ($khachmoi_percent >= 0 ? 'text-success' : 'text-danger') . ' fw-bold">
            ' . ($khachmoi_percent >= 0 ? '↑' : '↓') . ' ' . abs($khachmoi_percent) . '%
        </span>
    </div>
    <div class="d-flex justify-content-between align-items-center">
        <span><i class="fas fa-ban me-2 text-danger"></i>Tỷ lệ hủy</span>
        <span class="' . ($tylehuy_percent <= 0 ? 'text-success' : 'text-danger') . ' fw-bold">
            ' . ($tylehuy_percent <= 0 ? '↓' : '↑') . ' ' . abs($tylehuy_percent) . '%
        </span>
    </div>
</div>';

// ==================== 3. TOP SẢN PHẨM BÁN CHẠY ====================

$sql_top = "SELECT 
                sanpham.id,
                sanpham.ten_san_pham,
                sanpham.hinh_anh,

                SUM(chitietdonhang.so_luong) as da_ban,

                SUM(chitietdonhang.thanh_tien) as doanhthu_sp

            FROM chitietdonhang

            INNER JOIN donhang 
                ON chitietdonhang.don_hang_id = donhang.id

            INNER JOIN sanpham 
                ON sanpham.id = chitietdonhang.san_pham_id

            WHERE donhang.trang_thai = 'hoan_thanh'
            AND DATE(donhang.ngay_dat) BETWEEN :from AND :to

            GROUP BY sanpham.id

            ORDER BY da_ban DESC

            LIMIT 5";

$stmt = $db->prepare($sql_top);

$stmt->execute([
    ':from' => $from,
    ':to' => $to
]);

$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($topProducts as &$p) {

    $p['doanh_thu_formatted'] = formatPrice($p['doanhthu_sp']);

    // Xử lý ảnh
    if (!empty($p['hinh_anh'])) {

        $p['hinh_anh'] = BASE_URL . '/public/assets/images/products/' . $p['hinh_anh'];
    } else {

        $p['hinh_anh'] = 'https://placehold.co/48x48?text=No+Img';
    }
}

// Nếu không có sản phẩm nào bán được
if (empty($topProducts)) {
    $topProducts = [];
}

// ==================== 4. SẢN PHẨM SẮP HẾT ====================
$stmt = $db->prepare("SELECT ten_san_pham, so_luong, don_vi_tinh 
                      FROM sanpham 
                      WHERE so_luong <= 10 AND trang_thai = 1 
                      ORDER BY so_luong ASC LIMIT 8");
$stmt->execute();
$lowStock = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==================== 5. KHÁCH HÀNG MUA NHIỀU NHẤT ====================
$sql_vip = "SELECT 
                users.id,
                users.ho_ten,
                users.avatar,
                COUNT(donhang.id) as so_don,
                COALESCE(SUM(donhang.tong_thanh_toan), 0) as tong_chi_tieu
            FROM users
            INNER JOIN donhang ON users.id = donhang.user_id
            WHERE donhang.trang_thai = 'hoan_thanh'
            AND DATE(donhang.ngay_dat) BETWEEN :from AND :to
            GROUP BY users.id
            ORDER BY tong_chi_tieu DESC
            LIMIT 5";
$stmt = $db->prepare($sql_vip);
$stmt->execute([':from' => $from, ':to' => $to]);
$topCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($topCustomers as &$c) {
    $c['tong_chi_tieu_formatted'] = formatPrice($c['tong_chi_tieu']);
    $c['vip_badge'] = $c['so_don'] >= 10;
    if (!empty($c['avatar'])) {
        $c['avatar'] = BASE_URL . '/public/assets/images/users/' . $c['avatar'];
    } else {
        $c['avatar'] = 'https://placehold.co/48x48?text=User';
    }
}

// ==================== 6. DOANH THU THEO DANH MỤC ====================
$sql_cat = "SELECT 
                danhmuc.id,
                danhmuc.ten_danh_muc,
                COALESCE(SUM(chitietdonhang.thanh_tien), 0) as dt
            FROM danhmuc
            LEFT JOIN sanpham ON danhmuc.id = sanpham.danh_muc_id
            LEFT JOIN chitietdonhang ON sanpham.id = chitietdonhang.san_pham_id
            LEFT JOIN donhang ON chitietdonhang.don_hang_id = donhang.id 
                AND donhang.trang_thai = 'hoan_thanh'
                AND DATE(donhang.ngay_dat) BETWEEN :from AND :to
            GROUP BY danhmuc.id
            ORDER BY dt DESC";
$stmt = $db->prepare($sql_cat);
$stmt->execute([':from' => $from, ':to' => $to]);
$categoryRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalRevenue = array_sum(array_column($categoryRevenue, 'dt'));
foreach ($categoryRevenue as &$cat) {
    $cat['phan_tram'] = $totalRevenue > 0 ? round(($cat['dt'] / $totalRevenue) * 100) : 0;
}

// ==================== 7. CHART DOANH THU THEO NGÀY ====================
$sql_chart = "SELECT 
                DATE(ngay_dat) as ngay,
                COALESCE(SUM(tong_thanh_toan), 0) as tong
              FROM donhang
              WHERE trang_thai = 'hoan_thanh'
              AND DATE(ngay_dat) BETWEEN :from AND :to
              GROUP BY DATE(ngay_dat)
              ORDER BY ngay";
$stmt = $db->prepare($sql_chart);
$stmt->execute([':from' => $from, ':to' => $to]);
$chartData = $stmt->fetchAll(PDO::FETCH_ASSOC);
$chartRevenue = [
    'labels' => array_column($chartData, 'ngay'),
    'values' => array_column($chartData, 'tong')
];

// ==================== 8. CHART TÌNH TRẠNG ĐƠN (CÓ MÀU) ====================
$status_list = [
    'cho_xac_nhan' => 'Chờ xác nhận',
    'dang_xu_ly'   => 'Đang xử lý',
    'dang_giao'    => 'Đang giao',
    'hoan_thanh'   => 'Hoàn thành',
    'da_huy'       => 'Đã hủy'
];

$order_status_labels = [];
$order_status_data = [];
$order_status_colors = [
    '#f59e0b',  // Chờ xác nhận - cam
    '#3b82f6',  // Đang xử lý - xanh dương
    '#06b6d4',  // Đang giao - xanh ngọc
    '#10b981',  // Hoàn thành - xanh lá
    '#ef4444'   // Đã hủy - đỏ
];

foreach ($status_list as $key => $label) {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM donhang WHERE trang_thai = :status AND DATE(ngay_dat) BETWEEN :from AND :to");
    $stmt->execute([':status' => $key, ':from' => $from, ':to' => $to]);
    $count = $stmt->fetch()['total'];

    $order_status_labels[] = $label;
    $order_status_data[] = $count;
}

// ==================== 9. BẢNG ĐƠN HÀNG ====================
$search_sql = "";
$searchParams = [':from' => $from, ':to' => $to];
if (!empty($search)) {
    $search_sql = " AND (ma_don_hang LIKE :search OR ho_ten_nguoi_nhan LIKE :search OR email_nguoi_nhan LIKE :search)";
    $searchParams[':search'] = "%$search%";
}

$order_sql = $sort == 'highest' ? "ORDER BY tong_thanh_toan DESC" : "ORDER BY ngay_dat DESC";

$sql_orders = "SELECT ma_don_hang, ho_ten_nguoi_nhan, tong_thanh_toan, trang_thai, ngay_dat 
               FROM donhang 
               WHERE DATE(ngay_dat) BETWEEN :from AND :to $search_sql 
               $order_sql 
               LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql_orders);
$stmt->bindValue(':from', $from);
$stmt->bindValue(':to', $to);
if (!empty($search)) $stmt->bindValue(':search', "%$search%");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map trạng thái để hiển thị đẹp
$status_display = [
    'cho_xac_nhan' => 'Chờ xác nhận',
    'dang_xu_ly' => 'Đang xử lý',
    'dang_giao' => 'Đang giao',
    'hoan_thanh' => 'Hoàn thành',
    'da_huy' => 'Đã hủy'
];

$status_class = [
    'cho_xac_nhan' => 'bg-warning text-dark',
    'dang_xu_ly' => 'bg-info text-white',
    'dang_giao' => 'bg-primary text-white',
    'hoan_thanh' => 'bg-success text-white',
    'da_huy' => 'bg-danger text-white'
];

$orders_html = '<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Mã đơn</th>
                <th>Khách hàng</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th>Ngày đặt</th>
            </tr>
        </thead>
        <tbody>';
foreach ($orders as $o) {
    $display_status = $status_display[$o['trang_thai']] ?? $o['trang_thai'];
    $class = $status_class[$o['trang_thai']] ?? 'bg-secondary text-white';
    $orders_html .= "<tr>
        <td><strong>" . htmlspecialchars($o['ma_don_hang']) . "</strong></td>
        <td>" . htmlspecialchars($o['ho_ten_nguoi_nhan']) . "</td>
        <td class='fw-bold'>" . formatPrice($o['tong_thanh_toan']) . "</td>
        <td><span class='status-badge $class'>" . $display_status . "</span></td>
        <td>" . date('d/m/Y', strtotime($o['ngay_dat'])) . "</td>
    </tr>";
}
$orders_html .= '</tbody></table></div>';

// Pagination
$sql_count = "SELECT COUNT(*) as total FROM donhang WHERE DATE(ngay_dat) BETWEEN :from AND :to $search_sql";
$stmt = $db->prepare($sql_count);
foreach ($searchParams as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total_rows = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_rows / $limit);

$pagination_html = '<nav><ul class="pagination pagination-sm">';
for ($i = 1; $i <= $total_pages; $i++) {
    $active = ($i == $page) ? 'active' : '';
    $pagination_html .= "<li class='page-item $active'><a class='page-link' href='#' data-page='$i'>$i</a></li>";
}
$pagination_html .= '</ul></nav>';

// ==================== TRẢ VỀ JSON ====================
header('Content-Type: application/json');
echo json_encode([
    'kpi' => $kpi,
    'comparison' => $comparison_html,
    'topProducts' => $topProducts,
    'lowStock' => $lowStock,
    'topCustomers' => $topCustomers,
    'categoryRevenue' => $categoryRevenue,
    'chartRevenue' => $chartRevenue,
    'orderStatus' => [
        'labels' => $order_status_labels,
        'values' => $order_status_data,
        'colors' => $order_status_colors  // Thêm màu sắc cho chart
    ],
    'orders' => $orders_html,
    'pagination' => $pagination_html
]);
