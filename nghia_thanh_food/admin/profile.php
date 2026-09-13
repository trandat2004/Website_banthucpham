<?php
require_once __DIR__ . '/includes/check_auth.php';

$page_title = 'Thông tin Admin';

require_once 'includes/config.php';

$db = getDB();

$admin_id = $_SESSION['admin_id'];

// Xác định tab đang active dựa trên URL (mặc định là overview)
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    // giữ dữ liệu để hiện lỗi
} else {
    $password_errors    = [];
    $password_form_data = [
        'current_password' => '',
        'new_password'     => '',
        'confirm_password' => '',
    ];
}

/*
|--------------------------------------------------------------------------
| CẬP NHẬT THÔNG TIN CƠ BẢN (HỌ TÊN, SĐT, ĐỊA CHỈ, AVATAR)
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $ho_ten     = trim($_POST['ho_ten']);
    $dien_thoai = trim($_POST['dien_thoai']);
    $dia_chi    = trim($_POST['dia_chi']);

    // Lấy thông tin cũ
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$admin_id]);
    $old_admin = $stmt->fetch();

    $avatar_name = $old_admin['avatar'];

    // Thư mục upload
    $upload_dir = __DIR__ . '/../public/assets/images/admin/';
    if (! is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Xử lý upload avatar
    if (isset($_FILES['avatar']) && ! empty($_FILES['avatar']['name'])) {
        $ext   = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allow = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allow)) {
            $avatar_name = 'admin_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $avatar_name);
        }
    }

    // Cập nhật database (không ảnh hưởng mật khẩu)
    $stmt = $db->prepare("
        UPDATE users
        SET ho_ten = ?, dien_thoai = ?, dia_chi = ?, avatar = ?
        WHERE id = ?
    ");
    $stmt->execute([$ho_ten, $dien_thoai, $dia_chi, $avatar_name, $admin_id]);

    // Cập nhật session
    $_SESSION['admin_name']   = $ho_ten;
    $_SESSION['admin_avatar'] = $avatar_name;

    $_SESSION['success_profile'] = 'Cập nhật tài khoản thành công!';
    header("Location: profile.php?tab=overview");
    exit;
}

/*
|--------------------------------------------------------------------------
| ĐỔI MẬT KHẨU
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {

    $active_tab = 'password';

    $password_form_data = [
        'current_password' => trim($_POST['current_password']),
        'new_password'     => trim($_POST['new_password']),
        'confirm_password' => trim($_POST['confirm_password']),
    ];

    $current_password = $password_form_data['current_password'];
    $new_password     = $password_form_data['new_password'];
    $confirm_password = $password_form_data['confirm_password'];

    if (empty($current_password)) {
        $password_errors['current_password'] = 'Vui lòng nhập mật khẩu hiện tại';
    }

    if (empty($new_password)) {
        $password_errors['new_password'] = 'Vui lòng nhập mật khẩu mới';
    } elseif (strlen($new_password) < 6) {
        $password_errors['new_password'] = 'Mật khẩu phải tối thiểu 6 ký tự';
    }

    if (empty($confirm_password)) {
        $password_errors['confirm_password'] = 'Vui lòng xác nhận mật khẩu';
    } elseif ($new_password !== $confirm_password) {
        $password_errors['confirm_password'] = 'Mật khẩu xác nhận không khớp';
    }

    if (empty($password_errors)) {

        $stmt = $db->prepare("SELECT mat_khau FROM users WHERE id = ?");
        $stmt->execute([$admin_id]);
        $admin_data = $stmt->fetch();

        if (! password_verify($current_password, $admin_data['mat_khau'])) {

            $password_errors['current_password'] = 'Mật khẩu hiện tại không chính xác';
        } else {

            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("
                UPDATE users
                SET mat_khau = ?
                WHERE id = ?
            ");

            $stmt->execute([$new_password_hash, $admin_id]);

            $_SESSION['success_profile'] = 'Đổi mật khẩu thành công!';

            header("Location: profile.php?tab=password");
            exit;
        }
    }
}

/*
|--------------------------------------------------------------------------
| CẬP NHẬT CẤU HÌNH WEBSITE
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $settings = [
        'company_name',
        'company_phone',
        'company_email',
        'company_address',
        'shipping_fee',
        'free_shipping_limit',
        'facebook_url',
        'instagram_url',
        'youtube_url',
    ];

    foreach ($settings as $key) {
        if (isset($_POST[$key])) {
            $value = trim($_POST[$key]);
            $stmt  = $db->prepare("UPDATE cauhinh SET gia_tri = ? WHERE ten_cau_hinh = ?");
            $stmt->execute([$value, $key]);
        }
    }

    $_SESSION['success_profile'] = 'Cập nhật cấu hình website thành công!';
    header("Location: profile.php?tab=website");
    exit;
}

/*
|--------------------------------------------------------------------------
| LẤY THÔNG TIN ADMIN HIỆN TẠI
|--------------------------------------------------------------------------
*/
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

/*
|--------------------------------------------------------------------------
| LẤY CẤU HÌNH WEBSITE
|--------------------------------------------------------------------------
*/
$stmt    = $db->query("SELECT ten_cau_hinh, gia_tri FROM cauhinh");
$configs = [];
while ($row = $stmt->fetch()) {
    $configs[$row['ten_cau_hinh']] = $row['gia_tri'];
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
    /* ========== GIỮ NGUYÊN STYLE GỐC ========== */
    .profile-page {
        min-height: 100vh;
        background: linear-gradient(135deg, #f8fafc, #eef2ff);
        padding: 30px;
    }

    .profile-container {
        max-width: 1400px;
        margin: auto;
    }

    .profile-hero {
        position: relative;
        overflow: hidden;
        border-radius: 36px;
        padding: 45px;
        background: linear-gradient(135deg, #0f172a 0%, #14532d 100%);
        box-shadow: 0 20px 50px rgba(0, 0, 0, .08);
        margin-bottom: 28px;
    }

    .profile-hero::before {
        content: '';
        position: absolute;
        right: -80px;
        top: -80px;
        width: 300px;
        height: 300px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .05);
    }

    .profile-flex {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 30px;
        position: relative;
        z-index: 2;
    }

    .profile-left {
        display: flex;
        align-items: center;
        gap: 28px;
    }

    .profile-avatar {
        width: 150px;
        height: 150px;
        border-radius: 35px;
        object-fit: cover;
        border: 5px solid rgba(255, 255, 255, .15);
        background: white;
    }

    .avatar-placeholder {
        width: 150px;
        height: 150px;
        border-radius: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #22c55e, #15803d);
        color: white;
        font-size: 55px;
        font-weight: 800;
    }

    .profile-name {
        color: white;
        font-size: 42px;
        font-weight: 800;
        margin-bottom: 10px;
    }

    .profile-role {
        color: rgba(255, 255, 255, .75);
        font-size: 15px;
    }

    .profile-badge {
        margin-top: 18px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 11px 20px;
        border-radius: 50px;
        background: rgba(255, 255, 255, .12);
        color: white;
        font-size: 14px;
        font-weight: 700;
    }

    .logout-btn {
        min-width: 200px;
        height: 58px;
        border-radius: 18px;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: #ffffff !important;
        font-size: 15px;
        font-weight: 800;
        transition: .25s;
    }

    .logout-btn i {
        color: #ffffff !important;
    }

    .logout-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 25px rgba(239, 68, 68, .25);
        color: white;
    }

    /* Tabs thiết kế mới */
    .custom-tabs {
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 30px;
        gap: 8px;
    }

    .custom-tabs .nav-link {
        border: none;
        font-weight: 700;
        font-size: 16px;
        padding: 12px 24px;
        border-radius: 40px;
        color: #475569;
        transition: all 0.2s ease;
        background: transparent;
    }

    .custom-tabs .nav-link i {
        margin-right: 8px;
        font-size: 1.1rem;
    }

    .custom-tabs .nav-link:hover {
        color: #16a34a;
        background: #f1f5f9;
    }

    .custom-tabs .nav-link.active {
        background: linear-gradient(135deg, #22c55e, #15803d);
        color: white;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
    }

    .tab-pane {
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Card & Form */
    .custom-card {
        border: none;
        border-radius: 30px;
        overflow: hidden;
        background: white;
        box-shadow: 0 12px 40px rgba(0, 0, 0, .05);
    }

    .card-title-custom {
        font-size: 25px;
        font-weight: 800;
        color: #0f172a;
    }

    .info-item {
        padding: 18px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .info-item:last-child {
        border: none;
    }

    .info-label {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 6px;
    }

    .info-value {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
    }

    .form-label {
        font-weight: 700;
        margin-bottom: 10px;
        color: #0f172a;
    }

    .custom-input {
        height: 58px;
        border-radius: 18px;
        border: 1px solid #dbe2ea;
        padding: 0 18px;
        font-size: 15px;
        transition: .25s;
    }

    .custom-input:focus {
        border-color: #16a34a;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, .12);
    }

    .file-input {
        border-radius: 18px;
        padding: 14px;
    }

    .password-box {
        position: relative;
    }

    .password-toggle {
        position: absolute;
        right: 18px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: none;
        color: #64748b;
        font-size: 18px;
        cursor: pointer;
    }

    .save-btn {
        height: 60px;
        border: none;
        border-radius: 20px;
        background: linear-gradient(135deg, #22c55e, #15803d);
        font-size: 16px;
        font-weight: 700;
        transition: .25s;
        color: white;
    }

    .save-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 25px rgba(34, 197, 94, .25);
    }

    .custom-toast {
        position: fixed;
        top: 25px;
        right: 25px;
        z-index: 999999;
        padding: 14px 20px;
        border-radius: 16px;
        color: white;
        font-size: 14px;
        font-weight: 700;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .15);
    }

    .toast-success {
        background: linear-gradient(135deg, #22c55e, #15803d);
    }

    .toast-error {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    @media (max-width:992px) {
        .profile-flex {
            flex-direction: column;
            align-items: flex-start;
        }

        .logout-btn {
            width: 100%;
        }

        .profile-left {
            flex-wrap: wrap;
        }

        .custom-tabs .nav-link {
            padding: 8px 16px;
            font-size: 14px;
        }
    }

    .is-invalid {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 4px rgba(239, 68, 68, .12) !important;
    }

    .invalid-feedback {
        color: #ef4444;
        font-size: 13px;
        font-weight: 600;
        margin-top: 8px;
    }
</style>

<div class="col-md-10 profile-page">
    <div class="profile-container">

        <!-- Toast thông báo -->
        <?php if (isset($_SESSION['success_profile'])): ?>
            <div class="custom-toast toast-success">
                <i class="fas fa-circle-check me-2"></i>
                <?php echo $_SESSION['success_profile'];
                unset($_SESSION['success_profile']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_profile'])): ?>
            <div class="custom-toast toast-error">
                <i class="fas fa-circle-xmark me-2"></i>
                <?php echo $_SESSION['error_profile'];
                unset($_SESSION['error_profile']); ?>
            </div>
        <?php endif; ?>

        <!-- Hero header -->
        <div class="profile-hero">
            <div class="profile-flex">
                <div class="profile-left">
                    <?php if (! empty($admin['avatar'])): ?>
                        <img src="<?php echo BASE_URL; ?>/public/assets/images/admin/<?php echo $admin['avatar']; ?>?v=<?php echo time(); ?>" class="profile-avatar">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?php echo strtoupper(substr($admin['ho_ten'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div class="profile-name"><?php echo htmlspecialchars($admin['ho_ten']); ?></div>
                        <div class="profile-role">Quản trị viên hệ thống Nghĩa Thành Food</div>
                        <div class="profile-badge"><i class="fas fa-shield-halved"></i> Super Administrator</div>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>/admin/auth/logout.php" class="logout-btn"><i class="fas fa-right-from-bracket"></i> Đăng xuất</a>
            </div>
        </div>

        <!-- Tabs giao diện -->
        <ul class="nav custom-tabs" id="adminTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $active_tab == 'overview' ? 'active' : ''; ?>"
                    href="profile.php?tab=overview">
                    <i class="fas fa-user-circle"></i> Tổng quan & Cập nhật
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $active_tab == 'password' ? 'active' : ''; ?>"
                    href="profile.php?tab=password">
                    <i class="fas fa-key"></i> Đổi mật khẩu
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $active_tab == 'website' ? 'active' : ''; ?>"
                    href="profile.php?tab=website">
                    <i class="fas fa-globe"></i> Cấu hình website
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- TAB 1: TỔNG QUAN + CẬP NHẬT TÀI KHOẢN -->
            <div class="tab-pane fade <?php echo $active_tab == 'overview' ? 'show active' : ''; ?>" id="overview" role="tabpanel">
                <div class="row g-4">
                    <!-- Cột trái: Thông tin chi tiết -->
                    <div class="col-lg-5">
                        <div class="custom-card h-100">
                            <div class="p-4">
                                <div class="card-title-custom mb-4"><i class="fas fa-chart-simple me-2"></i> Tổng quan tài khoản</div>
                                <div class="info-item">
                                    <div class="info-label">Email</div>
                                    <div class="info-value"><?php echo htmlspecialchars($admin['email']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Điện thoại</div>
                                    <div class="info-value"><?php echo htmlspecialchars($admin['dien_thoai']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Địa chỉ</div>
                                    <div class="info-value"><?php echo htmlspecialchars($admin['dia_chi']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Vai trò</div>
                                    <div class="info-value">Administrator</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Cột phải: Form cập nhật thông tin -->
                    <div class="col-lg-7">
                        <div class="custom-card">
                            <div class="p-4">
                                <div class="card-title-custom mb-4"><i class="fas fa-pen-alt me-2"></i> Cập nhật thông tin</div>
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Họ tên</label>
                                            <input type="text" name="ho_ten" class="form-control custom-input" value="<?php echo htmlspecialchars($admin['ho_ten']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Điện thoại</label>
                                            <input type="text" name="dien_thoai" class="form-control custom-input" value="<?php echo htmlspecialchars($admin['dien_thoai']); ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Địa chỉ</label>
                                        <input type="text" name="dia_chi" class="form-control custom-input" value="<?php echo htmlspecialchars($admin['dia_chi']); ?>">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Ảnh đại diện</label>
                                        <input type="file" name="avatar" class="form-control file-input">
                                        <small class="text-muted">Chấp nhận JPG, PNG, WEBP</small>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn save-btn w-100"><i class="fas fa-floppy-disk me-2"></i> Lưu thay đổi</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: ĐỔI MẬT KHẨU -->
            <div class="tab-pane fade <?php echo $active_tab == 'password' ? 'show active' : ''; ?>" id="password" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-lg-7">
                        <div class="custom-card">
                            <div class="p-4">
                                <div class="card-title-custom mb-4"><i class="fas fa-lock me-2"></i> Đổi mật khẩu</div>
                                <form method="POST">

                                    <div class="mb-3">
                                        <label class="form-label">Mật khẩu hiện tại</label>
                                        <div class="password-box">

                                            <input
                                                type="password"
                                                name="current_password"
                                                id="current_password"
                                                value="<?php echo htmlspecialchars($password_form_data['current_password'] ?? ''); ?>"
                                                class="form-control custom-input <?php echo isset($password_errors['current_password']) ? 'is-invalid' : ''; ?>">

                                            <button type="button" class="password-toggle" onclick="togglePassword('current_password',this)">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                        </div>

                                        <?php if (isset($password_errors['current_password'])): ?>
                                            <div class="invalid-feedback d-block">
                                                <?php echo $password_errors['current_password']; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Mật khẩu mới</label>
                                        <div class="password-box">

                                            <input
                                                type="password"
                                                name="new_password"
                                                id="new_password"
                                                value="<?php echo htmlspecialchars($password_form_data['new_password'] ?? ''); ?>"
                                                class="form-control custom-input <?php echo isset($password_errors['new_password']) ? 'is-invalid' : ''; ?>">

                                            <button type="button" class="password-toggle" onclick="togglePassword('new_password',this)">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                        </div>

                                        <?php if (isset($password_errors['new_password'])): ?>
                                            <div class="invalid-feedback d-block">
                                                <?php echo $password_errors['new_password']; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label">Xác nhận mật khẩu mới</label>
                                        <div class="password-box">

                                            <input
                                                type="password"
                                                name="confirm_password"
                                                id="confirm_password"
                                                value="<?php echo htmlspecialchars($password_form_data['confirm_password'] ?? ''); ?>"
                                                class="form-control custom-input <?php echo isset($password_errors['confirm_password']) ? 'is-invalid' : ''; ?>">

                                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password',this)">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                        </div>

                                        <?php if (isset($password_errors['confirm_password'])): ?>
                                            <div class="invalid-feedback d-block">
                                                <?php echo $password_errors['confirm_password']; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <button type="submit" name="change_password" class="btn save-btn w-100">
                                        <i class="fas fa-sync-alt me-2"></i> Đổi mật khẩu
                                    </button>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: CẤU HÌNH WEBSITE -->
            <div class="tab-pane fade <?php echo $active_tab == 'website' ? 'show active' : ''; ?>" id="website" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="custom-card">
                            <div class="p-4">
                                <div class="card-title-custom mb-4"><i class="fas fa-gear me-2"></i> Cấu hình website</div>
                                <form method="POST">
                                    <input type="hidden" name="update_settings" value="1">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Tên công ty</label>
                                            <input type="text" name="company_name" class="form-control custom-input" value="<?php echo htmlspecialchars($configs['company_name'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Số điện thoại</label>
                                            <input type="text" name="company_phone" class="form-control custom-input" value="<?php echo htmlspecialchars($configs['company_phone'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email công ty</label>
                                        <input type="email" name="company_email" class="form-control custom-input" value="<?php echo htmlspecialchars($configs['company_email'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Địa chỉ công ty</label>
                                        <input type="text" name="company_address" class="form-control custom-input" value="<?php echo htmlspecialchars($configs['company_address'] ?? ''); ?>">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Phí vận chuyển (VNĐ)</label>
                                            <input type="number" name="shipping_fee" class="form-control custom-input" value="<?php echo htmlspecialchars($configs['shipping_fee'] ?? '25000'); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Mức miễn phí ship (VNĐ)</label>
                                            <input type="number" name="free_shipping_limit" class="form-control custom-input" value="<?php echo htmlspecialchars($configs['free_shipping_limit'] ?? '200000'); ?>">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Facebook URL</label>
                                                <input type="url" name="facebook_url" class="form-control custom-input"
                                                    value="<?php echo htmlspecialchars($configs['facebook_url'] ?? ''); ?>"
                                                    placeholder="https://facebook.com/yourpage">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Instagram URL</label>
                                                <input type="url" name="instagram_url" class="form-control custom-input"
                                                    value="<?php echo htmlspecialchars($configs['instagram_url'] ?? ''); ?>"
                                                    placeholder="https://instagram.com/yourpage">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">YouTube URL</label>
                                            <input type="url" name="youtube_url" class="form-control custom-input"
                                                value="<?php echo htmlspecialchars($configs['youtube_url'] ?? ''); ?>"
                                                placeholder="https://youtube.com/@yourchannel">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn save-btn w-100"><i class="fas fa-save me-2"></i> Lưu cấu hình</button>
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
    function togglePassword(id, button) {
        const input = document.getElementById(id);
        const icon = button.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Tự động ẩn toast sau 3 giây
    setTimeout(function() {
        const toast = document.querySelector('.custom-toast');
        if (toast) {
            toast.style.transition = '.4s';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(() => toast.remove(), 400);
        }
    }, 3000);
</script>

<?php include 'includes/footer.php'; ?>