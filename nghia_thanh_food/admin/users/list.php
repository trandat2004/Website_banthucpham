<?php
// Set múi giờ Việt Nam (UTC+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once __DIR__ . '/../includes/check_auth.php';
$page_title = 'Quản lý người dùng';

require_once '../includes/config.php';
include '../includes/header.php';
include '../includes/sidebar.php';

$db = getDB();

// Xử lý khóa/mở khóa tài khoản
if (isset($_POST['toggle_status']) && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $current_status = $db->query("SELECT trang_thai FROM users WHERE id = $user_id")->fetchColumn();
    $new_status = $current_status ? 0 : 1;
    $stmt = $db->prepare("UPDATE users SET trang_thai = ? WHERE id = ?");
    $stmt->execute([$new_status, $user_id]);
    header("Location: list.php?msg=" . ($new_status ? "Đã mở khóa tài khoản" : "Đã khóa tài khoản"));
    exit;
}

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Lấy ngày hôm nay theo giờ Việt Nam
$today_vn = date('Y-m-d');

// Đếm tổng số users (SQLite)
$count_sql = "SELECT COUNT(*) FROM users WHERE vai_tro = 0";
$count_params = [];
if ($search) {
    $count_sql .= " AND (ho_ten LIKE ? OR email LIKE ?)";
    $count_params = ["%$search%", "%$search%"];
}
$stmt = $db->prepare($count_sql);
$stmt->execute($count_params);
$total_users = $stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Lấy danh sách users có phân trang và tìm kiếm (SQLite)
$sql = "SELECT * FROM users WHERE vai_tro = 0";
$params = [];
if ($search) {
    $sql .= " AND (ho_ten LIKE ? OR email LIKE ?)";
    $params = ["%$search%", "%$search%"];
}
$sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Lấy thống kê cho SQLite (dùng ngày Việt Nam)
$stmt = $db->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN trang_thai = 1 THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN trang_thai = 0 THEN 1 ELSE 0 END) as locked,
    SUM(CASE WHEN DATE(ngay_tao) = ? THEN 1 ELSE 0 END) as today_new
    FROM users WHERE vai_tro = 0");
$stmt->execute([$today_vn]);
$stats = $stmt->fetch();

// Xử lý xem chi tiết
$user_detail = null;
$user_orders = [];
if (isset($_GET['view_id'])) {
    $view_id = intval($_GET['view_id']);
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$view_id]);
    $user_detail = $stmt->fetch();

    // Lấy danh sách đơn hàng từ bảng donhang
    try {
        $stmt = $db->prepare("SELECT * FROM donhang WHERE user_id = ? ORDER BY ngay_dat DESC LIMIT 10");
        $stmt->execute([$view_id]);
        $user_orders = $stmt->fetchAll();

        // Lấy chi tiết đơn hàng cho mỗi đơn
        foreach ($user_orders as &$order) {
            $stmt_detail = $db->prepare("SELECT * FROM chitietdonhang WHERE don_hang_id = ?");
            $stmt_detail->execute([$order['id']]);
            $order['chi_tiet'] = $stmt_detail->fetchAll();
        }
    } catch (Exception $e) {
        $user_orders = [];
    }
}
?>

<style>
    /* 🚫 KHÓA SCROLL TOÀN TRANG ADMIN */
    html,
    body {
        height: 100%;
        overflow: hidden;
    }

    /* KHUNG CHÍNH */
    .user-page {
        height: calc(100vh - 0px);
        display: flex;
        flex-direction: column;
        padding: 20px;
        background: #f1f5f9;
    }

    /* TITLE CỐ ĐỊNH */
    .page-title {
        font-size: 28px;
        font-weight: 800;
        margin-bottom: 20px;
        flex: 0 0 auto;
        color: #0f172a;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* STATS CARDS */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
        flex: 0 0 auto;
    }

    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s;
        border: 1px solid #e2e8f0;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
    }

    .stat-info h3 {
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 8px;
    }

    .stat-number {
        font-size: 32px;
        font-weight: 800;
        color: #0f172a;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        background: #eef2ff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #3b82f6;
        font-size: 24px;
    }

    /* SEARCH BAR */
    .search-bar {
        background: white;
        border-radius: 16px;
        padding: 15px 20px;
        margin-bottom: 20px;
        display: flex;
        gap: 12px;
        align-items: center;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        flex: 0 0 auto;
    }

    .search-input-wrapper {
        flex: 1;
        position: relative;
    }

    .search-input-wrapper i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }

    .search-input-wrapper input {
        width: 100%;
        padding: 12px 15px 12px 45px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        font-size: 14px;
        transition: all 0.2s;
    }

    .search-input-wrapper input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .search-btn,
    .reset-btn {
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
    }

    .search-btn {
        background: #3b82f6;
        color: white;
    }

    .search-btn:hover {
        background: #2563eb;
    }

    .reset-btn {
        background: #f1f5f9;
        color: #475569;
    }

    .reset-btn:hover {
        background: #e2e8f0;
    }

    /* TABLE CARD */
    .table-box {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
    }

    /* TABLE SCROLL */
    .table-scroll {
        flex: 1;
        overflow: auto;
    }

    .table-scroll::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .table-scroll::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    .table-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 20px;
    }

    .table-scroll::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* TABLE */
    .table-modern {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table-modern thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f8fafc;
        padding: 16px;
        font-weight: 700;
        color: #334155;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e2e8f0;
    }

    .table-modern tbody td {
        padding: 16px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
        color: #0f172a;
    }

    .table-modern tbody tr:hover {
        background: #f8fafc;
    }

    /* STATUS */
    .status-active {
        background: #dcfce7;
        color: #166534;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .status-lock {
        background: #fee2e2;
        color: #991b1b;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    /* ACTION BUTTONS */
    .action-buttons {
        display: flex;
        gap: 8px;
    }

    .btn-icon {
        padding: 6px 10px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
    }

    .btn-view {
        background: #eef2ff;
        color: #3b82f6;
    }

    .btn-view:hover {
        background: #3b82f6;
        color: white;
    }

    .btn-lock {
        background: #fee2e2;
        color: #dc2626;
    }

    .btn-lock:hover {
        background: #dc2626;
        color: white;
    }

    .btn-unlock {
        background: #dcfce7;
        color: #16a34a;
    }

    .btn-unlock:hover {
        background: #16a34a;
        color: white;
    }

    /* PAGINATION */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        padding: 20px;
        border-top: 1px solid #e2e8f0;
        background: white;
    }

    .pagination a,
    .pagination span {
        padding: 8px 14px;
        border-radius: 10px;
        text-decoration: none;
        color: #475569;
        font-size: 14px;
        transition: all 0.2s;
        border: 1px solid #e2e8f0;
    }

    .pagination a:hover {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    .pagination .active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    /* MODAL */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: 24px;
        max-width: 1200px;
        width: 95%;
        max-height: 85vh;
        overflow-y: auto;
        padding: 30px;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e2e8f0;
    }

    .modal-header h2 {
        font-size: 22px;
        font-weight: 700;
        color: #0f172a;
    }

    .close-modal {
        cursor: pointer;
        font-size: 28px;
        color: #94a3b8;
        transition: all 0.2s;
    }

    .close-modal:hover {
        color: #dc2626;
    }

    .info-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .info-label {
        font-weight: 600;
        width: 140px;
        color: #64748b;
    }

    .info-value {
        flex: 1;
        color: #0f172a;
    }

    /* ORDER CARDS */
    .order-card {
        background: #f8fafc;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }

    .order-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e2e8f0;
    }

    .order-title {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
    }

    /* ========== TRẠNG THÁI ĐƠN HÀNG - MÀU SẮC RÕ RÀNG ========== */
    .order-status {
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    /* Hoàn thành - Màu XANH LÁ */
    .status-completed {
        background: #22c55e !important;
        color: white !important;
        border: none !important;
    }

    /* Đang xử lý - Màu XANH DƯƠNG */
    .status-processing {
        background: #3b82f6 !important;
        color: white !important;
        border: none !important;
    }

    /* Đã hủy - Màu ĐỎ */
    .status-cancelled {
        background: #ef4444 !important;
        color: white !important;
        border: none !important;
    }

    /* Chờ xác nhận - Màu VÀNG CAM */
    .status-pending {
        background: #f59e0b !important;
        color: white !important;
        border: none !important;
    }

    /* Đang giao - Màu TÍM */
    .status-shipping {
        background: #a855f7 !important;
        color: white !important;
        border: none !important;
    }

    .order-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 12px;
        margin-bottom: 15px;
    }

    .order-info-item {
        display: flex;
        gap: 8px;
        font-size: 13px;
    }

    .order-info-label {
        font-weight: 600;
        color: #64748b;
        min-width: 100px;
    }

    .order-info-value {
        color: #0f172a;
    }

    .order-details-table {
        width: 100%;
        margin-top: 15px;
        border-collapse: collapse;
    }

    .order-details-table th {
        background: #e2e8f0;
        padding: 10px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
    }

    .order-details-table td {
        padding: 10px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 13px;
    }

    .order-total {
        text-align: right;
        margin-top: 15px;
        padding-top: 12px;
        border-top: 1px solid #e2e8f0;
        font-weight: 700;
        font-size: 16px;
        color: #0f172a;
    }

    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-success {
        background: #dcfce7;
        color: #166534;
    }

    .badge-warning {
        background: #fed7aa;
        color: #9a3412;
    }

    .badge-info {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .alert {
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        background: #dcfce7;
        color: #166534;
        border-left: 4px solid #22c55e;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
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

<div class="col-md-10 user-page">

    <!-- Hiển thị thông báo -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert" id="alert-msg">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['msg']) ?>
        </div>
        <script>
            setTimeout(() => {
                document.getElementById('alert-msg')?.remove();
            }, 3000);
        </script>
    <?php endif; ?>

    <!-- TITLE -->
    <div class="page-title">
        <span><i class="fas fa-users mr-3"></i> Quản lý người dùng</span>
    </div>

    <!-- STATS CARDS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <h3><i class="fas fa-users"></i> Tổng khách hàng</h3>
                <div class="stat-number"><?= number_format($stats['total']) ?></div>
            </div>
            <div class="stat-icon"><i class="fas fa-users"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3><i class="fas fa-circle"></i> Đang hoạt động</h3>
                <div class="stat-number"><?= number_format($stats['active']) ?></div>
            </div>
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3><i class="fas fa-lock"></i> Đã khóa</h3>
                <div class="stat-number"><?= number_format($stats['locked']) ?></div>
            </div>
            <div class="stat-icon"><i class="fas fa-ban"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3><i class="fas fa-calendar-day"></i> Hôm nay mới</h3>
                <div class="stat-number"><?= number_format($stats['today_new']) ?></div>
            </div>
            <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
        </div>
    </div>

    <!-- SEARCH BAR -->
    <div class="search-bar">
        <form method="GET" style="display: flex; gap: 12px; flex: 1;">
            <div class="search-input-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Tìm theo tên hoặc email..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="search-btn"><i class="fas fa-search"></i> Tìm kiếm</button>
            <?php if ($search): ?>
                <a href="list.php" class="reset-btn"><i class="fas fa-times"></i> Xóa</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- TABLE CARD -->
    <div class="table-box">
        <div class="table-scroll">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Địa chỉ</th>
                        <th>Ngày đăng ký</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) == 0): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 60px;">
                                <i class="fas fa-user-slash" style="font-size: 48px; color: #cbd5e1;"></i>
                                <p style="margin-top: 15px; color: #64748b;">Không tìm thấy người dùng nào</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><strong>#<?= $u['id'] ?></strong></td>
                                <td><?= htmlspecialchars($u['ho_ten']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= htmlspecialchars($u['dien_thoai'] ?: '---') ?></td>
                                <td><?= htmlspecialchars($u['dia_chi'] ?: '---') ?></td>
                                <td><?= date('d/m/Y H:i:s', strtotime($u['ngay_tao'])) ?></td>
                                <td>
                                    <?php if ($u['trang_thai']): ?>
                                        <span class="status-active"><i class="fas fa-circle" style="font-size: 8px;"></i> Hoạt động</span>
                                    <?php else: ?>
                                        <span class="status-lock"><i class="fas fa-lock"></i> Đã khóa</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?view_id=<?= $u['id'] ?>" class="btn-icon btn-view" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn <?= $u['trang_thai'] ? 'khóa' : 'mở khóa' ?> tài khoản này?')">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" name="toggle_status" class="btn-icon <?= $u['trang_thai'] ? 'btn-lock' : 'btn-unlock' ?>" title="<?= $u['trang_thai'] ? 'Khóa tài khoản' : 'Mở khóa' ?>">
                                                <i class="fas <?= $u['trang_thai'] ? 'fa-lock' : 'fa-unlock-alt' ?>"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>"><i class="fas fa-chevron-left"></i></a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>"><i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL CHI TIẾT -->
<?php if ($user_detail): ?>
    <div class="modal active" id="detailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-circle"></i> Chi tiết khách hàng</h2>
                <span class="close-modal" onclick="window.location.href='list.php'">&times;</span>
            </div>

            <div class="info-row">
                <div class="info-label"><i class="fas fa-user"></i> Họ tên:</div>
                <div class="info-value"><?= htmlspecialchars($user_detail['ho_ten']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label"><i class="fas fa-envelope"></i> Email:</div>
                <div class="info-value"><?= htmlspecialchars($user_detail['email']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label"><i class="fas fa-phone"></i> Số điện thoại:</div>
                <div class="info-value"><?= htmlspecialchars($user_detail['dien_thoai'] ?: 'Chưa cập nhật') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label"><i class="fas fa-map-marker-alt"></i> Địa chỉ:</div>
                <div class="info-value"><?= htmlspecialchars($user_detail['dia_chi'] ?: 'Chưa cập nhật') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label"><i class="fas fa-calendar"></i> Ngày đăng ký:</div>
                <div class="info-value"><?= date('d/m/Y H:i:s', strtotime($user_detail['ngay_tao'])) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label"><i class="fas fa-circle"></i> Trạng thái:</div>
                <div class="info-value">
                    <?= $user_detail['trang_thai'] ? '<span class="status-active">Hoạt động</span>' : '<span class="status-lock">Đã khóa</span>' ?>
                </div>
            </div>

            <h3 style="margin: 25px 0 15px 0; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-shopping-bag"></i> Danh sách đơn hàng
                <span class="badge badge-info"><?= count($user_orders) ?> đơn</span>
            </h3>

            <?php if (count($user_orders) > 0): ?>
                <?php foreach ($user_orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-title">
                                <i class="fas fa-receipt"></i> Đơn hàng #<?= htmlspecialchars($order['ma_don_hang']) ?>
                            </div>
                            <div>
                                <?php
                                // Xác định class và icon cho trạng thái đơn hàng
                                $status_text = $order['trang_thai'] ?? 'Đang xử lý';
                                $status_class = 'status-pending';
                                $status_icon = 'fa-clock';

                                // Chuẩn hóa trạng thái (không phân biệt hoa thường, có dấu không dấu)
                                $status_lower = mb_strtolower($status_text, 'UTF-8');

                                // HOÀN THÀNH -> MÀU XANH LÁ
                                if (
                                    strpos($status_lower, 'hoàn thành') !== false ||
                                    strpos($status_lower, 'hoan thanh') !== false ||
                                    strpos($status_lower, 'completed') !== false
                                ) {
                                    $status_class = 'status-completed';
                                    $status_icon = 'fa-check-circle';
                                }
                                // ĐANG XỬ LÝ -> MÀU XANH DƯƠNG
                                elseif (
                                    strpos($status_lower, 'đang xử lý') !== false ||
                                    strpos($status_lower, 'dang xu ly') !== false ||
                                    strpos($status_lower, 'processing') !== false
                                ) {
                                    $status_class = 'status-processing';
                                    $status_icon = 'fa-spinner fa-pulse';
                                }
                                // ĐÃ HỦY -> MÀU ĐỎ
                                elseif (
                                    strpos($status_lower, 'đã hủy') !== false ||
                                    strpos($status_lower, 'da huy') !== false ||
                                    strpos($status_lower, 'cancelled') !== false
                                ) {
                                    $status_class = 'status-cancelled';
                                    $status_icon = 'fa-ban';
                                }
                                // CHỜ XÁC NHẬN -> MÀU CAM
                                elseif (
                                    strpos($status_lower, 'chờ xác nhận') !== false ||
                                    strpos($status_lower, 'cho xac nhan') !== false ||
                                    strpos($status_lower, 'pending') !== false
                                ) {
                                    $status_class = 'status-pending';
                                    $status_icon = 'fa-hourglass-half';
                                }
                                // ĐANG GIAO -> MÀU TÍM
                                elseif (
                                    strpos($status_lower, 'đang giao') !== false ||
                                    strpos($status_lower, 'dang giao') !== false ||
                                    strpos($status_lower, 'shipping') !== false
                                ) {
                                    $status_class = 'status-shipping';
                                    $status_icon = 'fa-truck';
                                }
                                ?>
                                <span class="order-status <?= $status_class ?>">
                                    <i class="fas <?= $status_icon ?>"></i> <?= htmlspecialchars($status_text) ?>
                                </span>
                            </div>
                        </div>

                        <div class="order-info-grid">
                            <div class="order-info-item">
                                <span class="order-info-label">Ngày đặt:</span>
                                <span class="order-info-value"><?= date('d/m/Y H:i:s', strtotime($order['ngay_dat'])) ?></span>
                            </div>
                            <div class="order-info-item">
                                <span class="order-info-label">Người nhận:</span>
                                <span class="order-info-value"><?= htmlspecialchars($order['ho_ten_nguoi_nhan']) ?></span>
                            </div>
                            <div class="order-info-item">
                                <span class="order-info-label">SĐT nhận:</span>
                                <span class="order-info-value"><?= htmlspecialchars($order['dien_thoai_nguoi_nhan']) ?></span>
                            </div>
                            <div class="order-info-item">
                                <span class="order-info-label">Địa chỉ giao:</span>
                                <span class="order-info-value"><?= htmlspecialchars($order['dia_chi_giao_hang']) ?></span>
                            </div>
                            <div class="order-info-item">
                                <span class="order-info-label">Thành phố:</span>
                                <span class="order-info-value"><?= htmlspecialchars($order['thanh_pho']) ?></span>
                            </div>
                            <div class="order-info-item">
                                <span class="order-info-label">Thanh toán:</span>
                                <span class="order-info-value"><?= htmlspecialchars($order['phuong_thuc_thanh_toan']) ?></span>
                            </div>
                        </div>

                        <?php if (!empty($order['ghi_chu'])): ?>
                            <div class="order-info-item" style="margin-bottom: 15px;">
                                <span class="order-info-label">Ghi chú:</span>
                                <span class="order-info-value"><?= htmlspecialchars($order['ghi_chu']) ?></span>
                            </div>
                        <?php endif; ?>

                        <table class="order-details-table">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Đơn giá</th>
                                    <th>Số lượng</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($order['chi_tiet'])): ?>
                                    <?php foreach ($order['chi_tiet'] as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['ten_san_pham']) ?></td>
                                            <td><?= number_format($item['gia']) ?>đ</span></td>
                                            <td><?= $item['so_luong'] ?></span></td>
                                            <td><?= number_format($item['thanh_tien']) ?>đ</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center;">Không có chi tiết đơn hàng</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <div class="order-total">
                            <div style="display: flex; justify-content: flex-end; gap: 20px;">
                                <span>Tạm tính: <?= number_format($order['tong_tien']) ?>đ</span>
                                <span>Phí ship: <?= number_format($order['phi_ship']) ?>đ</span>
                                <span style="color: #dc2626; font-size: 18px;">Tổng thanh toán: <?= number_format($order['tong_thanh_toan']) ?>đ</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 12px;">
                    <i class="fas fa-shopping-cart" style="font-size: 48px; color: #cbd5e1;"></i>
                    <p style="margin-top: 15px; color: #64748b;">Khách hàng chưa có đơn hàng nào</p>
                </div>
            <?php endif; ?>

            <div style="margin-top: 25px; text-align: right;">
                <a href="list.php" class="reset-btn" style="display: inline-block; text-decoration: none;">Đóng</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    // Tự động đóng modal khi click ra ngoài
    document.addEventListener('click', function(e) {
        const modal = document.querySelector('.modal.active');
        if (modal && e.target === modal) {
            window.location.href = 'list.php';
        }
    });
</script>

<?php include '../includes/footer.php'; ?>