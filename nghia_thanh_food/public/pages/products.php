<?php
require_once '../includes/config.php';
require_once '../includes/promotion_helper.php';

$page_title = 'Sản phẩm';

$db = getDB();


$where_conditions = ["sanpham.trang_thai = 1"];
$params = [];

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;

$search = isset($_GET['search'])
    ? safeInput($_GET['search'])
    : '';

$min_price = isset($_GET['min_price'])
    ? floatval($_GET['min_price'])
    : 0;

$max_price = isset($_GET['max_price'])
    ? floatval($_GET['max_price'])
    : 0;

$sort = isset($_GET['sort'])
    ? $_GET['sort']
    : 'newest';

/*
|--------------------------------------------------------------------------
| CATEGORY
|--------------------------------------------------------------------------
*/

if ($category_id > 0) {

    $where_conditions[] = "sanpham.danh_muc_id = ?";

    $params[] = $category_id;
}

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search != '') {

    $where_conditions[] = "
        (
            sanpham.ten_san_pham LIKE ?
            OR sanpham.ma_san_pham LIKE ?
            OR danhmuc.ten_danh_muc LIKE ?
        )
    ";

    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_sql = implode(" AND ", $where_conditions);

/*
|--------------------------------------------------------------------------
| SORT
|--------------------------------------------------------------------------
*/

$order_sql = "";

switch ($sort) {

    case 'price_asc':

        $order_sql = "
            ORDER BY
            COALESCE(
                sanpham.gia_khuyen_mai,
                sanpham.gia_ban
            ) ASC
        ";

        break;

    case 'price_desc':

        $order_sql = "
            ORDER BY
            COALESCE(
                sanpham.gia_khuyen_mai,
                sanpham.gia_ban
            ) DESC
        ";

        break;

    case 'popular':

        $order_sql = "
            ORDER BY sanpham.luot_xem DESC
        ";

        break;

    default:

        $order_sql = "
            ORDER BY sanpham.ngay_tao DESC
        ";
}

/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$page = isset($_GET['page'])
    ? intval($_GET['page'])
    : 1;

$limit = 12;

$offset = ($page - 1) * $limit;

/*
|--------------------------------------------------------------------------
| COUNT
|--------------------------------------------------------------------------
*/

$count_sql = "
    SELECT COUNT(*) as total

    FROM sanpham

    LEFT JOIN danhmuc
    ON sanpham.danh_muc_id = danhmuc.id

    WHERE $where_sql
";

$stmt = $db->prepare($count_sql);

$stmt->execute($params);

$total_products = $stmt->fetch()['total'];

$total_pages = ceil($total_products / $limit);

/*
|--------------------------------------------------------------------------
| PRODUCTS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        sanpham.*,
        danhmuc.ten_danh_muc

    FROM sanpham

    LEFT JOIN danhmuc
    ON sanpham.danh_muc_id = danhmuc.id

    WHERE $where_sql

    $order_sql

    LIMIT $limit OFFSET $offset
";

$stmt = $db->prepare($sql);

$stmt->execute($params);

$products = $stmt->fetchAll();


/*
|------------------------------------------------------------------
| FILTER GIÁ SAU KHUYẾN MÃI
|------------------------------------------------------------------
*/

$filtered_products = [];

foreach ($products as $product) {

    $promotion_data = getFinalProductPrice($db, $product);

    $final_price = $promotion_data['final_price'];

    if (
        ($min_price > 0 && $final_price < $min_price)
        ||
        ($max_price > 0 && $final_price > $max_price)
    ) {
        continue;
    }

    $product['final_price'] = $final_price;

    $product['discount_percent'] = $promotion_data['discount_percent'];

    $filtered_products[] = $product;
}

$products = $filtered_products;

/*
|--------------------------------------------------------------------------
| CATEGORIES
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT *
    FROM danhmuc
    WHERE trang_thai = 1
    ORDER BY ten_danh_muc
");

$categories = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/navbar.php';
?>

<style>
    .products-page {

        padding: 60px 0;
    }

    /*
|--------------------------------------------------------------------------
| SIDEBAR
|--------------------------------------------------------------------------
*/

    .filter-card {

        position: sticky;

        top: 100px;

        z-index: 10;

        border: none;

        border-radius: 24px;

        overflow: hidden;

        box-shadow: 0 10px 35px rgba(0, 0, 0, 0.06);

        transition: all .2s ease;
    }

    .filter-card .card-header {

        background: linear-gradient(135deg,
                #15803d,
                #166534);

        padding: 18px 22px;

        border: none;
    }

    .filter-card .card-body {

        padding: 22px;
    }

    .filter-card h6 {

        font-weight: 700;

        margin-bottom: 12px;

        color: #111827;
    }

    .filter-card .form-select,
    .filter-card .form-control {

        border-radius: 14px;

        padding: 12px 14px;

        border: 1px solid #dbe3ea;
    }

    .filter-card .form-select:focus,
    .filter-card .form-control:focus {

        box-shadow: none;

        border-color: #198754;
    }

    /*
|--------------------------------------------------------------------------
| PRODUCT CARD
|--------------------------------------------------------------------------
*/

    .product-card .card-body {

        padding: 14px;
    }

    .product-actions {

        display: flex;

        gap: 8px;
    }

    .product-actions .btn {

        flex: 1;

        border-radius: 10px;

        padding: 8px;

        font-size: 12px;

        font-weight: 700;
    }

    /*
|--------------------------------------------------------------------------
| NO IMAGE
|--------------------------------------------------------------------------
*/

    .no-image {

        width: 100%;

        aspect-ratio: 1/1;

        background: #f3f4f6;

        display: flex;

        align-items: center;

        justify-content: center;

        flex-direction: column;

        color: #9ca3af;
    }

    .no-image i {

        font-size: 42px;

        margin-bottom: 10px;
    }

    .no-image span {

        font-size: 14px;

        font-weight: 600;
    }

    /*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

    .pagination .page-link {

        border: none;

        border-radius: 12px;

        margin: 0 4px;

        color: #166534;

        font-weight: 600;

        padding: 10px 15px;
    }

    .pagination .page-item.active .page-link {

        background: #198754;

        color: #fff;
    }
</style>

<div class="products-page">

    <div class="container">

        <div class="row">

            <!-- SIDEBAR -->

            <div class="col-lg-3 mb-4">

                <div class="card filter-card">

                    <div class="card-header text-white">

                        <h5 class="mb-0">

                            <i class="fas fa-filter me-2"></i>

                            Bộ lọc sản phẩm

                        </h5>

                    </div>

                    <div class="card-body">

                        <form method="GET">

                            <!-- CATEGORY -->

                            <div class="mb-4">

                                <h6>Danh mục</h6>

                                <select
                                    name="category"
                                    class="form-select"
                                    onchange="this.form.submit()">

                                    <option value="0">

                                        Tất cả danh mục

                                    </option>

                                    <?php foreach ($categories as $cat): ?>

                                        <option
                                            value="<?php echo $cat['id']; ?>"
                                            <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>

                                            <?php echo htmlspecialchars($cat['ten_danh_muc']); ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <!-- PRICE -->

                            <div class="mb-4">

                                <h6>Khoảng giá</h6>

                                <div class="row g-2">

                                    <div class="col-6">

                                        <input
                                            type="number"
                                            name="min_price"
                                            class="form-control"
                                            placeholder="Từ"
                                            value="<?php echo $min_price > 0 ? $min_price : ''; ?>">

                                    </div>

                                    <div class="col-6">

                                        <input
                                            type="number"
                                            name="max_price"
                                            class="form-control"
                                            placeholder="Đến"
                                            value="<?php echo $max_price > 0 ? $max_price : ''; ?>">

                                    </div>

                                </div>

                            </div>

                            <!-- SORT -->

                            <div class="mb-4">

                                <h6>Sắp xếp</h6>

                                <select
                                    name="sort"
                                    class="form-select"
                                    onchange="this.form.submit()">

                                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>

                                        Mới nhất

                                    </option>

                                    <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>

                                        Giá tăng dần

                                    </option>

                                    <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>

                                        Giá giảm dần

                                    </option>

                                    <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>

                                        Xem nhiều nhất

                                    </option>

                                </select>

                            </div>

                            <input
                                type="hidden"
                                name="search"
                                value="<?php echo htmlspecialchars($search); ?>">

                            <button
                                type="submit"
                                class="btn btn-success w-100">

                                Áp dụng bộ lọc

                            </button>

                        </form>

                    </div>

                </div>

            </div>

            <!-- PRODUCTS -->

            <div class="col-lg-9">

                <div class="d-flex justify-content-between align-items-center mb-4">

                    <h3 class="fw-bold mb-0">

                        Sản phẩm

                    </h3>

                    <span class="text-muted">

                        Có <?php echo $total_products; ?> sản phẩm

                    </span>

                </div>

                <?php if (count($products) > 0): ?>

                    <div class="row g-4">

                        <?php foreach ($products as $product): ?>

                            <?php

                            $product_image = '';

                            if (!empty($product['hinh_anh'])) {

                                $product_image =
                                    BASE_URL .
                                    '/public/assets/images/products/' .
                                    $product['hinh_anh'];
                            }
                            ?>

                            <div class="col-lg-4 col-md-6 col-6">

                                <div class="product-card card h-100">

                                    <div class="product-image">

                                        <?php if ($product_image): ?>

                                            <img
                                                src="<?php echo $product_image; ?>"
                                                class="card-img-top"
                                                alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">

                                        <?php else: ?>

                                            <div class="no-image">

                                                <i class="fas fa-image"></i>

                                                <span>Chưa có ảnh</span>

                                            </div>

                                        <?php endif; ?>

                                        <?php if ($product['discount_percent'] > 0): ?>

                                            <span class="sale-badge">

                                                -<?php echo round($product['discount_percent']); ?>%

                                            </span>

                                        <?php elseif ($product['gia_khuyen_mai'] && $product['gia_khuyen_mai'] < $product['gia_ban']): ?>

                                            <span class="sale-badge">

                                                -<?php echo round((1 - $product['gia_khuyen_mai'] / $product['gia_ban']) * 100); ?>%

                                            </span>

                                        <?php endif; ?>

                                    </div>

                                    <div class="card-body text-center">

                                        <h5 class="product-title">

                                            <?php echo htmlspecialchars($product['ten_san_pham']); ?>

                                        </h5>

                                        <div class="product-price mb-3">

                                            <?php if ($product['discount_percent'] > 0): ?>

                                                <span class="old-price">

                                                    <?php echo formatPrice($product['gia_ban']); ?>

                                                </span>

                                                <span class="current-price">

                                                    <?php echo formatPrice($product['final_price']); ?>

                                                </span>

                                            <?php elseif ($product['gia_khuyen_mai'] && $product['gia_khuyen_mai'] < $product['gia_ban']): ?>

                                                <span class="old-price">

                                                    <?php echo formatPrice($product['gia_ban']); ?>

                                                </span>

                                                <span class="current-price">

                                                    <?php echo formatPrice($product['gia_khuyen_mai']); ?>

                                                </span>

                                            <?php else: ?>

                                                <span class="current-price">

                                                    <?php echo formatPrice($product['gia_ban']); ?>

                                                </span>

                                            <?php endif; ?>

                                        </div>

                                        <div class="product-actions">

                                            <button
                                                onclick="addToCart(<?php echo $product['id']; ?>)"
                                                class="btn btn-success btn-sm">

                                                <i class="fas fa-cart-plus me-1"></i>

                                                Thêm

                                            </button>

                                            <a
                                                href="<?php echo BASE_URL; ?>/public/pages/product-detail.php?id=<?php echo $product['id']; ?>"
                                                class="btn btn-outline-success btn-sm">

                                                <i class="fas fa-eye me-2"></i>
                                                Chi tiết

                                            </a>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                    <!-- PAGINATION -->

                    <?php if ($total_pages > 1): ?>

                        <nav class="mt-5">

                            <ul class="pagination justify-content-center">

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>

                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">

                                        <a
                                            class="page-link"
                                            href="?page=<?php echo $i; ?>&category=<?php echo $category_id; ?>&search=<?php echo urlencode($search); ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>&sort=<?php echo $sort; ?>">

                                            <?php echo $i; ?>

                                        </a>

                                    </li>

                                <?php endfor; ?>

                            </ul>

                        </nav>

                    <?php endif; ?>

                <?php else: ?>

                    <div class="alert alert-info text-center p-5 rounded-4">

                        <i class="fas fa-box-open fa-3x mb-3"></i>

                        <h5>Không tìm thấy sản phẩm phù hợp</h5>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>

<?php include '../includes/footer.php'; ?>