<?php
    $cart_count = 0;

    if (isset($_SESSION['cart'])) {

    $cart_count = count($_SESSION['cart']);
    }
?>

<style>
/* ============================================
   NOTIFICATION DROPDOWN STYLES
   ============================================ */
.notification-dropdown {
    position: relative;
}

.notif-btn {
    background: transparent;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    position: relative;
    padding: 8px 10px;
    border-radius: 50%;
    transition: all 0.2s;
    color: #166534;
}

.notif-btn:hover {
    background: #e8f5e9;
}

.notif-badge {
    position: absolute;
    top: -2px;
    right: -5px;
    background: #dc2626;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 10px;
    font-weight: bold;
    min-width: 18px;
    text-align: center;
    box-shadow: 0 0 0 2px white;
}

.notif-dropdown {
    position: absolute;
    right: 0;
    top: 45px;
    width: 360px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    display: none;
    z-index: 1000;
    overflow: hidden;
}

.notif-dropdown.show {
    display: block;
}

.notif-header {
    padding: 14px 16px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f9fafb;
}

.notif-header span {
    font-weight: 700;
    color: #1f2937;
    font-size: 15px;
}

.notif-header a {
    color: #166534;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
}

.notif-header a:hover {
    text-decoration: underline;
}

.notif-list {
    max-height: 400px;
    overflow-y: auto;
}

.notif-item {
    display: flex;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid #f3f4f6;
    text-decoration: none;
    transition: background 0.2s;
    cursor: pointer;
}

.notif-item:hover {
    background: #f9fafb;
}

.notif-item.unread {
    background: #ecfdf5;
}

.notif-icon {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.notif-icon.don_hang {
    background: #dcfce7;
    color: #16a34a;
}

.notif-icon.san_pham {
    background: #fef3c7;
    color: #d97706;
}

.notif-icon.tin_tuc {
    background: #dbeafe;
    color: #2563eb;
}

.notif-icon.lien_he {
    background: #e0e7ff;
    color: #4f46e5;
}

.notif-icon.promotion {
    background: #fce7f3;
    color:      #db2777;
}


.notif-icon.default {
    background: #f3f4f6;
    color: #6b7280;
}

.notif-content {
    flex: 1;
}

.notif-title {
    font-weight: 600;
    font-size: 14px;
    color: #111827;
    margin-bottom: 4px;
    line-height: 1.4;
}

.notif-text {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 4px;
    line-height: 1.4;
}

.notif-time {
    font-size: 11px;
    color: #9ca3af;
}

.notif-empty {
    padding: 40px 20px;
    text-align: center;
    color: #9ca3af;
    font-size: 14px;
}

.notif-loading {
    padding: 40px 20px;
    text-align: center;
    color: #9ca3af;
}

/* Responsive */
@media (max-width: 576px) {
    .notif-dropdown {
        width: 320px;
        right: -50px;
    }
}
</style>

<nav class="navbar navbar-expand-lg sticky-top custom-navbar">

    <div class="container">

        <!-- LOGO -->
        <a class="navbar-brand custom-brand" href="<?php echo BASE_URL; ?>/index.php">

            <div class="brand-wrapper">
                <div class="brand-top">NGHĨA THÀNH</div>
                <div class="brand-bottom">EXPORT FOOD PROCESSING</div>
            </div>

        </a>

        <!-- MOBILE BUTTON -->
        <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarMain">

            <span class="navbar-toggler-icon"></span>

        </button>

        <!-- MENU -->
        <div class="collapse navbar-collapse"
             id="navbarMain">

            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">

                <!-- TRANG CHỦ -->
                <li class="nav-item">
                    <a class="nav-link nav-custom-link
                    <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"
                       href="<?php echo BASE_URL; ?>/index.php">
                        Trang chủ
                    </a>
                </li>

                <!-- SẢN PHẨM -->
                <li class="nav-item">
                    <a class="nav-link nav-custom-link
                    <?php echo strpos($_SERVER['PHP_SELF'], 'products.php') !== false ? 'active' : ''; ?>"
                       href="<?php echo BASE_URL; ?>/public/pages/products.php">
                        Sản phẩm
                    </a>
                </li>

                <!-- GIỚI THIỆU -->
                <li class="nav-item">
                    <a class="nav-link nav-custom-link
                    <?php echo strpos($_SERVER['PHP_SELF'], 'about.php') !== false ? 'active' : ''; ?>"
                       href="<?php echo BASE_URL; ?>/public/pages/about.php">
                        Giới thiệu
                    </a>
                </li>

                <!-- TIN TỨC -->
                <li class="nav-item">
                    <a class="nav-link nav-custom-link
                    <?php echo strpos($_SERVER['PHP_SELF'], 'news.php') !== false ? 'active' : ''; ?>"
                       href="<?php echo BASE_URL; ?>/public/pages/news.php">
                        Tin tức
                    </a>
                </li>

                <!-- LIÊN HỆ -->
                <li class="nav-item">
                    <a class="nav-link nav-custom-link
                    <?php echo strpos($_SERVER['PHP_SELF'], 'contact.php') !== false ? 'active' : ''; ?>"
                       href="<?php echo BASE_URL; ?>/public/pages/contact.php">
                        Liên hệ
                    </a>
                </li>

            </ul>

            <!-- RIGHT -->
            <div class="d-flex align-items-center gap-2">

                <!-- SEARCH -->
                <form class="d-flex me-2"
                      action="<?php echo BASE_URL; ?>/public/pages/products.php"
                      method="GET">

                    <div class="input-group input-group-sm">
                        <input class="form-control"
                               type="search"
                               name="search"
                               placeholder="Tìm kiếm..."
                               aria-label="Search">
                        <button class="btn btn-outline-success" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>

                </form>

                <!-- CART -->
                <a href="<?php echo BASE_URL; ?>/public/pages/cart.php"
                   class="btn btn-outline-success position-relative">

                    <i class="fas fa-shopping-cart"></i>

                    <?php if ($cart_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?php echo $cart_count; ?>
                    </span>
                    <?php endif; ?>

                </a>

                <!-- NOTIFICATION BELL (CHỈ HIỂN THỊ KHI ĐĂNG NHẬP) -->
                <?php if (isLoggedIn()): ?>
                <div class="notification-dropdown">
                    <button class="notif-btn" id="notifBtn">
                        <i class="fas fa-bell"></i>
                        <span class="notif-badge" id="notifBadge" style="display: none;">0</span>
                    </button>

                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-header">
                            <span><i class="fas fa-bell me-1"></i> Thông báo</span>
                            <a href="<?php echo BASE_URL; ?>/public/pages/notifications/index.php">Xem tất cả</a>
                        </div>
                        <div class="notif-list" id="notifList">
                            <div class="notif-loading">Đang tải...</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- LOGIN / USER DROPDOWN -->
                <?php if (isLoggedIn()): ?>

                <div class="dropdown">

                    <button class="btn btn-success dropdown-toggle"
                            type="button"
                            data-bs-toggle="dropdown">

                        <i class="fas fa-user me-1"></i>

                        <?php echo htmlspecialchars($_SESSION['user_name']); ?>

                    </button>

                    <ul class="dropdown-menu dropdown-menu-end">

                        <li>
                            <a class="dropdown-item"
                               href="<?php echo BASE_URL; ?>/public/pages/profile.php">
                                <i class="fas fa-id-card me-2"></i>
                                Tài khoản
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <?php if (isAdmin()): ?>
                        <li>
                            <a class="dropdown-item"
                               href="<?php echo BASE_URL; ?>/admin/index.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Quản trị
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>

                        <li>
                            <a class="dropdown-item text-danger"
                               href="<?php echo BASE_URL; ?>/public/pages/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Đăng xuất
                            </a>
                        </li>

                    </ul>

                </div>

                <?php else: ?>

                <a href="<?php echo BASE_URL; ?>/public/pages/login.php"
                   class="btn btn-success">

                    <i class="fas fa-sign-in-alt me-1"></i>

                    Đăng nhập

                </a>

                <a href="<?php echo BASE_URL; ?>/public/pages/register.php"
                   class="btn btn-outline-success">

                    <i class="fas fa-user-plus me-1"></i>

                    Đăng ký

                </a>

                <?php endif; ?>

            </div>

        </div>

    </div>

</nav>

<script>
// ============================================
// NOTIFICATION SYSTEM (CHẠY KHI ĐÃ ĐĂNG NHẬP)
// ============================================
<?php if (isLoggedIn()): ?>
let notifInterval;

function loadNotifications() {
    fetch('<?php echo BASE_URL; ?>/public/pages/notifications/api.php?action=get')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Cập nhật badge
                const badge = document.getElementById('notifBadge');
                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }

                // Render danh sách
                renderNotifList(data.notifications);
            }
        })
        .catch(err => console.error('Lỗi load thông báo:', err));
}

function renderNotifList(notifications) {
    const container = document.getElementById('notifList');

    if (!notifications || notifications.length === 0) {
        container.innerHTML = '<div class="notif-empty">🔔 Không có thông báo nào</div>';
        return;
    }

    let html = '';
    for (const notif of notifications) {
        // Xác định icon dựa vào loại (dùng khi không có ảnh)
        let iconHtml = '<i class="fas fa-bell"></i>';
        let iconClass = 'default';

        switch(notif.loai) {
            case 'don_hang':
                iconHtml = '<i class="fas fa-shopping-cart"></i>';
                iconClass = 'don_hang';
                break;
            case 'san_pham':
                iconHtml = '<i class="fas fa-box"></i>';
                iconClass = 'san_pham';
                break;
            case 'tin_tuc':
                iconHtml = '<i class="fas fa-newspaper"></i>';
                iconClass = 'tin_tuc';
                break;
            case 'lien_he':
                iconHtml = '<i class="fas fa-comments"></i>';
                iconClass = 'lien_he';
                break;
            case 'khuyen_mai':
                iconHtml  = '<i class="fas fa-tags"></i>';
                iconClass = 'promotion';
                break;
            default:
                iconHtml = '<i class="fas fa-bell"></i>';
                iconClass = 'default';
        }

        // Quyết định hiển thị ảnh hay icon
        let leftContent = '';
        let imagePath = '';

switch(notif.loai){

    case 'san_pham':
    case 'don_hang':
        imagePath = '/public/assets/images/products/' + notif.hinh_anh;
        break;

    case 'tin_tuc':
        imagePath = '/public/assets/images/news/' + notif.hinh_anh;
        break;

    case 'danh_muc':
        imagePath = '/public/assets/images/categories/' + notif.hinh_anh;
        break;

    case 'khuyen_mai':
        imagePath = '/public/assets/images/promotions/' + notif.hinh_anh;
        break;
}

if (notif.hinh_anh) {

    leftContent = `
        <img src="${imagePath}"
             style="width:40px;height:40px;object-fit:cover;border-radius:8px;">
    `;

} else {

    leftContent = `
        <div class="notif-icon ${iconClass}">
            ${iconHtml}
        </div>
    `;
}

        html += `
            <div class="notif-item ${notif.da_xem == 0 ? 'unread' : ''}"
                 data-id="${notif.id}"
                 data-link="${notif.link || '#'}">
                ${leftContent}
                <div class="notif-content">
                    <div class="notif-title">${escapeHtml(notif.tieu_de)}</div>
                    <div class="notif-text">${escapeHtml(notif.noi_dung)}</div>
                    <div class="notif-time">${notif.thoi_gian}</div>
                </div>
            </div>
        `;
    }

    container.innerHTML = html;

    // Gắn sự kiện click cho từng thông báo
    container.querySelectorAll('.notif-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
            const id = this.dataset.id;
            const link = this.dataset.link;

            // Đánh dấu đã đọc qua API
            fetch('<?php echo BASE_URL; ?>/public/pages/notifications/api.php?action=mark_read', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });

            // Chuyển hướng nếu có link
            if (link && link !== '#') {
                window.location.href = link;
            }
        });
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// Dropdown toggle
const notifBtn = document.getElementById('notifBtn');
const notifDropdown = document.getElementById('notifDropdown');

if (notifBtn) {
    notifBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        notifDropdown.classList.toggle('show');
        if (notifDropdown.classList.contains('show')) {
            loadNotifications(); // Reload khi mở dropdown
        }
    });
}

// Đóng dropdown khi click ra ngoài
document.addEventListener('click', function(e) {
    if (notifDropdown && !notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
        notifDropdown.classList.remove('show');
    }
});

// Load lần đầu và set interval mỗi 30 giây
loadNotifications();
setInterval(loadNotifications, 30000);
<?php endif; ?>
</script>