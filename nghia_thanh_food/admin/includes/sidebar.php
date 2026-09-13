<?php
require_once __DIR__ . '/config.php';
$db = getDB();

/*
|-------------------------------------------------------
| LẤY INFO ADMIN LUÔN FRESH (QUAN TRỌNG)
|-------------------------------------------------------
*/
$admin_id = $_SESSION['admin_id'] ?? null;

$admin_avatar = null;
$admin_name = $_SESSION['admin_name'] ?? 'Admin';

if ($admin_id) {
    $stmt = $db->prepare("SELECT ho_ten, avatar FROM users WHERE id = ?");
    $stmt->execute([$admin_id]);
    $row = $stmt->fetch();

    if ($row) {
        $admin_name = $row['ho_ten'];
        $admin_avatar = $row['avatar'];

        // cập nhật session để đồng bộ toàn hệ thống
        $_SESSION['admin_name'] = $admin_name;
        $_SESSION['admin_avatar'] = $admin_avatar;
    }
}
?>

<style>
    .sidebar {
        height: 100vh;
        position: sticky;
        top: 0;
        left: 0;
        background:
            linear-gradient(180deg,
                #0f172a 0%,
                #111827 40%,
                #166534 100%);
        overflow: hidden;
        box-shadow: 10px 0 35px rgba(0, 0, 0, .08);
        display: flex;
        flex-direction: column;
        border-right: 1px solid rgba(255, 255, 255, .06);
    }

    /* LOGO */
    .sidebar-logo {
        padding: 30px 24px 24px;
        border-bottom: 1px solid rgba(255, 255, 255, .08);
        display: flex;
        align-items: center;
        gap: 14px;
        text-align: center;
    }

    .logo-box {
        width: 47px;
        height: 52px;
        border-radius: 16px;
        background: linear-gradient(135deg, #22c55e, #15803d);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 20px;
        box-shadow: 0 10px 25px rgba(34, 197, 94, .35);
        flex-shrink: 0;
    }

    .logo-text-group {
        flex: 1;
    }

    .logo-title {
        color: #fff;
        font-size: 18px;
        font-weight: 800;
        margin-bottom: 4px;
        line-height: 1.2;
    }

    .logo-sub {
        color: rgba(255, 255, 255, .65);
        font-size: 12px;
        line-height: 1.3;
    }

    /* MENU */
    .sidebar-menu {
        flex: 1;
        overflow-y: auto;
        padding: 20px 14px;
    }

    .sidebar-menu::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar-menu::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, .15);
        border-radius: 20px;
    }

    .menu-title {
        color: rgba(255, 255, 255, .45);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1px;
        margin: 10px 14px 14px;
    }

    .sidebar .nav-link {
        height: 56px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        gap: 14px;
        color: rgba(255, 255, 255, .75);
        font-size: 15px;
        font-weight: 600;
        padding: 0 18px;
        margin-bottom: 10px;
        transition: .25s;
        position: relative;
        text-decoration: none;
    }

    .sidebar .nav-link i {
        width: 22px;
        font-size: 18px;
        text-align: center;
    }

    .sidebar .nav-link:hover {
        background: rgba(255, 255, 255, .08);
        color: #fff;
        transform: translateX(4px);
    }

    .sidebar .nav-link.active {
        background: linear-gradient(135deg, #22c55e, #15803d);
        color: #fff;
        box-shadow: 0 10px 25px rgba(34, 197, 94, .25);
    }

    .sidebar .nav-link.active::before {
        content: '';
        position: absolute;
        left: -14px;
        width: 5px;
        height: 28px;
        border-radius: 20px;
        background: #4ade80;
    }

    /* FOOTER */
    .sidebar-footer {
        padding: 20px;
        border-top: 1px solid rgba(255, 255, 255, .08);
    }

    /* KHỐI ADMIN */
    .admin-box {
        background: rgba(255, 255, 255, .06);
        border: 1px solid rgba(255, 255, 255, .08);
        border-radius: 18px;
        padding: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-decoration: none;
        cursor: pointer;
        transition: .25s;
    }

    .admin-box:hover {
        transform: translateY(-2px);
        background: rgba(255, 255, 255, .09);
    }

    .admin-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    /* AVATAR SIDEBAR */
    .admin-avatar {
        width: 45px;
        height: 45px;
        border-radius: 14px;
        overflow: hidden;
        background: linear-gradient(135deg, #22c55e, #16a34a);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 16px;
    }

    .admin-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* TEXT (FIX GẠCH CHÂN + TO HƠN) */
    .admin-text {
        display: flex;
        flex-direction: column;
    }

    .admin-name {
        color: #fff;
        font-size: 15px;
        font-weight: 800;
        line-height: 1.2;
    }

    .admin-role {
        color: rgba(255, 255, 255, .6);
        font-size: 12px;
        margin-top: 2px;
    }

    /* mũi tên */
    .admin-arrow {
        color: rgba(255, 255, 255, .45);
        font-size: 14px;
        transition: .25s;
    }

    .admin-box:hover .admin-arrow {
        color: #fff;
        transform: translateX(3px);
    }

    /* Thêm style cho menu có icon đặc biệt */
    .nav-link i.fa-chart-line {
        color: #22c55e;
    }

    .nav-link.active i.fa-chart-line {
        color: white;
    }
</style>

<div class="col-md-2 p-0 sidebar">

    <!-- LOGO -->
    <div class="sidebar-logo">
        <div class="logo-box">
            <i class="fas fa-store"></i>
        </div>
        <div class="logo-text-group">
            <div class="logo-title">Nghĩa Thành Food</div>
            <div class="logo-sub">Hệ thống quản trị thương mại điện tử</div>
        </div>
    </div>

    <!-- MENU -->
    <div class="sidebar-menu">

        <div class="menu-title">MAIN MENU</div>

        <?php
        $current_uri = $_SERVER['REQUEST_URI'];
        ?>

        <a class="nav-link <?php echo (strpos($current_uri, '/admin/index.php') !== false || $current_uri == '/admin/') ? 'active' : ''; ?>"
            href="<?php echo BASE_URL; ?>/admin/index.php">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>

        <a class="nav-link <?php echo (strpos($current_uri, '/admin/products/') !== false) ? 'active' : ''; ?>"
            href="<?php echo BASE_URL; ?>/admin/products/list.php">
            <i class="fas fa-box-open"></i> Sản phẩm
        </a>

        <a class="nav-link <?php echo (strpos($current_uri, '/admin/categories/') !== false) ? 'active' : ''; ?>"
            href="<?php echo BASE_URL; ?>/admin/categories/list.php">
            <i class="fas fa-layer-group"></i> Danh mục
        </a>

        <a class="nav-link <?php echo (strpos($current_uri, '/admin/orders/') !== false) ? 'active' : ''; ?>"
            href="<?php echo BASE_URL; ?>/admin/orders/list.php">
            <i class="fas fa-cart-shopping"></i> Đơn hàng
        </a>

        <a class="nav-link <?php echo (strpos($current_uri, '/admin/promotions/') !== false) ? 'active' : ''; ?>"
            href="<?php echo BASE_URL; ?>/admin/promotions/list.php">
            <i class="fas fa-tags"></i> Khuyến mãi
        </a>

        <!-- MENU BÁO CÁO - DÙNG REQUEST_URI THAY VÌ PHP_SELF -->
        <a class="nav-link <?php echo (strpos($current_uri, '/admin/reports/') !== false) ? 'active' : ''; ?>"
            href="<?php echo BASE_URL; ?>/admin/reports/index.php">
            <i class="fas fa-chart-line"></i> Báo cáo & Thống kê
        </a>

        <a class="nav-link <?php echo (strpos($current_uri, '/admin/news/') !== false) ? 'active' : ''; ?>"
            href="<?php echo BASE_URL; ?>/admin/news/list.php">
            <i class="fas fa-newspaper"></i> Tin tức
        </a>

        <a class="nav-link <?php echo (strpos($current_uri, '/admin/users/') !== false) ? 'active' : ''; ?>"
            href="<?php echo BASE_URL; ?>/admin/users/list.php">
            <i class="fas fa-users"></i> Người dùng
        </a>

        <a class="nav-link <?php echo (strpos($current_uri, '/admin/contact/') !== false) ? 'active' : ''; ?>"
            href="<?php echo BASE_URL; ?>/admin/contact/list.php">
            <i class="fas fa-envelope-open-text"></i> Liên hệ
        </a>

    </div>

    <!-- FOOTER ADMIN -->
    <div class="sidebar-footer">

        <a href="<?php echo BASE_URL; ?>/admin/profile.php" class="admin-box-link">

            <div class="admin-box">

                <div class="admin-info">

                    <div class="admin-avatar">

                        <?php if (!empty($_SESSION['admin_avatar'])): ?>

                            <img src="<?php echo BASE_URL; ?>/public/assets/images/admin/<?php echo $_SESSION['admin_avatar']; ?>?v=<?php echo time(); ?>">

                        <?php else: ?>

                            <?php echo strtoupper(substr($admin_name, 0, 1)); ?>

                        <?php endif; ?>

                    </div>

                    <div>
                        <div class="admin-name">
                            <?php echo htmlspecialchars($admin_name); ?>
                        </div>
                        <div class="admin-role">Quản trị viên</div>
                    </div>

                </div>

                <div class="admin-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>

            </div>

        </a>

    </div>

</div>