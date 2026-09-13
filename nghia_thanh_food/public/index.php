<?php
require_once 'includes/config.php';
require_once 'includes/promotion_helper.php';


$page_title = 'Trang chủ';

// Lấy sản phẩm nổi bật
$db = getDB();
$stmt = $db->prepare("SELECT * FROM sanpham WHERE trang_thai = 1 AND san_pham_noi_bat = 1 ORDER BY id DESC LIMIT 8");
$stmt->execute();
$featured_products = $stmt->fetchAll();

// Lấy sản phẩm mới
$stmt = $db->prepare("SELECT * FROM sanpham WHERE trang_thai = 1 AND san_pham_moi = 1 ORDER BY ngay_tao DESC LIMIT 8");
$stmt->execute();
$new_products = $stmt->fetchAll();

// Lấy danh mục
$stmt = $db->prepare("SELECT * FROM danhmuc WHERE trang_thai = 1 ORDER BY id");
$stmt->execute();
$categories = $stmt->fetchAll();

// Lấy tin tức mới
$stmt = $db->prepare("SELECT * FROM tintuc WHERE trang_thai = 1 ORDER BY ngay_dang DESC LIMIT 3");
$stmt->execute();
$news = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- Hero Banner -->
<!-- HERO SECTION -->
<section class="hero-banner">

    <div class="hero-slide">

        <div class="container">

            <div class="hero-content">

                <div class="hero-badge">
                    Chất lượng xuất khẩu
                </div>

                <h1>
                    TINH HOA <br>
                    <span>ẨM THỰC</span> <br>
                    <span>VIỆT</span>
                </h1>

                <p>
                    Nghĩa Thành Food cam kết mang đến những sản phẩm
                    thực phẩm sạch, đạt tiêu chuẩn quốc tế cho mọi
                    gia đình Việt và thế giới.
                </p>

                <div class="hero-buttons">

                    <a href="<?php echo BASE_URL; ?>/public/pages/products.php"
                        class="hero-btn-primary">

                        MUA SẮM NGAY
                        <i class="fas fa-arrow-right ms-2"></i>

                    </a>

                    <a href="<?php echo BASE_URL; ?>/public/pages/about.php"
                        class="hero-btn-outline">

                        TÌM HIỂU THÊM

                    </a>

                </div>

            </div>

        </div>

    </div>

</section>

<div class="container mb-5">
    <!-- Danh mục sản phẩm -->
    <div class="row mb-5">

        <div class="col-12">

            <h2 class="section-title text-center mb-4">

                Danh mục sản phẩm

            </h2>

            <div class="row g-4">

                <?php foreach ($categories as $cat): ?>

                    <?php
                    $category_image = !empty($cat['hinh_anh'])
                        ? BASE_URL . '/public/assets/images/categories/' . $cat['hinh_anh']
                        : 'https://placehold.co/120x120/ffffff/2e7d32?text=Category';
                    ?>

                    <div class="col-md-2 col-6">

                        <a
                            href="<?php echo BASE_URL; ?>/public/pages/products.php?category=<?php echo $cat['id']; ?>"
                            class="text-decoration-none">

                            <div class="category-card text-center p-3">

                                <div class="category-image mb-3">

                                    <img
                                        src="<?php echo $category_image; ?>"
                                        alt="<?php echo htmlspecialchars($cat['ten_danh_muc']); ?>"
                                        class="category-img">

                                </div>

                                <h5 class="mb-0 category-name">

                                    <?php echo htmlspecialchars($cat['ten_danh_muc']); ?>

                                </h5>

                            </div>

                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    </div>

    <!-- =========================================
| SẢN PHẨM NỔI BẬT
========================================= -->

    <div class="row mb-5">

        <div class="col-12">

            <h2 class="section-title text-center mb-4">

                Sản phẩm nổi bật

            </h2>

            <div class="row g-4">

                <?php foreach ($featured_products as $product): ?>

                    <?php
                    $price_data =
                        getFinalProductPrice($db, $product);
                    ?>

                    <div class="col-md-3 col-6">

                        <div class="product-card card h-100 border-0 shadow-sm">

                            <!-- IMAGE -->

                            <div class="product-image">

                                <?php if (!empty($product['hinh_anh'])): ?>

                                    <img
                                        src="<?php echo BASE_URL; ?>/public/assets/images/products/<?php echo $product['hinh_anh']; ?>"
                                        class="card-img-top"
                                        alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">

                                <?php else: ?>

                                    <div class="no-product-image">

                                        <i class="fas fa-image"></i>

                                        <span>Chưa có ảnh</span>

                                    </div>

                                <?php endif; ?>

                                <?php if ($price_data['discount_percent'] > 0): ?>

                                    <span class="sale-badge">

                                        -<?php echo $price_data['discount_percent']; ?>%

                                    </span>

                                <?php endif; ?>

                            </div>

                            <!-- BODY -->

                            <div class="card-body text-center d-flex flex-column">

                                <h5 class="card-title product-title">

                                    <?php echo htmlspecialchars($product['ten_san_pham']); ?>

                                </h5>

                                <div class="product-price mb-3">

                                    <?php if ($price_data['discount_percent'] > 0): ?>

                                        <span class="old-price">

                                            <?php
                                            echo formatPrice(
                                                $price_data['original_price']
                                            );
                                            ?>

                                        </span>

                                        <span class="current-price text-danger fw-bold">

                                            <?php
                                            echo formatPrice(
                                                $price_data['final_price']
                                            );
                                            ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="current-price text-danger fw-bold">

                                            <?php
                                            echo formatPrice(
                                                $price_data['final_price']
                                            );
                                            ?>

                                        </span>

                                    <?php endif; ?>

                                </div>

                                <div class="product-actions mt-auto">

                                    <button
                                        onclick="addToCart(<?php echo $product['id']; ?>)"
                                        class="btn product-btn-add">

                                        <i class="fas fa-cart-plus me-2"></i>

                                        Thêm

                                    </button>

                                    <a
                                        href="<?php echo BASE_URL; ?>/public/pages/product-detail.php?id=<?php echo $product['id']; ?>"
                                        class="btn product-btn-detail">

                                        <i class="fas fa-eye me-2"></i>

                                        Chi tiết

                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    </div>

    <!-- =========================================
| SẢN PHẨM MỚI
========================================= -->

    <div class="row mb-5">

        <div class="col-12">

            <h2 class="section-title text-center mb-4">

                Sản phẩm mới

            </h2>

            <div class="row g-4">

                <?php foreach ($new_products as $product): ?>

                    <?php
                    $price_data =
                        getFinalProductPrice($db, $product);
                    ?>

                    <div class="col-md-3 col-6">

                        <div class="product-card card h-100 border-0 shadow-sm">

                            <!-- IMAGE -->

                            <div class="product-image">

                                <?php if (!empty($product['hinh_anh'])): ?>

                                    <img
                                        src="<?php echo BASE_URL; ?>/public/assets/images/products/<?php echo $product['hinh_anh']; ?>"
                                        class="card-img-top"
                                        alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">

                                <?php else: ?>

                                    <div class="no-product-image">

                                        <i class="fas fa-image"></i>

                                        <span>Chưa có ảnh</span>

                                    </div>

                                <?php endif; ?>

                                <span class="new-badge">

                                    Mới

                                </span>

                                <?php if ($price_data['discount_percent'] > 0): ?>

                                    <span class="sale-badge">

                                        -<?php echo $price_data['discount_percent']; ?>%

                                    </span>

                                <?php endif; ?>

                            </div>

                            <!-- BODY -->

                            <div class="card-body text-center d-flex flex-column">

                                <h5 class="card-title product-title">

                                    <?php echo htmlspecialchars($product['ten_san_pham']); ?>

                                </h5>

                                <div class="product-price mb-3">

                                    <?php if ($price_data['discount_percent'] > 0): ?>

                                        <span class="old-price">

                                            <?php
                                            echo formatPrice(
                                                $price_data['original_price']
                                            );
                                            ?>

                                        </span>

                                        <span class="current-price text-danger fw-bold">

                                            <?php
                                            echo formatPrice(
                                                $price_data['final_price']
                                            );
                                            ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="current-price text-danger fw-bold">

                                            <?php
                                            echo formatPrice(
                                                $price_data['final_price']
                                            );
                                            ?>

                                        </span>

                                    <?php endif; ?>

                                </div>

                                <div class="product-actions mt-auto">

                                    <button
                                        onclick="addToCart(<?php echo $product['id']; ?>)"
                                        class="btn product-btn-add">

                                        <i class="fas fa-cart-plus me-2"></i>

                                        Thêm

                                    </button>

                                    <a
                                        href="<?php echo BASE_URL; ?>/public/pages/product-detail.php?id=<?php echo $product['id']; ?>"
                                        class="btn product-btn-detail">

                                        <i class="fas fa-eye me-2"></i>

                                        Chi tiết

                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    </div>

    <!-- Tin tức -->
    <div class="row">
        <div class="col-12">
            <h2 class="section-title text-center mb-4">Tin tức nổi bật</h2>
            <div class="row g-4">
                <?php foreach ($news as $item): ?>
                    <div class="col-md-4">
                        <div class="card h-100 news-card">
                            <?php
                            $news_image = !empty($item['hinh_anh'])
                                ? BASE_URL . '/public/assets/images/news/' . $item['hinh_anh']
                                : 'https://placehold.co/400x200/2e7d32/white?text=' . urlencode($item['tieu_de']);
                            ?>

                            <img
                                src="<?php echo $news_image; ?>"
                                class="card-img-top"
                                alt="<?php echo htmlspecialchars($item['tieu_de']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($item['tieu_de']); ?></h5>
                                <p class="card-text text-muted small">
                                    <i class="far fa-calendar-alt me-1"></i><?php echo date('d/m/Y', strtotime($item['ngay_dang'])); ?>
                                </p>
                                <p class="card-text"><?php echo mb_substr(strip_tags($item['noi_dung']), 0, 100); ?>...</p>
                                <a href="<?php echo BASE_URL; ?>/public/pages/news.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-success">Đọc tiếp</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>