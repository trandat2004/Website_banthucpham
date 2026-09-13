<?php

    require_once '../includes/config.php';

    $id = intval($_GET['id'] ?? 0);

    if (! $id) {
    redirect('/index.php');
    }

    $db = getDB();

    $stmt = $db->prepare("
        SELECT *
        FROM khuyenmai
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $promotion = $stmt->fetch();

    if (! $promotion) {
    redirect('/index.php');
    }

    /*
    |--------------------------------------------------------------------------
    | LẤY DANH SÁCH ÁP DỤNG (ĐƯỢC THIẾT KẾ LẠI GIAO DIỆN NHƯNG GIỮ NGUYÊN LOGIC)
    |--------------------------------------------------------------------------
    */

    $ap_dung_text = '';

    if ($promotion['ap_dung_cho'] == 'tat_ca') {

    $ap_dung_text = '
            <div class="apply-badge all">
                <span class="apply-icon">🌐</span> Áp dụng cho toàn bộ sản phẩm trên hệ thống
            </div>
        ';

    } elseif ($promotion['ap_dung_cho'] == 'danh_muc') {

    $ids = array_filter(explode(',', $promotion['danh_sach_ap_dung']));

    if (! empty($ids)) {

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmtCate = $db->prepare("
                SELECT ten_danh_muc
                FROM danhmuc
                WHERE id IN ($placeholders)
            ");

        $stmtCate->execute($ids);
        $categories = $stmtCate->fetchAll(PDO::FETCH_COLUMN);

        $html = '';
        foreach ($categories as $cate) {
            $html .= '<span class="category-tag">' . htmlspecialchars($cate) . '</span>';
        }

        $ap_dung_text = '
                <div class="apply-section">
                    <div class="apply-title">📂 Áp dụng cho danh mục</div>
                    <div class="tags-wrapper">' . $html . '</div>
                </div>
            ';
    }

    } elseif ($promotion['ap_dung_cho'] == 'san_pham') {

    $ids = array_filter(explode(',', $promotion['danh_sach_ap_dung']));

    if (! empty($ids)) {

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmtProduct = $db->prepare("
                SELECT ten_san_pham
                FROM sanpham
                WHERE id IN ($placeholders)
            ");

        $stmtProduct->execute($ids);
        $products = $stmtProduct->fetchAll(PDO::FETCH_COLUMN);

        $html = '';
        foreach ($products as $product) {
            $html .= '<span class="product-tag">' . htmlspecialchars($product) . '</span>';
        }

        $ap_dung_text = '
                <div class="apply-section">
                    <div class="apply-title">🛒 Áp dụng cho sản phẩm</div>
                    <div class="tags-wrapper">' . $html . '</div>
                </div>
            ';
    }
    }

    $page_title = $promotion['ten_khuyen_mai'];

    $loai = $promotion['loai_khuyen_mai'];

    if ($loai == 'free_ship') {
    $icon       = '🚚';
    $main_title = 'MIỄN PHÍ VẬN CHUYỂN';
    $sub_title  = 'Giao hàng toàn quốc - Không giới hạn';
    $bg         = 'linear-gradient(135deg, #0ea5e9, #0284c7)';
    $badge_text = 'FREE SHIP';
    } elseif ($loai == 'giam_tien_don') {
    $icon       = '💰';
    $main_title = 'GIẢM ' . formatPrice($promotion['gia_tri']);
    $sub_title  = 'Áp dụng trực tiếp vào đơn hàng';
    $bg         = 'linear-gradient(135deg, #f59e0b, #d97706)';
    $badge_text = 'GIẢM TIỀN';
    } else {
    $icon       = '🔥';
    $main_title = 'GIẢM ' . $promotion['gia_tri'] . '%';
    $sub_title  = 'Tiết kiệm tối đa cùng Nghĩa Thành Food';
    $bg         = 'linear-gradient(135deg, #ef4444, #dc2626)';
    $badge_text = 'SIÊU SALE';
    }

    include '../includes/header.php';
    include '../includes/navbar.php';
?>

<!-- Google Fonts & Font Awesome -->
<link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
    * {
        font-family: 'Inter', sans-serif;
    }

    body {
        background: #f5f7fb;
    }

    /* Main container */
    .promo-detail-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
    }

    /* Hero card */
    .promo-hero-card {
        background: white;
        border-radius: 2rem;
        overflow: hidden;
        box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s ease;
    }

    .promo-hero-card:hover {
        transform: translateY(-3px);
    }

    /* Banner */
    .promo-banner {
        position: relative;
        padding: 2.5rem 2rem;
        background-size: cover;
        background-position: center;
        overflow: hidden;
    }

    .promo-banner::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle at 20% 30%, rgba(255,255,255,0.15) 0%, rgba(0,0,0,0.1) 100%);
        pointer-events: none;
    }

    .badge-flash {
        display: inline-block;
        background: rgba(255,255,255,0.25);
        backdrop-filter: blur(4px);
        padding: 0.3rem 1rem;
        border-radius: 40px;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 1px;
        color: white;
        margin-bottom: 1rem;
        border: 1px solid rgba(255,255,255,0.4);
    }

    .promo-main-title {
        font-size: 3rem;
        font-weight: 800;
        color: white;
        margin: 0.5rem 0;
        line-height: 1.2;
        text-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .promo-sub {
        font-size: 1rem;
        color: rgba(255,255,255,0.95);
        margin-top: 0.5rem;
        font-weight: 500;
    }

    /* Content body */
    .promo-content {
        padding: 2rem 2rem 2rem 2rem;
    }

    /* Description cards */
    .offer-description {
        background: #f8fafd;
        border-radius: 1.5rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
        border: 1px solid #eef2f8;
    }

    .offer-description h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .offer-description p {
        color: #2c3e50;
        line-height: 1.6;
        margin-bottom: 0.75rem;
    }

    .highlight-text {
        color: #e67e22;
        font-weight: 700;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.2rem;
        margin: 1.5rem 0;
    }

    .info-card {
        background: white;
        border-radius: 1rem;
        padding: 1rem 1.2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 2px 6px rgba(0,0,0,0.03);
        border: 1px solid #edf2f7;
        transition: all 0.2s;
    }

    .info-card i {
        font-size: 1.8rem;
        color: #f97316;
    }

    .info-card .info-label {
        font-size: 0.8rem;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 600;
    }

    .info-card .info-value {
        font-size: 1.2rem;
        font-weight: 700;
        color: #0f172a;
    }

    /* Tags & apply section */
    .apply-section {
        background: #f1f5f9;
        border-radius: 1.2rem;
        padding: 1rem 1.2rem;
        margin: 1rem 0;
    }

    .apply-title {
        font-weight: 700;
        margin-bottom: 0.75rem;
        font-size: 0.9rem;
        color: #1e293b;
    }

    .tags-wrapper {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .category-tag, .product-tag {
        background: white;
        padding: 0.4rem 1rem;
        border-radius: 30px;
        font-size: 0.8rem;
        font-weight: 500;
        color: #1e293b;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
    }

    .apply-badge.all {
        background: #d1fae5;
        border-radius: 50px;
        padding: 0.6rem 1.2rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: #065f46;
    }

    /* Countdown */
    .countdown-wrapper {
        background: #0f172a;
        border-radius: 1.2rem;
        padding: 1.2rem;
        color: white;
        margin: 1.5rem 0;
        background: linear-gradient(105deg, #1e293b 0%, #0f172a 100%);
    }

    .countdown-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        margin-bottom: 0.8rem;
    }

    .countdown-timer {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .countdown-block {
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(4px);
        border-radius: 1rem;
        padding: 0.5rem 0.8rem;
        min-width: 70px;
        text-align: center;
    }

    .countdown-number {
        font-size: 1.8rem;
        font-weight: 800;
        line-height: 1;
    }

    .countdown-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        opacity: 0.8;
    }

    .time-static {
        font-size: 0.85rem;
        margin-top: 0.8rem;
        border-top: 1px solid rgba(255,255,255,0.2);
        padding-top: 0.8rem;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
    }

    /* CTA Button */
    .btn-shop-now {
        display: inline-flex;
        align-items: center;
        gap: 0.8rem;
        background: #f97316;
        border: none;
        padding: 1rem 2rem;
        font-weight: 700;
        font-size: 1rem;
        border-radius: 60px;
        transition: 0.2s;
        box-shadow: 0 8px 20px rgba(249,115,22,0.3);
        color: white;
    }

    .btn-shop-now:hover {
        background: #ea580c;
        transform: scale(1.02);
        box-shadow: 0 12px 24px rgba(249,115,22,0.4);
        color: white;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .promo-main-title {
            font-size: 2rem;
        }
        .promo-content {
            padding: 1.5rem;
        }
        .countdown-number {
            font-size: 1.2rem;
        }
        .info-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="promo-detail-container">
    <div class="promo-hero-card">
        <!-- Banner gradient section -->
        <div class="promo-banner" style="background: <?php echo $bg; ?>;">
            <div class="badge-flash">
                <i class="fas fa-bolt"></i> <?php echo $badge_text; ?>
            </div>
            <div class="promo-main-title">
                <?php echo $icon; ?> <?php echo $main_title; ?>
            </div>
            <div class="promo-sub">
                <?php echo $sub_title; ?>
            </div>
        </div>

        <!-- Nội dung chi tiết ưu đãi -->
        <div class="promo-content">
            <!-- Mô tả theo loại khuyến mãi -->
            <div class="offer-description">
                <?php if ($loai == 'free_ship'): ?>
                    <h3><i class="fas fa-truck-fast"></i> Miễn phí vận chuyển toàn quốc</h3>
                    <p>🎉 <strong>Nghĩa Thành Food</strong> miễn phí 100% phí ship cho tất cả đơn hàng đủ điều kiện. Không giới hạn số lượng, nhận hàng tại nhà mà không lo chi phí phát sinh.</p>
                    <p>✨ Áp dụng tự động khi thanh toán.<br>📦 Hỗ trợ giao hàng nhanh chóng.<br>🎁 Ưu đãi kép cùng nhiều sản phẩm hot.</p>
                <?php elseif ($loai == 'giam_tien_don'): ?>
                    <h3><i class="fas fa-tags"></i> Giảm trực tiếp <?php echo formatPrice($promotion['gia_tri']); ?></h3>
                    <p>💰 Siêu ưu đãi giảm ngay <strong class="highlight-text"><?php echo formatPrice($promotion['gia_tri']); ?></strong> vào tổng giá trị đơn hàng. Cơ hội săn deal hời chỉ có tại Nghĩa Thành Food.</p>
                    <p>🛍️ Không giới hạn số lượng sản phẩm.<br>⚡ Áp dụng nhanh chóng, số lượng có hạn.<br>🎯 Đơn tối thiểu áp dụng bên dưới.</p>
                <?php else: ?>
                    <h3><i class="fas fa-fire"></i> Giảm ngay <?php echo $promotion['gia_tri']; ?>% toàn bộ sản phẩm</h3>
                    <p>🔥 Mức giảm sốc lên đến <strong><?php echo $promotion['gia_tri']; ?>%</strong> cho các sản phẩm yêu thích. Mua sắm thả ga, tiết kiệm tối đa.</p>
                    <p>🎯 Áp dụng trực tiếp khi mua hàng.<br>🛍️ Càng mua nhiều càng tiết kiệm.<br>⏰ Chương trình có thời hạn, nhanh tay săn deal.</p>
                <?php endif; ?>
            </div>

            <!-- Thông tin điều kiện nổi bật -->
            <div class="info-grid">
                <?php if (! empty($promotion['don_hang_toi_thieu'])): ?>
                <div class="info-card">
                    <i class="fas fa-shopping-cart"></i>
                    <div>
                        <div class="info-label">Đơn hàng tối thiểu</div>
                        <div class="info-value"><?php echo formatPrice($promotion['don_hang_toi_thieu']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="info-card">
                    <i class="fas fa-calendar-alt"></i>
                    <div>
                        <div class="info-label">Ngày bắt đầu</div>
                        <div class="info-value"><?php echo date('d/m/Y', strtotime($promotion['ngay_bat_dau'])); ?></div>
                    </div>
                </div>
                <div class="info-card">
                    <i class="fas fa-hourglass-half"></i>
                    <div>
                        <div class="info-label">Kết thúc vào</div>
                        <div class="info-value"><?php echo date('d/m/Y', strtotime($promotion['ngay_ket_thuc'])); ?></div>
                    </div>
                </div>
            </div>

            <!-- Hiển thị đối tượng áp dụng -->
            <?php echo $ap_dung_text; ?>

            <!-- Đồng hồ đếm ngược và thời gian chi tiết -->
            <div class="countdown-wrapper" id="countdownBox" data-endtime="<?php echo strtotime($promotion['ngay_ket_thuc']) * 1000; ?>">
                <div class="countdown-title">
                    <i class="fas fa-stopwatch"></i> KHUYẾN MÃI KẾT THÚC SAU
                </div>
                <div class="countdown-timer" id="timerDisplay">
                    <div class="countdown-block"><div class="countdown-number" id="days">00</div><div class="countdown-label">Ngày</div></div>
                    <div class="countdown-block"><div class="countdown-number" id="hours">00</div><div class="countdown-label">Giờ</div></div>
                    <div class="countdown-block"><div class="countdown-number" id="minutes">00</div><div class="countdown-label">Phút</div></div>
                    <div class="countdown-block"><div class="countdown-number" id="seconds">00</div><div class="countdown-label">Giây</div></div>
                </div>
                <div class="time-static">
                    <span><i class="far fa-clock"></i> Bắt đầu: <?php echo date('H:i d/m/Y', strtotime($promotion['ngay_bat_dau'])); ?></span>
                    <span><i class="far fa-calendar-check"></i> Kết thúc: <?php echo date('H:i d/m/Y', strtotime($promotion['ngay_ket_thuc'])); ?></span>
                </div>
            </div>

            <!-- Nút hành động -->
            <div style="text-align: center; margin-top: 1rem;">
                <a href="<?php echo BASE_URL; ?>/public/pages/products.php" class="btn btn-shop-now">
                    🛒 MUA NGAY <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Countdown Timer
    function startCountdown(endTimestamp) {
        const countdownInterval = setInterval(function() {
            const now = new Date().getTime();
            const distance = endTimestamp - now;

            if (distance < 0) {
                clearInterval(countdownInterval);
                document.getElementById('timerDisplay').innerHTML = '<div class="countdown-block"><div class="countdown-number" style="color:#f97316;">00</div><div class="countdown-label">Hết hạn</div></div>';
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById('days').innerHTML = days < 10 ? '0' + days : days;
            document.getElementById('hours').innerHTML = hours < 10 ? '0' + hours : hours;
            document.getElementById('minutes').innerHTML = minutes < 10 ? '0' + minutes : minutes;
            document.getElementById('seconds').innerHTML = seconds < 10 ? '0' + seconds : seconds;
        }, 1000);
    }

    const countdownDiv = document.getElementById('countdownBox');
    if (countdownDiv) {
        const endTime = parseInt(countdownDiv.getAttribute('data-endtime'));
        if (!isNaN(endTime)) {
            startCountdown(endTime);
        }
    }
</script>

<?php include '../includes/footer.php'; ?>