<?php
require_once __DIR__ . '/../includes/check_auth.php';

$page_title = 'Quản lý đơn hàng';

require_once '../includes/config.php';

include '../includes/header.php';
include '../includes/sidebar.php';

$db = getDB();

/*
|--------------------------------------------------------------------------
| CẬP NHẬT TRẠNG THÁI
|--------------------------------------------------------------------------
*/

if (isset($_POST['update_status'])) {

    $order_id = intval($_POST['order_id']);
    $new_status = safeInput($_POST['status']);

    $stmt = $db->prepare("
        SELECT trang_thai
        FROM donhang
        WHERE id = ?
    ");

    $stmt->execute([$order_id]);

    $current_order = $stmt->fetch();

    if ($current_order) {

        $current_status = $current_order['trang_thai'];

        $allowed = false;

        switch ($current_status) {

            case 'cho_xac_nhan':

                if (in_array($new_status, ['dang_xu_ly', 'da_huy'])) {
                    $allowed = true;
                }

                break;

            case 'dang_xu_ly':

                if (in_array($new_status, ['dang_giao'])) {
                    $allowed = true;
                }

                break;

            case 'dang_giao':

                if (in_array($new_status, ['hoan_thanh'])) {
                    $allowed = true;
                }

                break;
        }

        if ($allowed) {

            $update = $db->prepare("
        UPDATE donhang
        SET trang_thai = ?,
            ngay_cap_nhat = CURRENT_TIMESTAMP
        WHERE id = ?
    ");

            $update->execute([$new_status, $order_id]);

            // =============================================
            // GỬI THÔNG BÁO CHO KHÁCH HÀNG
            // =============================================

            // Lấy thông tin đơn hàng
            $stmt_order = $db->prepare("
        SELECT ma_don_hang, user_id, ho_ten_nguoi_nhan
        FROM donhang
        WHERE id = ?
    ");
            $stmt_order->execute([$order_id]);
            $order_info = $stmt_order->fetch();

            if ($order_info && $order_info['user_id'] > 0) {

                // Map trạng thái sang tiếng Việt
                $status_map = [
                    'cho_xac_nhan' => 'chờ xác nhận',
                    'dang_xu_ly' => 'đang xử lý',
                    'dang_giao' => 'đang giao hàng',
                    'hoan_thanh' => 'hoàn thành',
                    'da_huy' => 'đã hủy'
                ];
                $status_vn = $status_map[$new_status] ?? $new_status;

                // Tạo link đến chi tiết đơn hàng trong profile
                $order_detail_link = BASE_URL . '/public/pages/profile.php?order_id=' . $order_id . '#orders-tab';

                // INSERT thông báo với link
                $insert_sql = "INSERT INTO thongbao (
                            user_id, 
                            loai, 
                            hanh_dong, 
                            tham_chieu_id, 
                            tieu_de, 
                            noi_dung, 
                            link, 
                            da_xem, 
                            ngay_tao
                        ) VALUES (
                            :user_id, 
                            'don_hang', 
                            'cap_nhat_trang_thai', 
                            :order_id, 
                            :tieu_de, 
                            :noi_dung, 
                            :link, 
                            0, 
                            datetime('now', '+7 hours')
                        )";

                $insert_stmt = $db->prepare($insert_sql);
                $insert_stmt->execute([
                    ':user_id' => $order_info['user_id'],
                    ':order_id' => $order_id,
                    ':tieu_de' => '📦 Cập nhật đơn hàng #' . $order_info['ma_don_hang'],
                    ':noi_dung' => 'Đơn hàng của bạn đã được cập nhật trạng thái: ' . $status_vn,
                    ':link' => $order_detail_link
                ]);
            }

            $_SESSION['success_message'] = 'Cập nhật trạng thái thành công!';
        }
    }

    redirect('/admin/orders/list.php');
}

/*
|--------------------------------------------------------------------------
| TÌM KIẾM & LỌC
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$where = [];
$params = [];

if ($search != '') {

    $where[] = "(
        ma_don_hang LIKE ?
        OR ho_ten_nguoi_nhan LIKE ?
        OR dien_thoai_nguoi_nhan LIKE ?
    )";

    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter != '') {

    $where[] = "trang_thai = ?";
    $params[] = $status_filter;
}

$sql_where = '';

if (count($where) > 0) {

    $sql_where = 'WHERE ' . implode(' AND ', $where);
}

/*
|--------------------------------------------------------------------------
| THỐNG KÊ
|--------------------------------------------------------------------------
*/

$total_orders = $db->query("
    SELECT COUNT(*) 
    FROM donhang
")->fetchColumn();

$pending_orders = $db->query("
    SELECT COUNT(*) 
    FROM donhang
    WHERE trang_thai = 'cho_xac_nhan'
")->fetchColumn();

$completed_orders = $db->query("
    SELECT COUNT(*) 
    FROM donhang
    WHERE trang_thai = 'hoan_thanh'
")->fetchColumn();

/* DOANH THU HÔM NAY */

$today = date('Y-m-d');

$stmt = $db->prepare("
    SELECT COALESCE(SUM(tong_thanh_toan),0)
    FROM donhang
    WHERE trang_thai = 'hoan_thanh'
    AND DATE(ngay_cap_nhat) = ?
");

$stmt->execute([$today]);

$today_revenue = $stmt->fetchColumn();

/* DOANH THU THÁNG */

$current_month = date('m');
$current_year = date('Y');

$stmt = $db->prepare("
    SELECT COALESCE(SUM(tong_thanh_toan),0)
    FROM donhang
    WHERE trang_thai = 'hoan_thanh'
    AND strftime('%m', ngay_cap_nhat) = ?
    AND strftime('%Y', ngay_cap_nhat) = ?
");

$stmt->execute([$current_month, $current_year]);

$month_revenue = $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| DANH SÁCH ĐƠN HÀNG
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT *
    FROM donhang
    $sql_where
    ORDER BY ngay_dat DESC
");

$stmt->execute($params);

$orders = $stmt->fetchAll();

?>

<style>
    .order-page {
        background: #f3f6fb;

        height: 100vh;
        overflow: hidden;
    }

    .order-wrapper {
        width: 100%;
        height: 100%;
    }

    .order-scroll {
        width: 100%;
    }

    .order-scroll::-webkit-scrollbar {
        width: 8px;
    }

    .order-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 20px;
    }

    .order-title {
        font-size: 32px;
        font-weight: 800;
        color: #14532d;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 18px;
        margin-bottom: 20px;
    }

    .stats-card {
        border-radius: 22px;
        padding: 22px;
        color: #fff;
        position: relative;
        overflow: hidden;
    }

    .stats-card h4 {
        font-size: 14px;
        margin-bottom: 10px;
        opacity: .9;
    }

    .stats-card h2 {
        font-size: 30px;
        font-weight: 800;
        margin: 0;
    }

    .stats-card i {
        position: absolute;
        right: 20px;
        top: 20px;
        font-size: 34px;
        opacity: .2;
    }

    .bg-green {
        background: linear-gradient(135deg, #16a34a, #166534);
    }

    .bg-orange {
        background: linear-gradient(135deg, #f59e0b, #ea580c);
    }

    .bg-blue {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
    }

    .bg-purple {
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
    }

    .bg-pink {
        background: linear-gradient(135deg, #ec4899, #be185d);
    }

    .filter-card {
        background: #fff;
        border-radius: 24px;
        padding: 22px;
        margin-bottom: 20px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
    }

    .filter-input {
        height: 48px;
        border-radius: 14px;
        border: 1px solid #dbe4ea;
    }

    .order-card {
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
        overflow: hidden;

        /* KHUNG BẢNG */
        height: calc(100vh - 365px);

        display: flex;
        flex-direction: column;
    }

    /* KHUNG SCROLL RIÊNG */
    .order-table-wrapper {
        flex: 1;
        overflow-y: auto;
        overflow-x: auto;
        min-height: 0;
    }

    /* SCROLLBAR */
    .order-table-wrapper::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .order-table-wrapper::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 20px;
    }

    /* TABLE */
    .order-table {
        margin: 0;
    }

    /* HEADER CỐ ĐỊNH */
    .order-table thead {
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .order-table thead th {
        background: #166534;
        color: #fff;
        padding: 18px 14px;
        border: none;
        white-space: nowrap;

        position: sticky;
        top: 0;
    }

    /* BODY */
    .order-table tbody td {
        padding: 18px 14px;
        vertical-align: middle;
    }

    .order-table tbody tr {
        border-bottom: 1px solid #eef2f7;
        transition: .2s;
    }

    .order-table tbody tr:hover {
        background: #f9fafb;
    }

    .order-id {
        font-weight: 800;
        color: #15803d;
    }

    .customer-name {
        font-weight: 700;
    }

    .customer-phone {
        color: #6b7280;
        font-size: 14px;
    }

    .total-price {
        color: #dc2626;
        font-size: 16px;
        font-weight: 800;
    }

    .status-select {
        min-width: 150px;
        border-radius: 12px;
        height: 42px;
        border: 1px solid #dbe4ea;
    }

    .update-btn {
        height: 42px;
        border-radius: 12px;
        font-weight: 600;
        padding: 0 16px;
    }

    .view-btn {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .badge-status {
        padding: 10px 14px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 700;
    }

    .status-warning {
        background: #fff7ed;
        color: #ea580c;
    }

    .status-info {
        background: #eff6ff;
        color: #2563eb;
    }

    .status-primary {
        background: #eef2ff;
        color: #4f46e5;
    }

    .status-success {
        background: #ecfdf5;
        color: #059669;
    }

    .status-danger {
        background: #fef2f2;
        color: #dc2626;
    }

    .order-modal .modal-content {
        border: none;
        border-radius: 24px;
        overflow: hidden;
    }

    .order-modal .modal-header {
        background: linear-gradient(90deg, #16a34a, #166534);
        color: #fff;
        padding: 20px 24px;
    }

    .order-modal .modal-body {
        padding: 25px;
    }

    .info-box {
        background: #f9fafb;
        border-radius: 18px;
        padding: 18px;
        margin-bottom: 20px;
    }

    .info-title {
        font-weight: 700;
        color: #166534;
        margin-bottom: 12px;
    }

    .product-table th {
        background: #f0fdf4;
        color: #166534;
    }

    @media(max-width:1200px) {

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .custom-toast {
        position: fixed;
        top: 20px;
        right: 25px;

        background: linear-gradient(135deg, #16a34a, #166534);
        color: #fff;

        padding: 14px 22px;
        border-radius: 16px;

        font-weight: 600;
        font-size: 15px;

        box-shadow: 0 10px 30px rgba(0, 0, 0, .18);

        z-index: 99999;

        display: flex;
        align-items: center;

        animation: toastShow .35s ease;
    }

    @keyframes toastShow {

        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div class="col-md-10 p-4 order-page">

    <div class="order-wrapper">

        <div class="order-scroll">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>

                    <h2 class="order-title mb-1">
                        Quản lý đơn hàng
                    </h2>

                    <p class="text-muted mb-0">
                        Theo dõi và xử lý đơn hàng khách hàng
                    </p>

                </div>

            </div>

            <!-- THỐNG KÊ -->

            <div class="stats-grid">

                <div class="stats-card bg-green">

                    <i class="fas fa-shopping-cart"></i>

                    <h4>Tổng đơn hàng</h4>

                    <h2><?php echo number_format($total_orders); ?></h2>

                </div>

                <div class="stats-card bg-orange">

                    <i class="fas fa-clock"></i>

                    <h4>Đơn chờ xử lý</h4>

                    <h2><?php echo number_format($pending_orders); ?></h2>

                </div>

                <div class="stats-card bg-blue">

                    <i class="fas fa-check-circle"></i>

                    <h4>Đơn hoàn thành</h4>

                    <h2><?php echo number_format($completed_orders); ?></h2>

                </div>

                <div class="stats-card bg-purple">

                    <i class="fas fa-sack-dollar"></i>

                    <h4>Doanh thu hôm nay</h4>

                    <h2><?php echo formatPrice($today_revenue); ?></h2>

                </div>

                <div class="stats-card bg-pink">

                    <i class="fas fa-chart-line"></i>

                    <h4>Doanh thu tháng</h4>

                    <h2><?php echo formatPrice($month_revenue); ?></h2>

                </div>

            </div>

            <!-- FILTER -->

            <div class="filter-card">

                <form method="GET">

                    <div class="row g-3 align-items-center">

                        <div class="col-md-7">

                            <input
                                type="text"
                                name="search"
                                class="form-control filter-input"
                                placeholder="Tìm theo mã đơn, tên khách hoặc SĐT..."
                                value="<?php echo htmlspecialchars($search); ?>">

                        </div>

                        <div class="col-md-3">

                            <select
                                name="status"
                                class="form-select filter-input">

                                <option value="">
                                    Tất cả trạng thái
                                </option>

                                <option value="cho_xac_nhan" <?php echo $status_filter == 'cho_xac_nhan' ? 'selected' : ''; ?>>
                                    Chờ xác nhận
                                </option>

                                <option value="dang_xu_ly" <?php echo $status_filter == 'dang_xu_ly' ? 'selected' : ''; ?>>
                                    Đang xử lý
                                </option>

                                <option value="dang_giao" <?php echo $status_filter == 'dang_giao' ? 'selected' : ''; ?>>
                                    Đang giao
                                </option>

                                <option value="hoan_thanh" <?php echo $status_filter == 'hoan_thanh' ? 'selected' : ''; ?>>
                                    Hoàn thành
                                </option>

                                <option value="da_huy" <?php echo $status_filter == 'da_huy' ? 'selected' : ''; ?>>
                                    Đã hủy
                                </option>

                            </select>

                        </div>

                        <div class="col-md-2">

                            <div class="d-flex gap-2">

                                <button
                                    type="submit"
                                    class="btn btn-success flex-fill filter-input">
                                    <i class="fas fa-search"></i>
                                </button>

                                <a
                                    href="list.php"
                                    class="btn btn-danger flex-fill filter-input d-flex align-items-center justify-content-center">
                                    <i class="fas fa-rotate-left"></i>
                                </a>

                            </div>

                        </div>

                    </div>

                </form>

            </div>

            <?php if (isset($_SESSION['success_message'])): ?>

                <div class="custom-toast" id="successToast">

                    <i class="fas fa-circle-check me-2"></i>

                    <?php
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>

                </div>

            <?php endif; ?>

            <!-- TABLE -->

            <div class="order-card">

                <div class="order-table-wrapper">

                    <table class="table order-table">

                        <thead>

                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Ngày đặt</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th class="text-center">Chi tiết</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($orders as $order): ?>

                                <?php

                                $status_class = '';

                                switch ($order['trang_thai']) {

                                    case 'cho_xac_nhan':
                                        $status_class = 'status-warning';
                                        break;

                                    case 'dang_xu_ly':
                                        $status_class = 'status-info';
                                        break;

                                    case 'dang_giao':
                                        $status_class = 'status-primary';
                                        break;

                                    case 'hoan_thanh':
                                        $status_class = 'status-success';
                                        break;

                                    case 'da_huy':
                                        $status_class = 'status-danger';
                                        break;
                                }

                                ?>

                                <tr>

                                    <td>

                                        <div class="order-id">
                                            #<?php echo $order['ma_don_hang']; ?>
                                        </div>

                                    </td>

                                    <td>

                                        <div class="customer-name">
                                            <?php echo htmlspecialchars($order['ho_ten_nguoi_nhan']); ?>
                                        </div>

                                        <div class="customer-phone">
                                            <?php echo htmlspecialchars($order['dien_thoai_nguoi_nhan']); ?>
                                        </div>

                                    </td>

                                    <td>

                                        <?php echo date('d/m/Y H:i', strtotime($order['ngay_dat'])); ?>

                                    </td>

                                    <td>

                                        <div class="total-price">
                                            <?php echo formatPrice($order['tong_thanh_toan']); ?>
                                        </div>

                                    </td>

                                    <td>

                                        <form method="POST" class="d-flex gap-2 align-items-center">

                                            <input
                                                type="hidden"
                                                name="order_id"
                                                value="<?php echo $order['id']; ?>">

                                            <select
                                                name="status"
                                                class="form-select status-select">

                                                <?php if ($order['trang_thai'] == 'cho_xac_nhan'): ?>

                                                    <option value="cho_xac_nhan" selected>
                                                        Chờ xác nhận
                                                    </option>

                                                    <option value="dang_xu_ly">
                                                        Đang xử lý
                                                    </option>

                                                    <option value="da_huy">
                                                        Đã hủy
                                                    </option>

                                                <?php elseif ($order['trang_thai'] == 'dang_xu_ly'): ?>

                                                    <option value="dang_xu_ly" selected>
                                                        Đang xử lý
                                                    </option>

                                                    <option value="dang_giao">
                                                        Đang giao
                                                    </option>

                                                <?php elseif ($order['trang_thai'] == 'dang_giao'): ?>

                                                    <option value="dang_giao" selected>
                                                        Đang giao
                                                    </option>

                                                    <option value="hoan_thanh">
                                                        Hoàn thành
                                                    </option>

                                                <?php elseif ($order['trang_thai'] == 'hoan_thanh'): ?>

                                                    <option value="hoan_thanh" selected>
                                                        Hoàn thành
                                                    </option>

                                                <?php elseif ($order['trang_thai'] == 'da_huy'): ?>

                                                    <option value="da_huy" selected>
                                                        Đã hủy
                                                    </option>

                                                <?php endif; ?>

                                            </select>

                                            <?php if (!in_array($order['trang_thai'], ['hoan_thanh', 'da_huy'])): ?>

                                                <button
                                                    type="submit"
                                                    name="update_status"
                                                    class="btn btn-success update-btn">
                                                    Lưu
                                                </button>

                                            <?php endif; ?>

                                        </form>

                                    </td>

                                    <td class="text-center">

                                        <button
                                            type="button"
                                            class="btn btn-outline-success view-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#orderModal<?php echo $order['id']; ?>">

                                            <i class="fas fa-eye"></i>

                                        </button>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

<!-- MODAL -->

<?php foreach ($orders as $order): ?>

    <?php

    $stmt = $db->prepare("
    SELECT 
        ctdh.*,
        sp.hinh_anh
    FROM chitietdonhang ctdh
    LEFT JOIN sanpham sp ON sp.id = ctdh.san_pham_id
    WHERE ctdh.don_hang_id = ?
");

    $stmt->execute([$order['id']]);

    $items = $stmt->fetchAll();

    ?>

    <div
        class="modal fade order-modal"
        id="orderModal<?php echo $order['id']; ?>"
        tabindex="-1">

        <div class="modal-dialog modal-xl modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">

                        Chi tiết đơn hàng #<?php echo $order['ma_don_hang']; ?>

                    </h5>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>

                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-6">

                            <div class="info-box">

                                <div class="info-title">
                                    Thông tin người nhận
                                </div>

                                <p>
                                    <strong>Họ tên:</strong>
                                    <?php echo htmlspecialchars($order['ho_ten_nguoi_nhan']); ?>
                                </p>

                                <p>
                                    <strong>SĐT:</strong>
                                    <?php echo htmlspecialchars($order['dien_thoai_nguoi_nhan']); ?>
                                </p>

                                <p class="mb-0">
                                    <strong>Địa chỉ:</strong>
                                    <?php echo htmlspecialchars($order['dia_chi_giao_hang']); ?>
                                </p>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="info-box">

                                <div class="info-title">
                                    Thông tin đơn hàng
                                </div>

                                <p>
                                    <strong>Ngày đặt:</strong>
                                    <?php echo date('d/m/Y H:i', strtotime($order['ngay_dat'])); ?>
                                </p>

                                <p>
                                    <strong>Thanh toán:</strong>
                                    <?php echo htmlspecialchars($order['phuong_thuc_thanh_toan']); ?>
                                </p>

                                <p class="mb-0">
                                    <strong>Ghi chú:</strong>

                                    <?php echo !empty($order['ghi_chu']) ? nl2br(htmlspecialchars($order['ghi_chu'])) : 'Không có'; ?>

                                </p>

                            </div>

                        </div>

                    </div>

                    <div class="table-responsive mt-3">

                        <table class="table product-table">

                            <thead>

                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Số lượng</th>
                                    <th>Đơn giá</th>
                                    <th>Thành tiền</th>
                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($items as $item): ?>

                                    <tr>

                                        <td>
                                            <div class="d-flex align-items-center gap-2">

                                                <img
                                                    src="<?php echo BASE_URL . '/public/assets/images/products/' . $item['hinh_anh']; ?>"
                                                    style="width:50px;height:50px;object-fit:cover;border-radius:10px;border:1px solid #eee;"
                                                    onerror="this.src='<?php echo BASE_URL . '/public/assets/images/no-image.png'; ?>'">

                                                <span>
                                                    <?php echo htmlspecialchars($item['ten_san_pham']); ?>
                                                </span>

                                            </div>
                                        </td>

                                        <td>
                                            <?php echo $item['so_luong']; ?>
                                        </td>

                                        <td>
                                            <?php echo formatPrice($item['gia']); ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?php echo formatPrice($item['thanh_tien']); ?>
                                            </strong>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                            <tfoot>

                                <?php

                                $giam_gia =
                                    ($order['tong_tien'] + $order['phi_ship'])
                                    - $order['tong_thanh_toan'];

                                if ($giam_gia < 0) {
                                    $giam_gia = 0;
                                }

                                ?>

                                <tr>

                                    <th colspan="3" class="text-end">
                                        Tạm tính:
                                    </th>

                                    <th>
                                        <?php echo formatPrice($order['tong_tien']); ?>
                                    </th>

                                </tr>

                                <?php if ($giam_gia > 0): ?>

                                    <tr>

                                        <th colspan="3" class="text-end">
                                            Giảm giá:
                                        </th>

                                        <th class="text-danger">

                                            -<?php echo formatPrice($giam_gia); ?>

                                        </th>

                                    </tr>

                                <?php endif; ?>

                                <tr>

                                    <th colspan="3" class="text-end">
                                        Phí vận chuyển:
                                    </th>

                                    <th>

                                        <?php
                                        if ($order['phi_ship'] > 0) {

                                            echo formatPrice($order['phi_ship']);
                                        } else {

                                            echo '<span class="text-success">Miễn phí</span>';
                                        }
                                        ?>

                                    </th>

                                </tr>

                                <tr>

                                    <th colspan="3" class="text-end">
                                        Tổng thanh toán:
                                    </th>

                                    <th class="text-danger">
                                        <?php echo formatPrice($order['tong_thanh_toan']); ?>
                                    </th>

                                </tr>

                            </tfoot>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>

<?php endforeach; ?>

<script>
    setTimeout(function() {

        const toast = document.getElementById('successToast');

        if (toast) {

            toast.style.transition = '0.4s';

            toast.style.opacity = '0';

            toast.style.transform = 'translateY(-20px)';

            setTimeout(() => {

                toast.remove();

            }, 400);
        }

    }, 3000);
</script>

<?php include '../includes/footer.php'; ?>