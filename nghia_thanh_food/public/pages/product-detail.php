<?php
require_once '../includes/config.php';
require_once '../includes/promotion_helper.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id <= 0) {
    redirect('/public/pages/products.php');
}

$db = getDB();

/*
|---------------------------------------------------
| LOAD CẤU HÌNH WEBSITE
|---------------------------------------------------
*/

$configs = [];

$stmt = $db->query("
    SELECT ten_cau_hinh, gia_tri
    FROM cauhinh
");

while ($row = $stmt->fetch()) {

    $configs[$row['ten_cau_hinh']]
        = $row['gia_tri'];
}

// Cập nhật lượt xem
$db->exec("UPDATE sanpham SET luot_xem = luot_xem + 1 WHERE id = $product_id");

// Lấy thông tin sản phẩm
$stmt = $db->prepare("SELECT sanpham.*, danhmuc.ten_danh_muc, danhmuc.id as danhmuc_id 
                      FROM sanpham 
                      LEFT JOIN danhmuc ON sanpham.danh_muc_id = danhmuc.id 
                      WHERE sanpham.id = ? AND sanpham.trang_thai = 1");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    redirect('/public/pages/products.php');
}

$page_title = $product['ten_san_pham'];

$price_data = getFinalProductPrice($db, $product);

$final_old_price = $price_data['original_price'];

$final_price = $price_data['final_price'];

$discount_percent = $price_data['discount_percent'];

// Lấy sản phẩm liên quan
$stmt = $db->prepare("SELECT * FROM sanpham WHERE trang_thai = 1 AND danh_muc_id = ? AND id != ? LIMIT 4");
$stmt->execute([$product['danhmuc_id'], $product_id]);
$related_products = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-5 mb-4">
            <div class="product-detail-image">

                <?php
                $product_image = '';

                if (!empty($product['hinh_anh'])) {

                    $product_image =
                        BASE_URL .
                        '/public/assets/images/products/' .
                        $product['hinh_anh'];
                }
                ?>

                <?php if ($product_image): ?>

                    <img
                        src="<?php echo $product_image; ?>"
                        class="img-fluid rounded shadow"
                        alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>"
                        style="width:100%; object-fit:cover;">

                <?php else: ?>

                    <div class="no-image rounded shadow">

                        <i class="fas fa-image"></i>

                        <span>Chưa có ảnh</span>

                    </div>

                <?php endif; ?>

            </div>
        </div>
        <div class="col-md-7">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/index.php">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/public/pages/products.php">Sản phẩm</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['ten_san_pham']); ?></li>
                </ol>
            </nav>

            <h1 class="mb-3"><?php echo htmlspecialchars($product['ten_san_pham']); ?></h1>

            <?php if (isset($_SESSION['error'])): ?>

                <div class="alert alert-danger">

                    <?php
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>

                </div>

            <?php endif; ?>

            <div class="mb-3">
                <span class="text-muted">Mã sản phẩm: <?php echo htmlspecialchars($product['ma_san_pham']); ?></span>
                <span class="ms-3 text-muted">Danh mục: <?php echo htmlspecialchars($product['ten_danh_muc']); ?></span>
                <span class="ms-3 text-muted">Lượt xem: <?php echo number_format($product['luot_xem']); ?></span>
            </div>

            <div class="product-price-detail mb-4">
                <?php if ($discount_percent > 0): ?>
                    <div class="h2 text-danger fw-bold mb-2"><?php echo formatPrice($final_price); ?></div>
                    <div class="text-muted">
                        <del class="h5"><?php echo formatPrice($final_old_price); ?></del>
                        <span class="ms-2 badge bg-danger">Tiết kiệm <?php echo formatPrice($final_old_price - $final_price); ?></span>
                    </div>
                <?php else: ?>
                    <div class="h2 text-danger fw-bold"><?php echo formatPrice($product['gia_ban']); ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <div class="d-flex align-items-center mb-3">
                    <span class="fw-bold me-3">Số lượng:</span>
                    <div class="input-group" style="width: 130px;">
                        <button class="btn btn-outline-secondary" type="button" onclick="decrementQuantity()">-</button>
                        <input type="number" id="quantity" class="form-control text-center" value="1" min="1" max="<?php echo intval($product['so_luong']); ?>">
                        <button class="btn btn-outline-secondary" type="button" onclick="incrementQuantity()">+</button>
                    </div>
                    <span class="ms-3 text-muted"><?php echo $product['so_luong']; ?> sản phẩm có sẵn</span>
                </div>
            </div>

            <div class="mb-4">
                <button
                    type="button"
                    id="add-cart-btn"
                    data-product-id="<?php echo $product['id']; ?>"
                    class="btn btn-success btn-lg me-2">
                    <i class="fas fa-shopping-cart me-2"></i>Thêm vào giỏ hàng
                </button>
                <button onclick="buyNow(<?php echo $product['id']; ?>)" class="btn btn-warning btn-lg">
                    <i class="fas fa-bolt me-2"></i>Mua ngay
                </button>
            </div>

            <div
                class="p-3 mb-4 rounded"
                style="
        background: #e8fff1;
        border: 1px solid #b7ebc6;
        color: #198754;
        font-weight: 600;
    ">

                <i class="fas fa-truck me-2"></i>

                Miễn phí vận chuyển cho đơn hàng từ

                <?php
                echo formatPrice(
                    $configs['free_shipping_limit']
                        ?? 200000
                );
                ?>

            </div>
        </div>
    </div>
    <div class="row mt-5">
        <div class="col-12">
            <ul class="nav nav-tabs" id="productTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab">Mô tả sản phẩm</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="specs-tab" data-bs-toggle="tab" data-bs-target="#specs" type="button" role="tab">Thông tin chi tiết</button>
                </li>
            </ul>
            <div class="tab-content p-4 border border-top-0 rounded-bottom" id="productTabContent">
                <div class="tab-pane fade show active" id="description" role="tabpanel">
                    <?php echo nl2br(htmlspecialchars($product['mo_ta_chi_tiet'] ?: $product['mo_ta_ngan'])); ?>
                </div>
                <div class="tab-pane fade" id="specs" role="tabpanel">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 200px;">Tên sản phẩm</th>
                            <td><?php echo htmlspecialchars($product['ten_san_pham']); ?></td>
                        </tr>
                        <tr>
                            <th>Mã sản phẩm</th>
                            <td><?php echo htmlspecialchars($product['ma_san_pham']); ?></td>
                        </tr>
                        <tr>
                            <th>Đơn vị tính</th>
                            <td><?php echo htmlspecialchars($product['don_vi_tinh']); ?></td>
                        </tr>
                        <tr>
                            <th>Số lượng tồn</th>
                            <td><?php echo number_format($product['so_luong']); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if (count($related_products) > 0): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="section-title text-center mb-4">Sản phẩm liên quan</h3>
                <div class="row g-4">
                    <?php foreach ($related_products as $related): ?>
                        <div class="col-md-3 col-6">
                            <div class="product-card card h-100">
                                <div class="product-image">
                                    <?php
                                    $related_image = '';

                                    if (!empty($related['hinh_anh'])) {

                                        $related_image =
                                            BASE_URL .
                                            '/public/assets/images/products/' .
                                            $related['hinh_anh'];
                                    }
                                    ?>

                                    <?php if ($related_image): ?>

                                        <img
                                            src="<?php echo $related_image; ?>"
                                            class="card-img-top"
                                            alt="<?php echo htmlspecialchars($related['ten_san_pham']); ?>">

                                    <?php else: ?>

                                        <div class="no-image">

                                            <i class="fas fa-image"></i>

                                            <span>Chưa có ảnh</span>

                                        </div>

                                    <?php endif; ?>
                                </div>
                                <div class="card-body text-center">
                                    <h6 class="card-title"><?php echo htmlspecialchars($related['ten_san_pham']); ?></h6>
                                    <div class="product-price">
                                        <span class="current-price text-danger fw-bold"><?php echo formatPrice($related['gia_ban']); ?></span>
                                    </div>
                                    <div class="mt-2">
                                        <a href="<?php echo BASE_URL; ?>/public/pages/product-detail.php?id=<?php echo $related['id']; ?>" class="btn btn-sm btn-outline-success">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    function showStockMessage(message, type = 'danger') {

        let oldAlert = document.getElementById('stock-alert');

        if (oldAlert) {
            oldAlert.remove();
        }

        let alertBox = document.createElement('div');

        alertBox.id = 'stock-alert';

        alertBox.className =
            'alert alert-' + type + ' mt-3';

        alertBox.innerHTML = message;

        document.querySelector('.product-price-detail')
            .appendChild(alertBox);
    }

    function decrementQuantity() {

        let input = document.getElementById('quantity');

        let value = parseInt(input.value) || 1;

        if (value > 1) {

            input.value = value - 1;
        }
    }

    function incrementQuantity() {

        let input = document.getElementById('quantity');

        let value = parseInt(input.value) || 1;

        let max = parseInt(input.max) || 0;

        // HẾT HÀNG
        if (max <= 0) {

            showStockMessage(
                'Sản phẩm hiện đã hết hàng'
            );

            return;
        }

        // ĐẠT GIỚI HẠN
        if (value >= max) {

            showStockMessage(
                'Chỉ còn ' + max + ' sản phẩm trong kho',
                'warning'
            );

            input.value = max;

            return;
        }

        input.value = value + 1;
    }

    function validateQuantity() {

        let input = document.getElementById('quantity');

        let quantity = parseInt(input.value) || 1;

        let max = parseInt(input.max) || 0;

        // HẾT HÀNG
        if (max <= 0) {

            showStockMessage(
                'Sản phẩm hiện đã hết hàng'
            );

            return false;
        }

        // NHẬP QUÁ TỒN KHO
        if (quantity > max) {

            showStockMessage(
                'Chỉ còn ' + max + ' sản phẩm trong kho'
            );

            input.value = max;

            return false;
        }

        return true;
    }

    function buyNow(productId) {

        if (!validateQuantity()) {

            return;
        }

        let quantity =
            document.getElementById('quantity').value;

        window.location.href =
            '<?php echo BASE_URL; ?>/public/pages/cart.php?action=add&id=' +
            productId +
            '&quantity=' +
            quantity +
            '&checkout=1';
    }

    document
        .getElementById('quantity')
        .addEventListener('input', function() {

            let max = parseInt(this.max) || 0;

            let value = parseInt(this.value) || 1;

            // HẾT HÀNG
            if (max <= 0) {

                this.value = 1;

                showStockMessage(
                    'Sản phẩm hiện đã hết hàng'
                );

                return;
            }

            // QUÁ TỒN
            if (value > max) {

                this.value = max;

                showStockMessage(
                    'Chỉ còn ' + max + ' sản phẩm trong kho',
                    'warning'
                );
            }

            // NHỎ HƠN 1
            if (value < 1) {

                this.value = 1;
            }

        });
    document
        .getElementById('add-cart-btn')
        .addEventListener('click', function(e) {

            e.preventDefault();

            if (!validateQuantity()) {

                return;
            }

            let quantityInput =
                document.getElementById('quantity');

            let quantity =
                parseInt(quantityInput.value);

            let productId =
                this.dataset.productId;

            console.log('QUANTITY =', quantity);

            window.location.href =
                '<?php echo BASE_URL; ?>/public/pages/cart.php?action=add&id=' +
                productId +
                '&quantity=' +
                quantity;
        });
</script>

<?php include '../includes/footer.php'; ?>