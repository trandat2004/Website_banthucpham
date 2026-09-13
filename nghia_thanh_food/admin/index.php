<?php
require_once __DIR__ . '/includes/check_auth.php';

$page_title = 'Dashboard';

require_once 'includes/config.php';

include 'includes/header.php';
include 'includes/sidebar.php';

$db = getDB();

/*
|--------------------------------------------------------------------------
| FIX TIMEZONE
|--------------------------------------------------------------------------
*/

date_default_timezone_set('Asia/Ho_Chi_Minh');

/*
|--------------------------------------------------------------------------
| TỔNG SẢN PHẨM
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT COUNT(*) as total
    FROM sanpham
    WHERE trang_thai = 1
");

$total_products = $stmt->fetch()['total'];

/*
|--------------------------------------------------------------------------
| SẢN PHẨM SẮP HẾT
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT COUNT(*) as total
    FROM sanpham
    WHERE so_luong <= 10
");

$low_stock_products = $stmt->fetch()['total'];

/*
|--------------------------------------------------------------------------
| ĐƠN HÀNG
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT COUNT(*) as total
    FROM donhang
");

$total_orders = $stmt->fetch()['total'];

$stmt = $db->query("
    SELECT COUNT(*) as total
    FROM donhang
    WHERE trang_thai = 'cho_xac_nhan'
");

$new_orders = $stmt->fetch()['total'];

/*
|--------------------------------------------------------------------------
| KHÁCH HÀNG
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT COUNT(*) as total
    FROM users
    WHERE vai_tro = 0
");

$total_customers = $stmt->fetch()['total'];

$stmt = $db->query("
    SELECT COUNT(*) as total
    FROM users
    WHERE vai_tro = 0
    AND DATE(ngay_tao,'localtime') = DATE('now','localtime')
");

$new_customers_today = $stmt->fetch()['total'];

/*
|--------------------------------------------------------------------------
| LIÊN HỆ MỚI HÔM NAY
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT COUNT(*) as total
    FROM lienhe
    WHERE DATE(ngay_gui,'localtime') = DATE('now','localtime')
");

$new_contacts_today = $stmt->fetch()['total'];

/*
|--------------------------------------------------------------------------
| DOANH THU HÔM NAY
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT COALESCE(SUM(tong_thanh_toan),0) as total
    FROM donhang
    WHERE trang_thai = 'hoan_thanh'
    AND DATE(ngay_dat,'localtime') = DATE('now','localtime')
");

$today_revenue = $stmt->fetch()['total'];

/*
|--------------------------------------------------------------------------
| DOANH THU THÁNG
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT COALESCE(SUM(tong_thanh_toan),0) as total
    FROM donhang
    WHERE trang_thai = 'hoan_thanh'
    AND strftime('%m',ngay_dat,'localtime') = strftime('%m','now','localtime')
    AND strftime('%Y',ngay_dat,'localtime') = strftime('%Y','now','localtime')
");

$month_revenue = $stmt->fetch()['total'];

/*
|--------------------------------------------------------------------------
| CHART DOANH THU
|--------------------------------------------------------------------------
*/

$chart_data = [];

for ($i = 1; $i <= 12; $i++) {

    $stmt = $db->prepare("
        SELECT COALESCE(SUM(tong_thanh_toan),0) as total
        FROM donhang
        WHERE trang_thai = 'hoan_thanh'
        AND strftime('%m',ngay_dat,'localtime') = ?
        AND strftime('%Y',ngay_dat,'localtime') = strftime('%Y','now','localtime')
    ");

    $stmt->execute([
        str_pad($i, 2, '0', STR_PAD_LEFT),
    ]);

    $chart_data[] = round($stmt->fetch()['total']);
}

/*
|--------------------------------------------------------------------------
| TRẠNG THÁI ĐƠN
|--------------------------------------------------------------------------
*/

$order_status_labels = [];
$order_status_data   = [];

$status_list = [
    'cho_xac_nhan' => 'Chờ xác nhận',
    'dang_xu_ly'   => 'Đang xử lý',
    'dang_giao'    => 'Đang giao',
    'hoan_thanh'   => 'Hoàn thành',
    'da_huy'       => 'Đã hủy',
];

foreach ($status_list as $key => $label) {

    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM donhang
        WHERE trang_thai = ?
    ");

    $stmt->execute([$key]);

    $order_status_labels[] = $label;
    $order_status_data[]   = $stmt->fetch()['total'];
}

/*
|--------------------------------------------------------------------------
| ĐƠN HÀNG GẦN ĐÂY
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT *
    FROM donhang
    ORDER BY ngay_dat DESC
    LIMIT 10
");

$recent_orders = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| TOP PRODUCT
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT
        sanpham.ten_san_pham,
        sanpham.hinh_anh,
        COALESCE(SUM(chitietdonhang.so_luong), 0) as total_sold
    FROM chitietdonhang
    INNER JOIN donhang
        ON chitietdonhang.don_hang_id = donhang.id
    INNER JOIN sanpham
        ON chitietdonhang.san_pham_id = sanpham.id
    WHERE donhang.trang_thai = 'hoan_thanh'
    GROUP BY sanpham.id
    ORDER BY total_sold DESC
    LIMIT 6
");

$top_products = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| HOẠT ĐỘNG GẦN ĐÂY (ĐA DẠNG)
|--------------------------------------------------------------------------
*/

$activities = [];

// Lấy đơn hàng mới (30 ngày gần nhất)
$stmt = $db->query("
    SELECT
        'order' as type,
        ma_don_hang as ref_id,
        ho_ten_nguoi_nhan as customer_name,
        tong_thanh_toan as amount,
        trang_thai as status,
        ngay_dat as created_at
    FROM donhang
    WHERE DATE(ngay_dat,'localtime') >= DATE('now','localtime','-30 days')
    ORDER BY ngay_dat DESC
    LIMIT 10
");
$new_orders_activity = $stmt->fetchAll();

foreach ($new_orders_activity as $order) {
    $activities[] = [
        'type'        => 'order',
        'title'       => 'Đơn hàng mới',
        'description' => $order['customer_name'] . ' vừa đặt đơn hàng #' . $order['ref_id'],
        'amount'      => $order['amount'],
        'status'      => $order['status'],
        'time'        => $order['created_at'],
        'icon'        => 'fa-shopping-cart',
        'icon_bg'     => '#ecfdf5',
        'icon_color'  => '#10b981',
    ];
}

// Lấy khách hàng mới đăng ký (30 ngày gần nhất)
$stmt = $db->query("
    SELECT
        'register' as type,
        id as user_id,
        ho_ten as customer_name,
        email,
        ngay_tao as created_at
    FROM users
    WHERE vai_tro = 0
    AND DATE(ngay_tao,'localtime') >= DATE('now','localtime','-30 days')
    ORDER BY ngay_tao DESC
    LIMIT 10
");
$new_registers = $stmt->fetchAll();

foreach ($new_registers as $register) {
    $activities[] = [
        'type'        => 'register',
        'title'       => 'Khách hàng mới',
        'description' => $register['customer_name'] . ' vừa đăng ký tài khoản mới',
        'email'       => $register['email'],
        'time'        => $register['created_at'],
        'icon'        => 'fa-user-plus',
        'icon_bg'     => '#eff6ff',
        'icon_color'  => '#3b82f6',
    ];
}

// Lấy liên hệ mới (30 ngày gần nhất)
// Kiểm tra bảng lienhe có tồn tại không trước khi query
$check_table = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='lienhe'");
if ($check_table->fetch()) {
    $stmt = $db->query("
        SELECT
            'contact' as type,
            id as contact_id,
            ho_ten as customer_name,
            email,
            noi_dung as content,
            ngay_gui as created_at
        FROM lienhe
        WHERE DATE(ngay_gui,'localtime') >= DATE('now','localtime','-30 days')
        ORDER BY ngay_gui DESC
        LIMIT 10
    ");
    $new_contacts = $stmt->fetchAll();

    foreach ($new_contacts as $contact) {
        $activities[] = [
            'type'        => 'contact',
            'title'       => 'Liên hệ mới',
            'description' => $contact['customer_name'] . ' vừa gửi liên hệ',
            'email'       => $contact['email'],
            'content'     => mb_substr($contact['content'], 0, 50) . (strlen($contact['content']) > 50 ? '...' : ''),
            'time'        => $contact['created_at'],
            'icon'        => 'fa-envelope',
            'icon_bg'     => '#fef3c7',
            'icon_color'  => '#f59e0b',
        ];
    }
}

// Lấy đơn hàng bị hủy (30 ngày gần nhất)
$stmt = $db->query("
    SELECT
        'cancel_order' as type,
        ma_don_hang as ref_id,
        ho_ten_nguoi_nhan as customer_name,
        tong_thanh_toan as amount,
        trang_thai as status,
        ngay_dat as created_at
    FROM donhang
    WHERE trang_thai = 'da_huy'
    AND DATE(ngay_dat,'localtime') >= DATE('now','localtime','-30 days')
    ORDER BY ngay_dat DESC
    LIMIT 10
");
$cancel_orders = $stmt->fetchAll();

foreach ($cancel_orders as $cancel) {
    $activities[] = [
        'type'        => 'cancel_order',
        'title'       => 'Đơn hàng bị hủy',
        'description' => $cancel['customer_name'] . ' đã hủy đơn hàng #' . $cancel['ref_id'],
        'amount'      => $cancel['amount'],
        'time'        => $cancel['created_at'],
        'icon'        => 'fa-ban',
        'icon_bg'     => '#fef2f2',
        'icon_color'  => '#ef4444',
    ];
}

// Sắp xếp activities theo thời gian giảm dần và lấy 15 hoạt động gần nhất
usort($activities, function ($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});
$recent_activities = array_slice($activities, 0, 15);

?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

    * {
        font-family: 'Inter', sans-serif;
    }

    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        overflow: hidden;
    }

    /* MAIN */
    .main-content {
        height: 100vh;
        overflow: hidden;
        background: linear-gradient(180deg, #f0f4f8 0%, #e2e8f0 100%);
    }

    /* SCROLL */
    .dashboard-scroll {
        height: 100vh;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 0 30px 30px;
        scroll-behavior: smooth;
    }

    .dashboard-scroll::-webkit-scrollbar {
        width: 8px;
    }

    .dashboard-scroll::-webkit-scrollbar-track {
        background: #e2e8f0;
        border-radius: 10px;
    }

    .dashboard-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .dashboard-scroll::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* STICKY HEADER */
    .dashboard-header {
        position: sticky;
        top: 0;
        z-index: 999;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        padding: 28px 0 24px;
        margin-bottom: 28px;
        border-bottom: 1px solid rgba(203, 213, 225, 0.5);
    }

    .dashboard-title {
        font-size: 32px;
        font-weight: 800;
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        background-clip: text;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 8px;
    }

    .dashboard-sub {
        color: #64748b;
        font-size: 14px;
        font-weight: 500;
    }

    /* KPI CARDS MODERN */
    .kpi-card {
        border: none;
        border-radius: 24px;
        overflow: hidden;
        position: relative;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }

    .kpi-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .kpi-card .card-body {
        position: relative;
        z-index: 2;
        padding: 24px;
    }

    .kpi-icon-wrapper {
        width: 56px;
        height: 56px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
    }

    .kpi-icon-wrapper i {
        font-size: 28px;
        color: white;
    }

    .kpi-title {
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.9;
        margin-bottom: 8px;
    }

    .kpi-number {
        font-size: 32px;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .kpi-sub {
        font-size: 12px;
        opacity: 0.85;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .kpi-sub i {
        font-size: 11px;
    }

    /* SECTION CARDS */
    .section-card {
        background: white;
        border: none;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        transition: all 0.3s ease;
        height: 100%;
    }

    .section-card:hover {
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
    }

    .section-header {
        padding: 24px 26px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .section-title {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-title i {
        color: #3b82f6;
        font-size: 20px;
    }

    .section-body {
        padding: 24px 26px 26px;
    }

    /* CHART */
    .chart-wrapper {
        height: 320px;
        position: relative;
    }

    /* MODERN TABLE */
    .table-modern {
        margin: 0;
    }

    .table-modern thead th {
        background: #f8fafc;
        border: none;
        padding: 16px 20px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
    }

    .table-modern tbody td {
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        font-size: 14px;
    }

    .table-modern tbody tr:hover {
        background: #f8fafc;
    }

    .order-id {
        font-weight: 700;
        color: #1e293b;
        font-family: 'Monaco', monospace;
    }

    /* STATUS BADGES MODERN */
    .status-badge {
        padding: 6px 12px;
        border-radius: 100px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .status-badge i {
        font-size: 11px;
    }

    .bg-wait {
        background: #ffedd5;
        color: #ea580c;
    }

    .bg-process {
        background: #dbeafe;
        color: #2563eb;
    }

    .bg-delivery {
        background: #cffafe;
        color: #0891b2;
    }

    .bg-success-custom {
        background: #dcfce7;
        color: #16a34a;
    }

    .bg-cancel {
        background: #fee2e2;
        color: #dc2626;
    }

    /* PRODUCT ITEMS MODERN */
    .product-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 0;
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.2s ease;
    }

    .product-item:last-child {
        border: none;
    }

    .product-item:hover {
        transform: translateX(4px);
    }

    .product-left {
        display: flex;
        align-items: center;
        gap: 14px;
        flex: 1;
    }

    .product-image {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        object-fit: cover;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .product-name {
        font-weight: 600;
        color: #1e293b;
        font-size: 14px;
    }

    .product-sold {
        padding: 6px 14px;
        border-radius: 100px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
    }

    /* ACTIVITY ITEMS MODERN */
    .activity-list {
        max-height: 500px;
        overflow-y: auto;
    }

    .activity-list::-webkit-scrollbar {
        width: 4px;
    }

    .activity-list::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }

    .activity-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .activity-item {
        display: flex;
        gap: 14px;
        padding: 16px 0;
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.2s ease;
    }

    .activity-item:last-child {
        border: none;
    }

    .activity-item:hover {
        background: #f8fafc;
        margin: 0 -8px;
        padding: 16px 8px;
        border-radius: 12px;
    }

    .activity-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .activity-icon i {
        font-size: 20px;
    }

    .activity-content {
        flex: 1;
    }

    .activity-text {
        font-weight: 600;
        color: #1e293b;
        font-size: 14px;
        margin-bottom: 4px;
    }

    .activity-description {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 4px;
    }

    .activity-time {
        font-size: 11px;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .activity-time i {
        font-size: 10px;
    }

    .activity-amount {
        font-weight: 700;
        color: #10b981;
        font-size: 13px;
    }

    /* ALERT BOXES MODERN */
    .alert-box {
        background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%);
        color: #92400e;
        border-radius: 16px;
        padding: 18px 20px;
        margin-bottom: 14px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
        border-left: 4px solid #f59e0b;
        transition: all 0.2s ease;
    }

    .alert-box:last-child {
        margin-bottom: 0;
    }

    .alert-box:hover {
        transform: translateX(4px);
    }

    .alert-box i {
        font-size: 20px;
    }

    /* CHART CUSTOMIZATION */
    canvas {
        max-height: 100%;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .dashboard-scroll {
            padding: 0 16px 16px;
        }

        .kpi-number {
            font-size: 24px;
        }

        .section-body {
            padding: 20px;
        }
    }
</style>

<div class="col-md-10 main-content">
    <div class="dashboard-scroll">
        <!-- HEADER -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                🚀 Dashboard
            </div>
            <div class="dashboard-sub">
                Tổng quan hệ thống quản trị Nghĩa Thành Food
            </div>
        </div>

        <!-- KPI CARDS -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body">
                        <div class="kpi-icon-wrapper">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="kpi-title">Tổng sản phẩm</div>
                        <div class="kpi-number"><?php echo number_format($total_products); ?></div>
                        <div class="kpi-sub">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo $low_stock_products; ?> sản phẩm sắp hết
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="card-body">
                        <div class="kpi-icon-wrapper">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="kpi-title">Tổng đơn hàng</div>
                        <div class="kpi-number"><?php echo number_format($total_orders); ?></div>
                        <div class="kpi-sub">
                            <i class="fas fa-clock"></i>
                            <?php echo $new_orders; ?> đơn chưa xử lý
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="card-body">
                        <div class="kpi-icon-wrapper">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="kpi-title">Doanh thu tháng</div>
                        <div class="kpi-number" style="font-size: 24px;"><?php echo number_format($month_revenue, 0, ',', '.'); ?>đ</div>
                        <div class="kpi-sub">
                            <i class="fas fa-calendar-day"></i>
                            Hôm nay: <?php echo number_format($today_revenue, 0, ',', '.'); ?>đ
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="card-body">
                        <div class="kpi-icon-wrapper">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="kpi-title">Khách hàng</div>
                        <div class="kpi-number"><?php echo number_format($total_customers); ?></div>
                        <div class="kpi-sub">
                            <i class="fas fa-user-plus"></i>
                            +<?php echo $new_customers_today; ?> khách mới hôm nay
                            <?php if ($new_contacts_today > 0): ?>
                                <span style="margin-left: 8px;">| <i class="fas fa-envelope"></i> +<?php echo $new_contacts_today; ?> liên hệ</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CHARTS -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="fas fa-chart-line"></i>
                            Biểu đồ doanh thu năm nay
                        </div>
                        <div class="text-muted small">
                            <i class="fas fa-info-circle"></i> Đơn vị: VNĐ
                        </div>
                    </div>
                    <div class="section-body">
                        <div class="chart-wrapper">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="fas fa-chart-pie"></i>
                            Trạng thái đơn hàng
                        </div>
                    </div>
                    <div class="section-body">
                        <div class="chart-wrapper">
                            <canvas id="orderStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECENT ORDERS & TOP PRODUCTS -->
        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="fas fa-history"></i>
                            Đơn hàng gần đây
                        </div>
                        <a href="orders/list.php" class="text-primary small" style="text-decoration: none;">
                            Xem tất cả <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="section-body p-0">
                        <div class="table-responsive">
                            <table class="table table-modern">
                                <thead>
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Khách hàng</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <?php
                                        $status_class = '';
                                        $status_icon  = '';
                                        switch ($order['trang_thai']) {
                                            case 'cho_xac_nhan':
                                                $status_class = 'bg-wait';
                                                $status_icon  = 'fa-clock';
                                                break;
                                            case 'dang_xu_ly':
                                                $status_class = 'bg-process';
                                                $status_icon  = 'fa-spinner';
                                                break;
                                            case 'dang_giao':
                                                $status_class = 'bg-delivery';
                                                $status_icon  = 'fa-truck';
                                                break;
                                            case 'hoan_thanh':
                                                $status_class = 'bg-success-custom';
                                                $status_icon  = 'fa-check-circle';
                                                break;
                                            case 'da_huy':
                                                $status_class = 'bg-cancel';
                                                $status_icon  = 'fa-times-circle';
                                                break;
                                        }
                                        ?>
                                        <tr>
                                            <td class="order-id">#<?php echo $order['ma_don_hang']; ?></td>
                                            <td><?php echo htmlspecialchars($order['ho_ten_nguoi_nhan']); ?></td>
                                            <td class="fw-bold"><?php echo formatPrice($order['tong_thanh_toan']); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <i class="fas <?php echo $status_icon; ?>"></i>
                                                    <?php echo str_replace('_', ' ', $order['trang_thai']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="fas fa-fire"></i>
                            Top sản phẩm bán chạy
                        </div>
                    </div>
                    <div class="section-body">
                        <?php foreach ($top_products as $index => $product): ?>
                            <div class="product-item">
                                <div class="product-left">
                                    <div style="font-weight: 800; color: #f59e0b; width: 30px;">#<?php echo $index + 1; ?></div>
                                    <img src="<?php echo BASE_URL; ?>/public/assets/images/products/<?php echo $product['hinh_anh']; ?>" class="product-image" onerror="this.src='https://via.placeholder.com/52'">
                                    <div class="product-name">
                                        <?php echo htmlspecialchars($product['ten_san_pham']); ?>
                                    </div>
                                </div>
                                <div class="product-sold">
                                    <i class="fas fa-chart-simple"></i> <?php echo $product['total_sold']; ?> bán
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACTIVITIES & ALERTS -->
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="fas fa-bell"></i>
                            Hoạt động gần đây
                        </div>
                        <div class="text-muted small">
                            <i class="fas fa-sync-alt"></i> 30 ngày qua
                        </div>
                    </div>
                    <div class="section-body">
                        <div class="activity-list">
                            <?php if (count($recent_activities) > 0): ?>
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon" style="background: <?php echo $activity['icon_bg']; ?>; color: <?php echo $activity['icon_color']; ?>;">
                                            <i class="fas <?php echo $activity['icon']; ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-text">
                                                <?php echo $activity['title']; ?>
                                            </div>
                                            <div class="activity-description">
                                                <?php echo $activity['description']; ?>
                                                <?php if (isset($activity['amount'])): ?>
                                                    <span class="activity-amount">(<?php echo formatPrice($activity['amount']); ?>)</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="activity-time">
                                                <i class="fas fa-calendar-alt"></i>
                                                <?php
                                                $time = strtotime($activity['time']);
                                                $now  = time();
                                                $diff = $now - $time;

                                                if ($diff < 60) {
                                                    echo 'Vài giây trước';
                                                } elseif ($diff < 3600) {
                                                    echo floor($diff / 60) . ' phút trước';
                                                } elseif ($diff < 86400) {
                                                    echo floor($diff / 3600) . ' giờ trước';
                                                } elseif ($diff < 604800) {
                                                    echo floor($diff / 86400) . ' ngày trước';
                                                } else {
                                                    echo date('d/m/Y H:i', $time);
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>Chưa có hoạt động nào trong 30 ngày qua</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Thông báo hệ thống
                        </div>
                    </div>
                    <div class="section-body">
                        <?php if ($low_stock_products > 0): ?>
                            <div class="alert-box">
                                <i class="fas fa-box-open"></i>
                                <div>
                                    <strong>Cảnh báo tồn kho!</strong><br>
                                    Có <strong><?php echo $low_stock_products; ?></strong> sản phẩm sắp hết hàng (≤10)
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($new_orders > 0): ?>
                            <div class="alert-box">
                                <i class="fas fa-shopping-cart"></i>
                                <div>
                                    <strong>Đơn hàng mới!</strong><br>
                                    Có <strong><?php echo $new_orders; ?></strong> đơn hàng chưa xác nhận
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="alert-box">
                            <i class="fas fa-chart-line"></i>
                            <div>
                                <strong>Hiệu suất tháng</strong><br>
                                Doanh thu: <strong><?php echo number_format($month_revenue, 0, ',', '.'); ?>đ</strong>
                                <?php if ($month_revenue > 0): ?>
                                    <br><small class="text-success">
                                        <i class="fas fa-arrow-up"></i> Tích cực
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($new_customers_today > 0): ?>
                            <div class="alert-box">
                                <i class="fas fa-user-plus"></i>
                                <div>
                                    <strong>Khách hàng mới!</strong><br>
                                    Có <strong><?php echo $new_customers_today; ?></strong> khách hàng mới đăng ký hôm nay
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($new_contacts_today > 0): ?>
                            <div class="alert-box">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <strong>Liên hệ mới!</strong><br>
                                    Có <strong><?php echo $new_contacts_today; ?></strong> liên hệ chưa xử lý
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script>
    Chart.defaults.font.family = 'Inter';
    Chart.defaults.font.size = 12;

    // Revenue Chart
    const revenueChart = new Chart(
        document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
                datasets: [{
                    data: <?php echo json_encode($chart_data); ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.08)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: 'white',
                    pointBorderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let value = context.parsed.y;
                                return 'Doanh thu: ' + value.toLocaleString('vi-VN') + 'đ';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                weight: '600'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f5f9',
                            drawBorder: false
                        },
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return (value / 1000000).toFixed(1) + 'tr';
                                }
                                return value.toLocaleString('vi-VN') + 'đ';
                            }
                        }
                    }
                }
            }
        }
    );

    // Order Status Chart
    const orderStatusChart = new Chart(
        document.getElementById('orderStatusChart'), {
            type: 'doughnut',

            data: {
                labels: <?php echo json_encode($order_status_labels); ?>,

                datasets: [{
                    data: <?php echo json_encode($order_status_data); ?>,

                    backgroundColor: [
                        '#f59e0b',
                        '#3b82f6',
                        '#06b6d4',
                        '#10b981',
                        '#ef4444'
                    ],

                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },

            plugins: [ChartDataLabels],

            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',

                plugins: {

                    legend: {
                        position: 'bottom',

                        labels: {
                            padding: 15,

                            font: {
                                size: 11,
                                weight: '600'
                            },

                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },

                    // Hover tooltip cũ
                    tooltip: {
                        callbacks: {
                            label: function(context) {

                                let label = context.label || '';
                                let value = context.parsed || 0;

                                let total = context.dataset.data.reduce(
                                    (a, b) => a + b,
                                    0
                                );

                                let percentage = (
                                    (value / total) * 100
                                ).toFixed(1);

                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    },

                    // Hiển thị % trên chart
                    datalabels: {

                        color: '#fff',

                        font: {
                            weight: '700',
                            size: 13
                        },

                        formatter: (value, context) => {

                            const data =
                                context.chart.data.datasets[0].data;

                            const total = data.reduce(
                                (a, b) => a + b,
                                0
                            );

                            if (total === 0) return '';

                            const percentage =
                                ((value / total) * 100).toFixed(1);

                            // Ẩn nếu phần quá nhỏ
                            if (percentage < 5) {
                                return '';
                            }

                            return percentage + '%';
                        }
                    }
                }
            }
        }
    );

    // Auto refresh every 60 seconds (thay vì 30s để giảm tải)
    setInterval(function() {
        window.location.reload();
    }, 60000);
</script>

<?php include 'includes/footer.php'; ?>