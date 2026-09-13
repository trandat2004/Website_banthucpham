<?php
require_once '../includes/config.php';

if (!isLoggedIn()) {
    redirect('/public/pages/login.php');
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// Flash messages for cancel
if (isset($_SESSION['cancel_success'])) {
    $cancel_success = $_SESSION['cancel_success'];
    unset($_SESSION['cancel_success']);
}
if (isset($_SESSION['cancel_error'])) {
    $cancel_error = $_SESSION['cancel_error'];
    unset($_SESSION['cancel_error']);
}

/* =========================
   UPDATE PROFILE (with avatar)
========================= */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {

    $ho_ten = safeInput($_POST['ho_ten']);
    $dien_thoai = safeInput($_POST['dien_thoai']);
    $dia_chi = safeInput($_POST['dia_chi']);

    // Upload avatar
    $avatar_path = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['avatar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_name = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/public/assets/images/avatars/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $target = $upload_dir . $new_name;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                $avatar_path = '/public/assets/images/avatars/' . $new_name;
            }
        }
    }

    $sql = "UPDATE users SET ho_ten = ?, dien_thoai = ?, dia_chi = ?";
    $params = [$ho_ten, $dien_thoai, $dia_chi];

    if ($avatar_path) {
        $sql .= ", avatar = ?";
        $params[] = $avatar_path;
    }

    $sql .= " WHERE id = ?";
    $params[] = $user_id;

    $stmt = $db->prepare($sql);
    if ($stmt->execute($params)) {
        $_SESSION['user_name'] = $ho_ten;
        $success = "Cập nhật thông tin thành công!";
    }
}

/* =========================
   CHANGE PASSWORD
========================= */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {

    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $stmt = $db->prepare("SELECT mat_khau FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_pwd = $stmt->fetch();

    if (password_verify($old_password, $user_pwd['mat_khau'])) {
        if (strlen($new_password) >= 6) {
            if ($new_password == $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET mat_khau = ? WHERE id = ?");
                if ($stmt->execute([$hashed_password, $user_id])) {
                    $pwd_success = "Đổi mật khẩu thành công!";
                } else {
                    $pwd_error = "Có lỗi xảy ra!";
                }
            } else {
                $pwd_error = "Mật khẩu xác nhận không khớp!";
            }
        } else {
            $pwd_error = "Mật khẩu mới phải có ít nhất 6 ký tự!";
        }
    } else {
        $pwd_error = "Mật khẩu cũ không đúng!";
    }
}

/* =========================
   CANCEL ORDER (with redirect & flash)
========================= */
if (isset($_GET['cancel_order']) && is_numeric($_GET['cancel_order'])) {
    $order_id = (int)$_GET['cancel_order'];
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

    $stmt = $db->prepare("SELECT trang_thai FROM donhang WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    if ($order && in_array($order['trang_thai'], ['cho_xac_nhan', 'dang_xu_ly'])) {
        $stmt = $db->prepare("UPDATE donhang SET trang_thai = 'da_huy' WHERE id = ?");
        if ($stmt->execute([$order_id])) {
            $_SESSION['cancel_success'] = "Đơn hàng đã được hủy thành công!";
        } else {
            $_SESSION['cancel_error'] = "Có lỗi xảy ra khi hủy đơn hàng!";
        }
    } else {
        $_SESSION['cancel_error'] = "Không thể hủy đơn hàng này!";
    }

    header("Location: ?page=" . $current_page . "#orders-tab");
    exit;
}

/* =========================
   GET USER
========================= */
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$avatar = !empty($user['avatar']) ? $user['avatar'] : '/public/assets/images/default-avatar.png';

/* =========================
   GET ORDERS WITH PAGINATION
========================= */
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total number of orders
$stmt = $db->prepare("SELECT COUNT(*) as total FROM donhang WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_orders = $stmt->fetch()['total'];
$total_pages = ceil($total_orders / $limit);

// Adjust page if out of range
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) {
    header("Location: ?page=" . $total_pages . "#orders-tab");
    exit;
}

// Get orders for current page
$stmt = $db->prepare("
    SELECT * 
    FROM donhang 
    WHERE user_id = ?
    ORDER BY ngay_dat DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

$page_title = 'Tài khoản của tôi';

include '../includes/header.php';
include '../includes/navbar.php';
?>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .profile-page {
        background: #f5f6fa;
        min-height: 100vh;
        padding: 30px 0;
    }

    /* ======================
STICKY SIDEBAR - FIXED
====================== */
    .profile-sidebar {
        position: fixed;
        top: 90px;
        width: 320px;

        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        z-index: 999;
    }

    .profile-top {
        background: linear-gradient(135deg, #0d683f, #74c947);
        padding: 30px 20px;
        text-align: center;
        color: white;
    }

    .profile-avatar {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        margin: 0 auto 15px;
        overflow: hidden;
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        background: white;
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-name {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .profile-email {
        font-size: 12px;
        opacity: 0.9;
    }

    .profile-menu .list-group-item {
        border: none;
        padding: 14px 20px;
        font-size: 14px;
        font-weight: 500;
        color: #333;
        transition: all 0.2s;
    }

    .profile-menu .list-group-item i {
        width: 24px;
        color: #666;
    }

    .profile-menu .list-group-item.active {
        background: #e8f5e9;
        color: #0d683f;
        border-left: 3px solid #0d683f;
    }

    .profile-menu .list-group-item.active i {
        color: #0d683f;
    }

    .profile-menu .list-group-item:hover:not(.active) {
        background: #f8f8f8;
        color: #0d683f;
    }

    /* ======================
CONTENT CARD
====================== */
    .profile-card {
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .profile-card-header {
        padding: 20px 24px;
        border-bottom: 1px solid #efefef;
    }

    .profile-card-header h4 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: #222;
    }

    .profile-card-body {
        padding: 24px;
    }

    /* ======================
FORM STYLES
====================== */
    .form-label {
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
        font-size: 14px;
    }

    .form-control {
        border-radius: 10px;
        border: 1px solid #ddd;
        padding: 10px 12px;
        font-size: 14px;
        transition: all 0.2s;
    }

    .form-control:focus {
        border-color: #0d683f;
        box-shadow: 0 0 0 2px rgba(13, 104, 63, 0.1);
    }

    textarea.form-control {
        resize: vertical;
    }

    /* Password wrapper */
    .password-wrapper {
        position: relative;
    }

    .password-wrapper input {
        padding-right: 45px;
    }

    .password-toggle {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #999;
        background: transparent;
        border: none;
        font-size: 18px;
        z-index: 10;
    }

    .password-toggle:hover {
        color: #0d683f;
    }

    .save-btn {
        background: linear-gradient(90deg, #74c947, #0d683f);
        color: white;
        border: none;
        padding: 10px 28px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.2s;
    }

    .save-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(13, 104, 63, 0.3);
    }

    /* ======================
AVATAR UPLOAD
====================== */
    .avatar-preview-wrapper {
        text-align: center;
        margin-bottom: 16px;
    }

    .avatar-preview {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #0d683f;
        padding: 3px;
        background: white;
    }

    .avatar-upload {
        text-align: center;
        position: relative;
        display: inline-block;
        width: 100%;
    }

    .avatar-upload-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #0d683f;
        color: white;
        padding: 8px 20px;
        border-radius: 50px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .avatar-upload-label:hover {
        background: #0a5232;
    }

    .avatar-upload input {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        cursor: pointer;
    }

    /* ======================
ORDER CARD - SHOPEE STYLE
====================== */
    .order-item {
        background: #fff;
        border-radius: 12px;
        margin-bottom: 16px;
        border: 1px solid #eee;
        overflow: hidden;
        transition: all 0.2s;
    }

    .order-item:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .order-header {
        background: #fafafa;
        padding: 12px 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .order-shop-info {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .order-shop-icon {
        width: 24px;
        height: 24px;
        background: #0d683f;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
    }

    .order-shop-name {
        font-weight: 600;
        font-size: 14px;
        color: #222;
    }

    .order-status {
        font-size: 13px;
        font-weight: 600;
    }

    .status-warning {
        color: #ff9800;
    }

    .status-info {
        color: #0d683f;
    }

    .status-primary {
        color: #2196f3;
    }

    .status-success {
        color: #4caf50;
    }

    .status-danger {
        color: #999;
    }

    .order-products {
        padding: 16px 20px;
    }

    .order-product-item {
        display: flex;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f5f5f5;
    }

    .order-product-item:last-child {
        border-bottom: none;
    }

    .order-product-img {
        width: 70px;
        height: 70px;
        background: #f5f5f5;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ccc;
        font-size: 28px;
        flex-shrink: 0;
    }

    .order-product-info {
        flex: 1;
    }

    .order-product-name {
        font-size: 14px;
        font-weight: 500;
        color: #222;
        margin-bottom: 6px;
        display: -webkit-box;
        line-clamp: 2;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .order-product-price {
        font-size: 13px;
        color: #666;
    }

    .order-product-quantity {
        font-size: 13px;
        color: #999;
        text-align: right;
        min-width: 60px;
    }

    .order-product-total {
        text-align: right;
        min-width: 110px;
    }

    .order-product-total .price {
        font-size: 15px;
        font-weight: 600;
        color: #0d683f;
    }

    .order-footer {
        background: #fafafa;
        padding: 12px 20px;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .order-total-label {
        font-size: 14px;
        color: #666;
    }

    .order-total-price {
        font-size: 20px;
        font-weight: 700;
        color: #0d683f;
    }

    .order-actions {
        display: flex;
        gap: 12px;
    }

    .btn-order {
        padding: 8px 20px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s;
        cursor: pointer;
    }

    .btn-view {
        background: white;
        border: 1px solid #ddd;
        color: #333;
    }

    .btn-view:hover {
        border-color: #0d683f;
        color: #0d683f;
    }

    .btn-cancel {
        background: white;
        border: 1px solid #dc3545;
        color: #dc3545;
    }

    .btn-cancel:hover {
        background: #dc3545;
        color: white;
    }

    .empty-order {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-order i {
        font-size: 80px;
        color: #ddd;
        margin-bottom: 20px;
    }

    .empty-order h4 {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .empty-order p {
        color: #999;
        font-size: 14px;
    }

    /* ======================
PAGINATION
====================== */
    .pagination-container {
        margin-top: 30px;
        display: flex;
        justify-content: center;
    }

    .pagination .page-link {
        color: #0d683f;
        border-radius: 8px;
        margin: 0 4px;
        border: 1px solid #dee2e6;
        padding: 8px 14px;
        font-size: 14px;
    }

    .pagination .page-item.active .page-link {
        background-color: #0d683f;
        border-color: #0d683f;
        color: white;
    }

    .pagination .page-link:hover {
        background-color: #e8f5e9;
        color: #0a5232;
    }

    /* ======================
MODAL
====================== */
    .modal-content {
        border-radius: 16px;
        border: none;
    }

    .modal-header {
        background: linear-gradient(90deg, #0d683f, #74c947);
        color: white;
        border-bottom: none;
        padding: 16px 20px;
    }

    .modal-header .modal-title {
        font-size: 18px;
        font-weight: 600;
    }

    .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }

    .modal-body {
        padding: 20px;
    }

    .modal-product {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .modal-product:last-child {
        border-bottom: none;
    }

    .modal-product-info {
        flex: 1;
    }

    .modal-product-name {
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 4px;
    }

    .modal-product-price {
        font-size: 13px;
        color: #666;
    }

    .modal-product-total {
        font-size: 15px;
        font-weight: 600;
        color: #0d683f;
    }

    .modal-total {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #eee;
        text-align: right;
    }

    .modal-total span {
        font-size: 18px;
        font-weight: 700;
        color: #0d683f;
    }

    /* ======================
ALERTS
====================== */
    .alert {
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 20px;
        font-size: 14px;
    }

    /* ======================
RESPONSIVE
====================== */
    @media (max-width: 768px) {
        .profile-sidebar {
            position: relative;
            top: 0;
            margin-bottom: 20px;
        }

        .order-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .order-footer {
            flex-direction: column;
            align-items: flex-end;
        }

        .order-product-item {
            flex-wrap: wrap;
        }

        .order-product-total {
            width: 100%;
            text-align: left;
            margin-top: 8px;
        }
    }

    @media (max-width: 576px) {
        .profile-card-body {
            padding: 16px;
        }

        .order-products {
            padding: 12px;
        }
    }
</style>

<div class="profile-page">
    <div class="container">

        <?php if (isset($cancel_success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo $cancel_success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($cancel_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $cancel_error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">

            <!-- SIDEBAR - STICKY -->
            <div class="col-lg-3">
                <div class="profile-sidebar">
                    <div class="profile-top">
                        <div class="profile-avatar">
                            <img src="<?php echo BASE_URL . $avatar; ?>" alt="Avatar" id="sidebarAvatar">
                        </div>
                        <div class="profile-name">
                            <?php echo htmlspecialchars($user['ho_ten']); ?>
                        </div>
                        <div class="profile-email">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </div>
                    </div>
                    <div class="profile-menu list-group list-group-flush">
                        <a href="#" class="list-group-item active" data-bs-toggle="tab" data-bs-target="#profile-tab">
                            <i class="fas fa-user-circle"></i> Thông tin tài khoản
                        </a>
                        <a href="#" class="list-group-item" data-bs-toggle="tab" data-bs-target="#orders-tab">
                            <i class="fas fa-shopping-bag"></i> Đơn hàng của tôi
                        </a>
                        <a href="#" class="list-group-item" data-bs-toggle="tab" data-bs-target="#password-tab">
                            <i class="fas fa-lock"></i> Đổi mật khẩu
                        </a>
                        <a href="<?php echo BASE_URL; ?>/public/pages/logout.php" class="list-group-item text-danger">
                            <i class="fas fa-sign-out-alt"></i> Đăng xuất
                        </a>
                    </div>
                </div>
            </div>

            <!-- CONTENT -->
            <div class="col-lg-9 offset-lg-3">
                <div class="tab-content">

                    <!-- PROFILE TAB -->
                    <div class="tab-pane fade show active" id="profile-tab">
                        <div class="profile-card">
                            <div class="profile-card-header">
                                <h4><i class="fas fa-user-edit me-2" style="color:#0d683f;"></i>Thông tin tài khoản</h4>
                            </div>
                            <div class="profile-card-body">
                                <?php if (isset($success)): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" enctype="multipart/form-data">
                                    <div class="avatar-preview-wrapper">
                                        <img src="<?php echo BASE_URL . $avatar; ?>" class="avatar-preview" id="avatarPreview">
                                    </div>
                                    <div class="avatar-upload mb-4">
                                        <label class="avatar-upload-label">
                                            <i class="fas fa-camera"></i> Chọn ảnh đại diện
                                            <input type="file" name="avatar" accept="image/*" onchange="previewAvatar(this)">
                                        </label>
                                        <small class="text-muted d-block mt-2">Hỗ trợ: JPG, PNG, GIF (tối đa 2MB)</small>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Họ và tên</label>
                                            <input type="text" name="ho_ten" class="form-control"
                                                value="<?php echo htmlspecialchars($user['ho_ten']); ?>" required>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control"
                                                value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Số điện thoại</label>
                                            <input type="tel" name="dien_thoai" class="form-control"
                                                value="<?php echo htmlspecialchars($user['dien_thoai']); ?>">
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label">Địa chỉ</label>
                                        <textarea name="dia_chi" class="form-control" rows="3"><?php echo htmlspecialchars($user['dia_chi']); ?></textarea>
                                    </div>

                                    <button type="submit" name="update_profile" class="save-btn">
                                        <i class="fas fa-save me-2"></i>Cập nhật thông tin
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- ORDERS TAB -->
                    <div class="tab-pane fade" id="orders-tab">
                        <div class="profile-card">
                            <div class="profile-card-header">
                                <h4><i class="fas fa-shopping-bag me-2" style="color:#0d683f;"></i>Đơn hàng của tôi</h4>
                            </div>
                            <div class="profile-card-body">
                                <?php if (count($orders) > 0): ?>
                                    <?php foreach ($orders as $order): ?>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        $can_cancel = false;
                                        $status_icon = '';

                                        switch ($order['trang_thai']) {
                                            case 'cho_xac_nhan':
                                                $status_class = 'warning';
                                                $status_text = '⏳ Chờ xác nhận';
                                                $status_icon = 'fa-clock';
                                                $can_cancel = true;
                                                break;
                                            case 'dang_xu_ly':
                                                $status_class = 'info';
                                                $status_text = '🔄 Đang xử lý';
                                                $status_icon = 'fa-spinner fa-pulse';
                                                $can_cancel = true;
                                                break;
                                            case 'dang_giao':
                                                $status_class = 'primary';
                                                $status_text = '🚚 Đang giao';
                                                $status_icon = 'fa-truck';
                                                $can_cancel = false;
                                                break;
                                            case 'hoan_thanh':
                                                $status_class = 'success';
                                                $status_text = '✅ Hoàn thành';
                                                $status_icon = 'fa-check-circle';
                                                $can_cancel = false;
                                                break;
                                            case 'da_huy':
                                                $status_class = 'danger';
                                                $status_text = '❌ Đã hủy';
                                                $status_icon = 'fa-times-circle';
                                                $can_cancel = false;
                                                break;
                                        }

                                        // Lấy chi tiết đơn hàng an toàn
                                        $stmt_detail = $db->prepare("
                                            SELECT 
                                                chitietdonhang.*,
                                                sanpham.hinh_anh
                                            FROM chitietdonhang
                                            LEFT JOIN sanpham 
                                                ON chitietdonhang.san_pham_id = sanpham.id
                                            WHERE chitietdonhang.don_hang_id = ?
                                        ");
                                        $stmt_detail->execute([$order['id']]);
                                        $order_items = $stmt_detail->fetchAll();
                                        ?>

                                        <div class="order-item">
                                            <div class="order-header">
                                                <div class="order-shop-info">
                                                    <span class="order-shop-icon">
                                                        <i class="fas fa-store"></i>
                                                    </span>
                                                    <span class="order-shop-name">Mã đơn: #<?php echo $order['ma_don_hang']; ?></span>
                                                </div>
                                                <div class="order-status status-<?php echo $status_class; ?>">
                                                    <i class="fas <?php echo $status_icon; ?> me-1"></i>
                                                    <?php echo $status_text; ?>
                                                </div>
                                            </div>

                                            <div class="order-products">
                                                <?php foreach ($order_items as $index => $item): ?>
                                                    <?php if ($index < 2): ?>
                                                        <div class="order-product-item">
                                                            <div class="order-product-img">
                                                                <img
                                                                    src="<?php echo BASE_URL . '/public/assets/images/products/' . $item['hinh_anh']; ?>"
                                                                    alt=""
                                                                    style="width:100%;height:100%;object-fit:cover;border-radius:8px;">
                                                            </div>
                                                            <div class="order-product-info">
                                                                <div class="order-product-name">
                                                                    <?php echo htmlspecialchars($item['ten_san_pham']); ?>
                                                                </div>
                                                                <div class="order-product-price">
                                                                    <?php
                                                                    $gia_sp = isset($item['don_gia']) ? $item['don_gia'] : (isset($item['gia']) ? $item['gia'] : 0);
                                                                    echo formatPrice($gia_sp);
                                                                    ?>
                                                                </div>
                                                            </div>
                                                            <div class="order-product-quantity">
                                                                x<?php echo $item['so_luong']; ?>
                                                            </div>
                                                            <div class="order-product-total">
                                                                <div class="price">
                                                                    <?php
                                                                    $thanh_tien = isset($item['thanh_tien']) ? $item['thanh_tien'] : ($gia_sp * $item['so_luong']);
                                                                    echo formatPrice($thanh_tien);
                                                                    ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                <?php if (count($order_items) > 2): ?>
                                                    <div class="text-muted small mt-2">
                                                        <i class="fas fa-ellipsis-h me-1"></i> và
                                                        <?php echo (count($order_items) - 2); ?> sản phẩm khác
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="order-footer" style="flex-direction: column; align-items: flex-end; gap: 6px;">

                                                <!-- Tạm tính -->
                                                <div class="small text-muted">
                                                    Tạm tính:
                                                    <b><?php echo formatPrice($order['tong_tien']); ?></b>
                                                </div>

                                                <!-- Giảm giá -->
                                                <?php
                                                $giam_gia = $order['tong_tien'] - ($order['tong_thanh_toan'] - $order['phi_ship']);
                                                ?>

                                                <?php if ($giam_gia > 0): ?>
                                                    <div class="small text-danger">
                                                        Giảm giá:
                                                        <b>-<?php echo formatPrice($giam_gia); ?></b>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Ship -->
                                                <div class="small text-muted">
                                                    Phí vận chuyển:
                                                    <?php
                                                    if ($order['phi_ship'] > 0) {
                                                        echo '<b>' . formatPrice($order['phi_ship']) . '</b>';
                                                    } else {
                                                        echo '<span class="text-success"><b>Miễn phí</b></span>';
                                                    }
                                                    ?>
                                                </div>

                                                <!-- Tổng -->
                                                <div class="order-total-price">
                                                    Tổng thanh toán: <?php echo formatPrice($order['tong_thanh_toan']); ?>
                                                </div>

                                                <div class="order-actions mt-2">
                                                    <button type="button" class="btn-order btn-view"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#orderModal<?php echo $order['id']; ?>">
                                                        <i class="fas fa-eye me-1"></i> Xem chi tiết
                                                    </button>

                                                    <?php if ($can_cancel): ?>
                                                        <button type="button" class="btn-order btn-cancel"
                                                            onclick="confirmCancel(<?php echo $order['id']; ?>, '<?php echo $order['ma_don_hang']; ?>')">
                                                            <i class="fas fa-times me-1"></i> Hủy đơn
                                                        </button>
                                                    <?php endif; ?>
                                                </div>

                                            </div>
                                        </div>

                                        <!-- MODAL DETAIL -->
                                        <div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content" style="border-radius: 16px;">
                                                    <div class="modal-header" style="background: linear-gradient(90deg, #0d683f, #74c947); color: white; border-bottom: none;">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-receipt me-2"></i>
                                                            Chi tiết đơn hàng #<?php echo $order['ma_don_hang']; ?>
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body" style="padding: 20px;">

                                                        <!-- THÔNG TIN NGƯỜI NHẬN -->
                                                        <div style="background: #f8f9fa; padding: 15px; border-radius: 12px; margin-bottom: 20px;">
                                                            <h6 style="color: #0d683f; font-weight: 700; margin-bottom: 12px;">
                                                                <i class="fas fa-user-circle me-2"></i>Thông tin người nhận
                                                            </h6>
                                                            <div style="display: grid; grid-template-columns: 110px 1fr; gap: 8px; font-size: 14px;">
                                                                <div style="color: #666;">Họ tên:</div>
                                                                <div style="font-weight: 500; color: #333;"><?php echo htmlspecialchars($order['ho_ten_nguoi_nhan']); ?></div>

                                                                <div style="color: #666;">Số điện thoại:</div>
                                                                <div style="font-weight: 500; color: #333;"><?php echo htmlspecialchars($order['dien_thoai_nguoi_nhan']); ?></div>

                                                                <div style="color: #666;">Địa chỉ:</div>
                                                                <div style="font-weight: 500; color: #333;"><?php echo htmlspecialchars($order['dia_chi_giao_hang']); ?></div>

                                                                <?php if (!empty($order['ghi_chu'])): ?>
                                                                    <div style="color: #666;">Ghi chú:</div>
                                                                    <div style="font-weight: 500; color: #333;"><?php echo htmlspecialchars($order['ghi_chu']); ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>

                                                        <!-- DANH SÁCH SẢN PHẨM -->
                                                        <h6 style="color: #0d683f; font-weight: 700; margin-bottom: 12px;">
                                                            <i class="fas fa-box me-2"></i>Sản phẩm đã mua
                                                        </h6>

                                                        <?php foreach ($order_items as $item): ?>
                                                            <div class="modal-product">
                                                                <div style="display:flex; gap:10px; align-items:center;">
                                                                    <?php
                                                                    $img = !empty($item['hinh_anh']) ? $item['hinh_anh'] : 'default.png';
                                                                    ?>

                                                                    <img
                                                                        src="<?php echo BASE_URL . '/public/assets/images/products/' . $img; ?>"
                                                                        style="width:50px;height:50px;object-fit:cover;border-radius:8px;border:1px solid #eee;">

                                                                    <div class="modal-product-info">
                                                                        <div class="modal-product-name">
                                                                            <?php echo htmlspecialchars($item['ten_san_pham']); ?>
                                                                        </div>
                                                                        <div class="modal-product-price">
                                                                            <?php
                                                                            $gia_sp = isset($item['don_gia']) ? $item['don_gia'] : (isset($item['gia']) ? $item['gia'] : 0);
                                                                            echo formatPrice($gia_sp);
                                                                            ?> x <?php echo $item['so_luong']; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-product-total">
                                                                    <?php
                                                                    $thanh_tien = isset($item['thanh_tien']) ? $item['thanh_tien'] : ($gia_sp * $item['so_luong']);
                                                                    echo formatPrice($thanh_tien);
                                                                    ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>

                                                        <!-- TẠM TÍNH -->
                                                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                                            <span style="color:#666;">Tạm tính:</span>
                                                            <span style="font-weight:600;">
                                                                <?php echo formatPrice($order['tong_tien']); ?>
                                                            </span>
                                                        </div>

                                                        <!-- GIẢM GIÁ -->
                                                        <?php
                                                        $giam_gia = $order['tong_tien'] - ($order['tong_thanh_toan'] - $order['phi_ship']);
                                                        ?>

                                                        <?php if ($giam_gia > 0): ?>
                                                            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                                                <span style="color:#dc3545;">Giảm giá:</span>
                                                                <span style="font-weight:600; color:#dc3545;">
                                                                    -<?php echo formatPrice($giam_gia); ?>
                                                                </span>
                                                            </div>
                                                        <?php endif; ?>

                                                        <!-- PHÍ SHIP -->
                                                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                                            <span style="color:#666;">Phí vận chuyển:</span>
                                                            <span style="font-weight:600;">
                                                                <?php
                                                                if ($order['phi_ship'] > 0) {
                                                                    echo formatPrice($order['phi_ship']);
                                                                } else {
                                                                    echo '<span style="color:#4caf50;">Miễn phí</span>';
                                                                }
                                                                ?>
                                                            </span>
                                                        </div>

                                                        <!-- TỔNG -->
                                                        <div class="modal-total" style="margin-top: 10px; padding-top: 12px; border-top: 1px solid #ddd; text-align:right;">
                                                            <span>
                                                                Tổng thanh toán: <?php echo formatPrice($order['tong_thanh_toan']); ?>
                                                            </span>
                                                        </div>


                                                        <!-- TRẠNG THÁI ĐƠN HÀNG -->
                                                        <div style="margin-top: 16px; padding: 12px; background: #f0fdf4; border-radius: 10px; display: flex; justify-content: space-between; align-items: center;">
                                                            <span style="font-size: 13px; color: #166534;">
                                                                <i class="fas fa-truck me-1"></i> Trạng thái đơn hàng:
                                                            </span>
                                                            <span style="font-size: 14px; font-weight: 600; color: #0d683f;">
                                                                <?php
                                                                switch ($order['trang_thai']) {
                                                                    case 'cho_xac_nhan':
                                                                        echo '⏳ Chờ xác nhận';
                                                                        break;
                                                                    case 'dang_xu_ly':
                                                                        echo '🔄 Đang xử lý';
                                                                        break;
                                                                    case 'dang_giao':
                                                                        echo '🚚 Đang giao';
                                                                        break;
                                                                    case 'hoan_thanh':
                                                                        echo '✅ Hoàn thành';
                                                                        break;
                                                                    case 'da_huy':
                                                                        echo '❌ Đã hủy';
                                                                        break;
                                                                    default:
                                                                        echo $order['trang_thai'];
                                                                }
                                                                ?>
                                                            </span>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <!-- PHÂN TRANG -->
                                    <?php if ($total_pages > 1): ?>
                                        <div class="pagination-container">
                                            <nav>
                                                <ul class="pagination">
                                                    <?php if ($page > 1): ?>
                                                        <li class="page-item">
                                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>#orders-tab" aria-label="Previous">
                                                                <span aria-hidden="true">&laquo;</span>
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>

                                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                                            <a class="page-link" href="?page=<?php echo $i; ?>#orders-tab"><?php echo $i; ?></a>
                                                        </li>
                                                    <?php endfor; ?>

                                                    <?php if ($page < $total_pages): ?>
                                                        <li class="page-item">
                                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>#orders-tab" aria-label="Next">
                                                                <span aria-hidden="true">&raquo;</span>
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </nav>
                                        </div>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <div class="empty-order">
                                        <i class="fas fa-shopping-bag"></i>
                                        <h4>Chưa có đơn hàng nào</h4>
                                        <p>Hãy mua sắm để xem lịch sử đơn hàng tại đây</p>
                                        <a href="<?php echo BASE_URL; ?>/public/pages/products.php" class="save-btn">
                                            <i class="fas fa-shopping-cart me-2"></i>Mua sắm ngay
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- PASSWORD TAB -->
                    <div class="tab-pane fade" id="password-tab">
                        <div class="profile-card">
                            <div class="profile-card-header">
                                <h4><i class="fas fa-key me-2" style="color:#0d683f;"></i>Đổi mật khẩu</h4>
                            </div>
                            <div class="profile-card-body">
                                <?php if (isset($pwd_success)): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i><?php echo $pwd_success; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($pwd_error)): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $pwd_error; ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" id="passwordForm">
                                    <div class="mb-3">
                                        <label class="form-label">Mật khẩu hiện tại</label>
                                        <div class="password-wrapper">
                                            <input type="password" name="old_password" id="old_password" class="form-control" required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('old_password', this)">
                                                <i class="fas fa-eye-slash"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Mật khẩu mới</label>
                                        <div class="password-wrapper">
                                            <input type="password" name="new_password" id="new_password" class="form-control" required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('new_password', this)">
                                                <i class="fas fa-eye-slash"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Mật khẩu phải có ít nhất 6 ký tự</small>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label">Xác nhận mật khẩu mới</label>
                                        <div class="password-wrapper">
                                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                                                <i class="fas fa-eye-slash"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <button type="submit" name="change_password" class="save-btn">
                                        <i class="fas fa-key me-2"></i>Đổi mật khẩu
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Preview avatar
    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatarPreview').src = e.target.result;
                var sidebarImg = document.getElementById('sidebarAvatar');
                if (sidebarImg) sidebarImg.src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Toggle password visibility
    function togglePassword(fieldId, btn) {
        const input = document.getElementById(fieldId);
        const icon = btn.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    }

    // Confirm cancel order (include current page)
    function confirmCancel(orderId, orderCode) {
        // Get current page from URL
        const urlParams = new URLSearchParams(window.location.search);
        let currentPage = urlParams.get('page') || 1;

        if (confirm(`Bạn có chắc chắn muốn hủy đơn hàng #${orderCode} không?\n\nSau khi hủy, đơn hàng sẽ không thể khôi phục.`)) {
            window.location.href = '?cancel_order=' + orderId + '&page=' + currentPage;
        }
    }

    // Active tab management - keep orders tab active when pagination clicked
    document.querySelectorAll('.profile-menu .list-group-item').forEach(item => {
        item.addEventListener('click', function(e) {
            document.querySelectorAll('.profile-menu .list-group-item').forEach(link => {
                link.classList.remove('active');
            });
            this.classList.add('active');
        });
    });

    // If URL contains #orders-tab, activate orders tab and deactivate profile tab
    if (window.location.hash === '#orders-tab') {
        // Switch tab
        var ordersTabLink = document.querySelector('.profile-menu .list-group-item[data-bs-target="#orders-tab"]');
        var profileTabLink = document.querySelector('.profile-menu .list-group-item[data-bs-target="#profile-tab"]');
        if (ordersTabLink) {
            ordersTabLink.classList.add('active');
            if (profileTabLink) profileTabLink.classList.remove('active');

            // Activate the tab pane
            var ordersPane = document.querySelector('#orders-tab');
            var profilePane = document.querySelector('#profile-tab');
            if (ordersPane && profilePane) {
                profilePane.classList.remove('show', 'active');
                ordersPane.classList.add('show', 'active');
            }
        }
    }
</script>

<script>
    // Tự động mở modal chi tiết đơn hàng nếu có order_id trên URL
    const urlParams = new URLSearchParams(window.location.search);
    const orderId = urlParams.get('order_id');
    if (orderId) {
        // Chuyển sang tab đơn hàng
        const ordersTab = document.querySelector('#orders-tab');
        const profileTab = document.querySelector('#profile-tab');
        if (ordersTab && profileTab) {
            profileTab.classList.remove('show', 'active');
            ordersTab.classList.add('show', 'active');
            // Cập nhật active menu
            const ordersMenu = document.querySelector('.profile-menu .list-group-item[data-bs-target="#orders-tab"]');
            const profileMenu = document.querySelector('.profile-menu .list-group-item[data-bs-target="#profile-tab"]');
            if (ordersMenu) ordersMenu.classList.add('active');
            if (profileMenu) profileMenu.classList.remove('active');
        }
        // Mở modal chi tiết đơn hàng
        setTimeout(() => {
            const modalElement = document.getElementById(`orderModal${orderId}`);
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        }, 300);
    }
</script>

<?php include '../includes/footer.php'; ?>