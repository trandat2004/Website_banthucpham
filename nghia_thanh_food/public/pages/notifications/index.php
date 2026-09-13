<?php
    require_once '../../includes/config.php';

    if (! isLoggedIn()) {
    redirect('/public/pages/login.php');
    }

    $page_title = 'Thông báo';
    $db         = getDB();
    $user_id    = $_SESSION['user_id'];

    /*
|--------------------------------------------------------------------------
| Phân trang
|--------------------------------------------------------------------------
*/
    $page   = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit  = 20;
    $offset = ($page - 1) * $limit;

    /*
|--------------------------------------------------------------------------
| Lấy tổng số thông báo
|--------------------------------------------------------------------------
*/
    $total_stmt = $db->prepare("SELECT COUNT(*) as total FROM thongbao WHERE user_id = ?");
    $total_stmt->execute([$user_id]);
    $total       = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total / $limit);

    /*
|--------------------------------------------------------------------------
| Lấy danh sách thông báo của trang hiện tại
|--------------------------------------------------------------------------
*/
    $stmt = $db->prepare("
    SELECT
        t.id,
        t.tieu_de,
        t.noi_dung,
        t.link,
        t.da_xem,
        t.loai,
        t.ngay_tao,
        t.tham_chieu_id,
        t.hinh_anh
    FROM thongbao t
    WHERE t.user_id = ?
    ORDER BY t.ngay_tao DESC
    LIMIT ? OFFSET ?
");
    $stmt->execute([$user_id, $limit, $offset]);
    $notifications = $stmt->fetchAll();

    /*
|--------------------------------------------------------------------------
| Lấy ảnh cho từng thông báo và lưu vào cột hinh_anh (nếu chưa có)
|--------------------------------------------------------------------------
*/
    foreach ($notifications as &$tb) {
    // Nếu đã có ảnh rồi thì bỏ qua
    if (! empty($tb['hinh_anh'])) {
        continue;
    }

    $hinh_anh = null;

    try {
        switch ($tb['loai']) {
            case 'don_hang':
                $stmt_img = $db->prepare("
                        SELECT s.hinh_anh
                        FROM chitietdonhang ctd
                        JOIN sanpham s ON ctd.san_pham_id = s.id
                        WHERE ctd.don_hang_id = ?
                        LIMIT 1
                    ");
                $stmt_img->execute([$tb['tham_chieu_id']]);
                $result   = $stmt_img->fetch(PDO::FETCH_ASSOC);
                $hinh_anh = $result ? $result['hinh_anh'] : null;
                break;

            case 'san_pham':
                $stmt_img = $db->prepare("SELECT hinh_anh FROM sanpham WHERE id = ? LIMIT 1");
                $stmt_img->execute([$tb['tham_chieu_id']]);
                $result   = $stmt_img->fetch(PDO::FETCH_ASSOC);
                $hinh_anh = $result ? $result['hinh_anh'] : null;
                break;

            case 'danh_muc':
                $stmt_img = $db->prepare("
                        SELECT hinh_anh
                        FROM sanpham
                        WHERE danh_muc_id = ?
                        AND hinh_anh IS NOT NULL
                        AND hinh_anh != ''
                        ORDER BY id ASC
                        LIMIT 1
                    ");
                $stmt_img->execute([$tb['tham_chieu_id']]);
                $result   = $stmt_img->fetch(PDO::FETCH_ASSOC);
                $hinh_anh = $result ? $result['hinh_anh'] : null;
                break;

            case 'tin_tuc':
                try {
                    $stmt_img = $db->prepare("SELECT hinh_anh FROM tintuc WHERE id = ? LIMIT 1");
                    $stmt_img->execute([$tb['tham_chieu_id']]);
                    $result   = $stmt_img->fetch(PDO::FETCH_ASSOC);
                    $hinh_anh = $result && isset($result['hinh_anh']) ? $result['hinh_anh'] : null;
                } catch (PDOException $e) {
                    $hinh_anh = null;
                }
                break;

            case 'khuyen_mai':
                $stmt_img = $db->prepare("
                        SELECT s.hinh_anh
                        FROM chitietkhuyenmai ctkm
                        JOIN sanpham s ON ctkm.san_pham_id = s.id
                        WHERE ctkm.khuyen_mai_id = ?
                        LIMIT 1
                    ");
                $stmt_img->execute([$tb['tham_chieu_id']]);
                $result   = $stmt_img->fetch(PDO::FETCH_ASSOC);
                $hinh_anh = $result ? $result['hinh_anh'] : null;
                break;

            default:
                $hinh_anh = null;
                break;
        }
    } catch (PDOException $e) {
        $hinh_anh = null;
    }

    $tb['hinh_anh'] = $hinh_anh;

    // Lưu ảnh vào cột hinh_anh trong bảng thongbao
    if ($hinh_anh) {
        $update_img = $db->prepare("UPDATE thongbao SET hinh_anh = ? WHERE id = ?");
        $update_img->execute([$hinh_anh, $tb['id']]);
    }
    }
    unset($tb);

    /*
|--------------------------------------------------------------------------
| Đánh dấu đã đọc cho các thông báo trong trang hiện tại
|--------------------------------------------------------------------------
*/
    if (! empty($notifications)) {
    $ids          = array_column($notifications, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $update_stmt  = $db->prepare("UPDATE thongbao SET da_xem = 1 WHERE id IN ($placeholders) AND da_xem = 0");
    $update_stmt->execute($ids);
    }

    include '../../includes/header.php';
    include '../../includes/navbar.php';
?>

<style>
    .notifications-page {
        background: #f5f7fb;
        min-height: 100vh;
        padding: 40px 0;
    }

    .notifications-wrapper {
        max-width: 900px;
        margin: auto;
    }

    .notifications-card {
        background: #fff;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
    }

    .notifications-header {
        padding: 25px 30px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
    }

    .notifications-header h2 {
        margin: 0;
        font-size: 28px;
        font-weight: 800;
        color: #166534;
    }

    .notifications-count {
        background: #dcfce7;
        color: #166534;
        padding: 8px 16px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 700;
    }

    .mark-all-btn {
        background: transparent;
        border: 1px solid #e5e7eb;
        padding: 8px 16px;
        border-radius: 999px;
        font-size: 13px;
        color: #6b7280;
        cursor: pointer;
        transition: 0.2s;
    }

    .mark-all-btn:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
    }

    .notifications-list {
        max-height: 65vh;
        overflow-y: auto;
    }

    .notifications-list::-webkit-scrollbar {
        width: 6px;
    }

    .notifications-list::-webkit-scrollbar-track {
        background: #e5e7eb;
        border-radius: 10px;
    }

    .notifications-list::-webkit-scrollbar-thumb {
        background: #9ca3af;
        border-radius: 10px;
    }

    .notifications-item {
        display: flex;
        gap: 18px;
        padding: 22px 30px;
        border-bottom: 1px solid #f3f4f6;
        text-decoration: none;
        transition: 0.25s;
    }

    .notifications-item:hover {
        background: #f9fafb;
    }

    .notifications-item.unread {
        background: #ecfdf5;
        border-left: 4px solid #10b981;
    }

    .notification-avatar {
        width: 52px;
        height: 52px;
        min-width: 52px;
        border-radius: 16px;
        overflow: hidden;
        position: relative;
        background: #f3f4f6;
    }

    .notification-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .notifications-icon {
        width: 52px;
        height: 52px;
        min-width: 52px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        background: #f3f4f6;
    }

    .notifications-icon.don_hang {
        background: #dcfce7;
        color: #16a34a;
    }

    .notifications-icon.san_pham {
        background: #fef3c7;
        color: #d97706;
    }

    .notifications-icon.tin_tuc {
        background: #dbeafe;
        color: #2563eb;
    }

    .notifications-icon.lien_he {
        background: #e0e7ff;
        color: #4f46e5;
    }

    .notifications-icon.danh_muc {
        background: #fee2e2;
        color: #dc2626;
    }

    .notifications-icon.khuyen_mai {
        background: #fef3c7;
        color: #f59e0b;
    }

    .notifications-icon.default {
        background: #f3f4f6;
        color: #6b7280;
    }

    .notifications-content {
        flex: 1;
    }

    .notifications-title {
        font-size: 17px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .notifications-badge {
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 20px;
        background: #f3f4f6;
        color: #6b7280;
    }

    .notifications-badge.san_pham {
        background: #fef3c7;
        color: #d97706;
    }

    .notifications-badge.danh_muc {
        background: #fee2e2;
        color: #dc2626;
    }

    .notifications-badge.tin_tuc {
        background: #dbeafe;
        color: #2563eb;
    }

    .notifications-badge.don_hang {
        background: #dcfce7;
        color: #16a34a;
    }

    .notifications-badge.khuyen_mai {
        background: #fef3c7;
        color: #f59e0b;
    }

    .notifications-text {
        font-size: 15px;
        color: #6b7280;
        margin-bottom: 8px;
        line-height: 1.6;
    }

    .notifications-time {
        font-size: 13px;
        color: #9ca3af;
    }

    .empty-notifications {
        padding: 80px 20px;
        text-align: center;
        color: #9ca3af;
    }

    .empty-notifications i {
        font-size: 70px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .pagination-wrapper {
        padding: 20px 30px;
        border-top: 1px solid #f0f0f0;
        display: flex;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .pagination-wrapper .page-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        padding: 0 12px;
        border-radius: 12px;
        background: white;
        color: #374151;
        text-decoration: none;
        font-weight: 500;
        border: 1px solid #e5e7eb;
    }

    .pagination-wrapper .page-link:hover {
        background: #f3f4f6;
    }

    .pagination-wrapper .page-link.active {
        background: #166534;
        color: white;
        border-color: #166534;
    }

    .pagination-wrapper .page-link.disabled {
        opacity: 0.5;
        pointer-events: none;
    }

    @media (max-width: 768px) {
        .notifications-header {
            padding: 20px;
        }

        .notifications-item {
            padding: 18px;
            gap: 12px;
        }

        .notification-avatar,
        .notifications-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            font-size: 18px;
        }
    }
</style>

<div class="notifications-page">
    <div class="container">
        <div class="notifications-wrapper">
            <div class="notifications-card">
                <div class="notifications-header">
                    <h2>
                        <i class="fas fa-bell me-2"></i>
                        Thông báo
                    </h2>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <button class="mark-all-btn" id="markAllBtn">
                            <i class="fas fa-check-double me-1"></i> Đánh dấu tất cả đã đọc
                        </button>
                        <div class="notifications-count">
                            <?php echo number_format($total); ?> thông báo
                        </div>
                    </div>
                </div>

                <div class="notifications-list">
                    <?php if (count($notifications) > 0): ?>
                        <?php foreach ($notifications as $tb): ?>
                            <?php
                                $icon_class  = 'default';
                                $icon_html   = '<i class="fas fa-bell"></i>';
                                $badge_text  = '';
                                $badge_class = '';

                                switch ($tb['loai']) {
                                    case 'don_hang':
                                        $icon_class  = 'don_hang';
                                        $icon_html   = '<i class="fas fa-shopping-cart"></i>';
                                        $badge_text  = 'Đơn hàng';
                                        $badge_class = 'don_hang';
                                        break;
                                    case 'san_pham':
                                        $icon_class  = 'san_pham';
                                        $icon_html   = '<i class="fas fa-box"></i>';
                                        $badge_text  = 'Sản phẩm';
                                        $badge_class = 'san_pham';
                                        break;
                                    case 'tin_tuc':
                                        $icon_class  = 'tin_tuc';
                                        $icon_html   = '<i class="fas fa-newspaper"></i>';
                                        $badge_text  = 'Tin tức';
                                        $badge_class = 'tin_tuc';
                                        break;
                                    case 'lien_he':
                                        $icon_class  = 'lien_he';
                                        $icon_html   = '<i class="fas fa-comments"></i>';
                                        $badge_text  = 'Liên hệ';
                                        $badge_class = 'lien_he';
                                        break;
                                    case 'danh_muc':
                                        $icon_class  = 'danh_muc';
                                        $icon_html   = '<i class="fas fa-layer-group"></i>';
                                        $badge_text  = 'Danh mục';
                                        $badge_class = 'danh_muc';
                                        break;
                                    case 'khuyen_mai':
                                        $icon_class  = 'khuyen_mai';
                                        $icon_html   = '<i class="fas fa-gift"></i>';
                                        $badge_text  = 'Khuyến mãi';
                                        $badge_class = 'khuyen_mai';
                                        break;
                                }

                                // Kiểm tra và xác định đường dẫn ảnh
                                $has_image  = ! empty($tb['hinh_anh']);
                                $image_path = '';
                                if ($has_image) {
                                    if ($tb['loai'] == 'don_hang' || $tb['loai'] == 'san_pham') {
                                        $image_path = BASE_URL . '/public/assets/images/products/' . htmlspecialchars($tb['hinh_anh']);
                                    } elseif ($tb['loai'] == 'danh_muc') {
                                        $image_path = BASE_URL . '/public/assets/images/categories/' . htmlspecialchars($tb['hinh_anh']);
                                    } elseif ($tb['loai'] == 'tin_tuc') {
                                        $image_path = BASE_URL . '/public/assets/images/news/' . htmlspecialchars($tb['hinh_anh']);
                                    } elseif ($tb['loai'] == 'khuyen_mai') {
                                        $image_path = BASE_URL . '/public/assets/images/promotions/' . htmlspecialchars($tb['hinh_anh']);
                                    }
                                }
                            ?>
                            <a href="<?php echo ! empty($tb['link']) ? htmlspecialchars($tb['link']) : '#'; ?>"
                                class="notifications-item <?php echo $tb['da_xem'] == 0 ? 'unread' : ''; ?>"
                                data-id="<?php echo $tb['id']; ?>">

                                <!-- SỬA: Hiển thị ảnh nếu có, KHÔNG thì hiển thị icon -->
                                <?php if ($has_image): ?>
                                    <div class="notification-avatar">
                                        <img src="<?php echo $image_path; ?>"
                                             alt="<?php echo htmlspecialchars($tb['tieu_de']); ?>"
                                             onerror="this.style.display='none'; this.parentElement.style.background='#f3f4f6'; this.nextElementSibling.style.display='flex';">
                                        <div class="notifications-icon <?php echo $icon_class; ?>" style="display: none;">
                                            <?php echo $icon_html; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="notifications-icon <?php echo $icon_class; ?>">
                                        <?php echo $icon_html; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="notifications-content">
                                    <div class="notifications-title">
                                        <?php echo htmlspecialchars($tb['tieu_de']); ?>
                                        <?php if ($badge_text): ?>
                                            <span class="notifications-badge <?php echo $badge_class; ?>">
                                                <?php echo $badge_text; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notifications-text">
                                        <?php echo htmlspecialchars($tb['noi_dung']); ?>
                                    </div>
                                    <div class="notifications-time">
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($tb['ngay_tao'])); ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-notifications">
                            <i class="fas fa-bell-slash"></i>
                            <h4>Chưa có thông báo nào</h4>
                            <p class="mt-2">Khi có thông báo mới, chúng sẽ hiển thị tại đây</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination-wrapper">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="page-link"><i class="fas fa-chevron-left"></i></a>
                        <?php else: ?>
                            <span class="page-link disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>

                        <?php
                            $start_page = max(1, $page - 2);
                            $end_page   = min($total_pages, $page + 2);

                            if ($start_page > 1) {
                                echo '<a href="?page=1" class="page-link">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="page-link disabled">...</span>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor;

                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<span class="page-link disabled">...</span>';
                                }
                                echo '<a href="?page=' . $total_pages . '" class="page-link">' . $total_pages . '</a>';
                            }
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="page-link"><i class="fas fa-chevron-right"></i></a>
                        <?php else: ?>
                            <span class="page-link disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('markAllBtn')?.addEventListener('click', function() {
        fetch('/pages/notifications/api.php?action=mark_all_read', {
                method: 'POST'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
    });

    document.querySelectorAll('.notification-avatar img').forEach(img => {
        img.addEventListener('error', function() {
            this.style.display = 'none';
            if (this.nextElementSibling) {
                this.nextElementSibling.style.display = 'flex';
            }
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>