<?php
    require_once '../includes/config.php';
    require_once '../includes/promotion_helper.php';

    if (! isLoggedIn()) {
    $_SESSION['cart'] = [];
    }

    // Khởi tạo giỏ hàng
    if (! isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
    }

    // Xử lý thêm vào giỏ
    if (isset($_GET['action']) && $_GET['action'] == 'add') {

    $product_id = intval($_GET['id']);
    $quantity   = isset($_GET['quantity'])
        ? intval($_GET['quantity'])
        : 1;

    if ($quantity <= 0) {

        $quantity = 1;
    }

    if ($product_id > 0) {

        $db = getDB();

        $stmt = $db->prepare("
            SELECT * FROM sanpham
            WHERE id = ? AND trang_thai = 1
        ");

        $stmt->execute([$product_id]);

        $product = $stmt->fetch();

        if ($product) {

            // SỐ LƯỢNG HIỆN TẠI TRONG GIỎ
            $current_quantity =
            isset($_SESSION['cart'][$product_id])
                ? $_SESSION['cart'][$product_id]['quantity']
                : 0;

            // TỔNG SAU KHI THÊM
            $new_quantity =
                $current_quantity + $quantity;

            /*
    |---------------------------------------------------
    | HẾT HÀNG
    |---------------------------------------------------
    */
            if ($product['so_luong'] <= 0) {

                $_SESSION['error'] =
                    'Sản phẩm hiện đã hết hàng';

                redirect(
                    '/public/pages/product-detail.php?id='
                    . $product_id
                );
            }

            /*
    |---------------------------------------------------
    | VƯỢT QUÁ TỒN KHO
    |---------------------------------------------------
    */
            if ($new_quantity > $product['so_luong']) {

                $_SESSION['error'] =
                    'Chỉ còn '
                    . $product['so_luong']
                    . ' sản phẩm trong kho';

                redirect(
                    '/public/pages/product-detail.php?id='
                    . $product_id
                );
            }

            // GIÁ
            $price_data =
                getFinalProductPrice($db, $product);

            // ĐÃ CÓ TRONG GIỎ
            if (isset($_SESSION['cart'][$product_id])) {

                $_SESSION['cart'][$product_id]['quantity']
                += $quantity;
            } else {

                // THÊM MỚI
                $_SESSION['cart'][$product_id] = [

                    'id'               => $product['id'],

                    'name'             => $product['ten_san_pham'],

                    'price'            => $price_data['original_price'],

                    'final_price'      => $price_data['final_price'],

                    'discount_percent' => $price_data['discount_percent'],

                    'quantity'         => $quantity,

                    'image'            => $product['hinh_anh'],
                ];
            }

            $_SESSION['success']  =
                'Đã thêm sản phẩm vào giỏ hàng';
        }
    }

    redirect('/public/pages/cart.php');
    }

    // Xử lý cập nhật giỏ hàng
    if (isset($_POST['quantity'])) {

    $db = getDB();

    foreach ($_POST['quantity'] as $id => $qty) {

        $id = intval($id);

        $qty = intval($qty);

        // Lấy sản phẩm
        $stmt = $db->prepare("
            SELECT so_luong
            FROM sanpham
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        $product = $stmt->fetch();

        // Không tồn tại
        if (! $product) {

            continue;
        }

        // Hết hàng
        if ($product['so_luong'] <= 0) {

            unset($_SESSION['cart'][$id]);

            $_SESSION['error'] =
                'Một sản phẩm trong giỏ đã hết hàng';

            continue;
        }

        // Vượt tồn kho
        if ($qty > $product['so_luong']) {

            $qty = $product['so_luong'];

            $_SESSION['error'] =
                'Một số sản phẩm đã được giảm về mức tồn kho hiện có';
        }

        // Xóa sản phẩm
        if ($qty <= 0) {

            unset($_SESSION['cart'][$id]);
        } else {

            $_SESSION['cart'][$id]['quantity'] = $qty;
        }
    }

    redirect('/public/pages/cart.php');
    }

    // Xử lý xóa sản phẩm
    if (isset($_GET['remove']) && isset($_GET['id'])) {

    $id = intval($_GET['id']);

    unset($_SESSION['cart'][$id]);

    redirect('/public/pages/cart.php');
    }

    /*
|---------------------------------------------------
| LOAD TỒN KHO MỚI NHẤT
|---------------------------------------------------
*/

    $db = getDB();

    $product_stock = [];

    if (! empty($_SESSION['cart'])) {

    $cart_ids = array_keys($_SESSION['cart']);

    $placeholders =
        implode(',', array_fill(0, count($cart_ids), '?'));

    $stmt = $db->prepare("
        SELECT id, so_luong
        FROM sanpham
        WHERE id IN ($placeholders)
    ");

    $stmt->execute($cart_ids);

    while ($row = $stmt->fetch()) {

        $product_stock[$row['id']] =
            $row['so_luong'];
    }
    }

    /*
|---------------------------------------------------
| TÍNH TẠM TÍNH
|---------------------------------------------------
*/

    $total = 0;

    foreach ($_SESSION['cart'] as $id => $item) {

    // LOAD LẠI SẢN PHẨM MỚI NHẤT
    $stmt = $db->prepare("
        SELECT *
        FROM sanpham
        WHERE id = ?
        AND trang_thai = 1
    ");

    $stmt->execute([$id]);

    $product = $stmt->fetch();

    // KHÔNG TỒN TẠI -> XÓA KHỎI GIỎ
    if (! $product) {

        unset($_SESSION['cart'][$id]);

        continue;
    }

    // LẤY GIÁ MỚI NHẤT TỪ promotion_helper
    $price_data =
        getFinalProductPrice($db, $product);

    // CẬP NHẬT SESSION
    $_SESSION['cart'][$id]['price'] =
        $price_data['original_price'];

    $_SESSION['cart'][$id]['final_price'] =
        $price_data['final_price'];

    $_SESSION['cart'][$id]['discount_percent'] =
        $price_data['discount_percent'];

    // TÍNH TOTAL
    $total +=
        $price_data['final_price']
         * $item['quantity'];
    }

    /*
|---------------------------------------------------
| LOAD KHUYẾN MÃI ĐƠN HÀNG
|---------------------------------------------------
*/
    $db           = getDB();
    $current_time = date('Y-m-d H:i:s');

    /*
|---------------------------------------------------
| LOAD CẤU HÌNH SHIP
|---------------------------------------------------
*/

    $config_stmt = $db->query("
    SELECT ten_cau_hinh, gia_tri
    FROM cauhinh
");

    $configs = [];

    while ($row = $config_stmt->fetch()) {

    $configs[$row['ten_cau_hinh']]
    = $row['gia_tri'];
    }

    $shipping_fee_default =
    (int) ($configs['shipping_fee'] ?? 25000);

    $free_shipping_limit =
    (int) ($configs['free_shipping_limit'] ?? 200000);

    $stmt = $db->prepare("
    SELECT *
    FROM khuyenmai
    WHERE trang_thai = 1
    AND (
        ngay_bat_dau IS NULL
        OR ngay_bat_dau <= ?
    )
    AND (
        ngay_ket_thuc IS NULL
        OR ngay_ket_thuc >= ?
    )
");

    $stmt->execute([
    $current_time,
    $current_time,
    ]);

    $promotions  = $stmt->fetchAll();

    /*
|---------------------------------------------------
| GIẢM TIỀN ĐƠN / FREE SHIP
|---------------------------------------------------
*/

    $discount_amount = 0;

    $free_shipping_event = false;

    foreach ($promotions as $promo) {

    if ($total < $promo['don_hang_toi_thieu']) {

        continue;
    }

    if ($promo['loai_khuyen_mai'] == 'giam_tien_don') {

        $discount_amount += $promo['gia_tri'];
    }

    if ($promo['loai_khuyen_mai'] == 'free_ship') {

        $free_shipping_event = true;
    }
    }

    /*
|---------------------------------------------------
| SHIP
|---------------------------------------------------
*/

    if ($free_shipping_event) {

    $shipping_fee = 0;
    } else if ($total >= $free_shipping_limit) {

    $shipping_fee = 0;
    } else {

    $shipping_fee = $shipping_fee_default;
    }

    /*
|---------------------------------------------------
| TỔNG CUỐI
|---------------------------------------------------
*/

    $final_total =
    $total
     - $discount_amount
     + $shipping_fee;

    if ($final_total < 0) {

    $final_total = 0;
    }

    $page_title = 'Giỏ hàng';

    include '../includes/header.php';
    include '../includes/navbar.php';
?>

<div class="container py-4">

    <h1 class="mb-4">
        Giỏ hàng của bạn
    </h1>

    <?php if (count($_SESSION['cart']) > 0): ?>

        <div class="row">

            <!-- GIỎ HÀNG -->
            <div class="col-md-8">

                <div class="card shadow-sm">

                    <div class="card-body pb-3 d-flex flex-column" style="padding-top: 0; padding-left: 0; padding-right: 0;">

                        <!-- FORM CHECKOUT -->
                        <form
                            method="POST"
                            action="<?php echo $_SERVER['PHP_SELF']; ?>"
                            id="cartForm">

                            <div class="cart-table-wrapper">

                                <table class="table table-hover align-middle mb-0">
                                    <thead>

                                        <tr>

                                            <th width="50">

                                                <input type="checkbox"
                                                    id="checkAll"
                                                    checked>

                                            </th>

                                            <th>Sản phẩm</th>

                                            <th>Đơn giá</th>

                                            <th width="150">
                                                Số lượng
                                            </th>

                                            <th>Thành tiền</th>

                                            <th></th>

                                        </tr>

                                    </thead>

                                    <tbody>

                                        <?php foreach ($_SESSION['cart'] as $id => $item): ?>

                                            <?php
                                                $item_total =
                                                    ($_SESSION['cart'][$id]['final_price'])
                                                     * $item['quantity'];
                                            ?>

                                            <tr>

                                                <!-- CHECKBOX -->
                                                <td class="align-middle">

                                                    <input type="checkbox"
                                                        name="selected_items[]"
                                                        value="<?php echo $id; ?>"
                                                        class="item-checkbox"
                                                        data-total="<?php echo $item_total; ?>"
                                                        checked>

                                                </td>

                                                <!-- THÔNG TIN -->
                                                <td>

                                                    <div class="d-flex align-items-center">

                                                        <img
                                                            src="<?php echo BASE_URL; ?>/public/assets/images/products/<?php echo htmlspecialchars($item['image']); ?>"
                                                            class="rounded me-3"
                                                            style=" width: 60px;
        height: 60px;
        object-fit: cover;
    "
                                                            onerror="this.src='https://placehold.co/60x60/e9ecef/6c757d?text=No+Image'">

                                                        <div>

                                                            <h6 class="mb-0">

                                                                <?php echo htmlspecialchars($item['name']); ?>

                                                            </h6>

                                                        </div>

                                                    </div>

                                                </td>

                                                <!-- GIÁ -->
                                                <td class="align-middle">

                                                    <?php if ($item['discount_percent'] > 0): ?>

                                                        <div>

                                                            <small class="text-decoration-line-through text-muted d-block">

                                                                <?php echo formatPrice($item['price']); ?>

                                                            </small>

                                                            <span class="text-danger fw-bold">

                                                                <?php echo formatPrice($item['final_price']); ?>

                                                            </span>

                                                        </div>

                                                    <?php else: ?>

                                                        <?php echo formatPrice($item['price']); ?>

                                                    <?php endif; ?>

                                                </td>

                                                <!-- SỐ LƯỢNG -->
                                                <td class="align-middle">

                                                    <input
                                                        type="number"
                                                        name="quantity[<?php echo $id; ?>]"
                                                        value="<?php echo $item['quantity']; ?>"
                                                        min="1"
                                                        data-stock="<?php echo isset($product_stock[$id]) ? $product_stock[$id] : 0; ?>"
                                                        data-price="<?php echo $item['final_price']; ?>"
                                                        data-original-price="<?php echo $item['price']; ?>"
                                                        class="form-control form-control-sm quantity-input"
                                                        style="width: 80px;">

                                                </td>

                                                <!-- THÀNH TIỀN -->
                                                <td class="fw-bold align-middle item-price">

                                                    <?php
                                                        echo formatPrice($item_total);
                                                    ?>

                                                </td>

                                                <!-- XÓA -->
                                                <td class="align-middle">

                                                    <a
                                                        href="?remove=1&id=<?php echo $id; ?>"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="return confirmRemove(this.href)">

                                                        <i class="fas fa-trash"></i>

                                                    </a>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                            <!-- BUTTON -->
                            <div class="mt-3 d-flex gap-2">

                                <a href="<?php echo BASE_URL; ?>/public/pages/products.php"
                                    class="btn btn-outline-secondary">

                                    Tiếp tục mua sắm

                                </a>

                            </div>

                    </div>
                </div>
            </div>

            <!-- THANH TOÁN -->
            <div class="col-md-4">

                <div class="card shadow-sm">

                    <div class="card-header bg-success text-white">

                        <h5 class="mb-0">
                            Thông tin đơn hàng
                        </h5>

                    </div>

                    <div class="card-body">

                        <div class="d-flex justify-content-between mb-2">

                            <span>Tạm tính:</span>

                            <span class="fw-bold"
                                id="subtotalText">

                                <?php echo formatPrice($total); ?>

                            </span>

                            <?php if ($discount_amount > 0): ?>

                                <div class="d-flex justify-content-between mb-2">

                                    <span>Giảm giá:</span>

                                    <span class="fw-bold text-danger">

                                        -<?php echo formatPrice($discount_amount); ?>

                                    </span>

                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="d-flex justify-content-between mb-2">

                            <span>Phí vận chuyển:</span>

                            <span class="fw-bold"
                                id="shippingText">

                                <?php
                                    if ($free_shipping_event || $total >= $free_shipping_limit) {

                                        echo '<span class="text-success">Miễn phí</span>';
                                    } else {

                                        echo formatPrice($shipping_fee_default);
                                    }
                                ?>

                            </span>

                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-1">

                            <span class="h5">
                                Tổng cộng:
                            </span>

                            <span class="h5 text-danger fw-bold"
                                id="totalText">

                                <?php

                                    echo formatPrice($final_total);

                                ?>

                            </span>

                        </div>

                        <?php
                            $product_saved = 0;

                            foreach ($_SESSION['cart'] as $item) {

                                $product_saved +=
                                    ($item['price'] - $item['final_price'])
                                     * $item['quantity'];
                            }

                            $total_saved =
                                $product_saved + $discount_amount;

                            if ($free_shipping_event) {

                                $total_saved += 25000;
                            }
                        ?>

                        <?php if ($total_saved > 0): ?>

                            <div class="text-end mb-3">

                                <small class="text-success fw-bold"
                                    id="savingText">

                                    Bạn đã tiết kiệm
                                    <?php echo formatPrice($total_saved); ?>

                                </small>

                            </div>

                        <?php endif; ?>

                        <!-- BUTTON CHECKOUT -->
                        <button
                            type="submit"
                            formaction="<?php echo BASE_URL; ?>/public/pages/checkout.php"
                            class="btn btn-success w-100 btn-lg">

                            <i class="fas fa-credit-card me-2"></i>

                            Tiến hành thanh toán

                        </button>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    <?php else: ?>

        <div class="empty-cart-wrapper">

            <div class="empty-cart-box">

                <div class="empty-cart-icon">
                    <i class="fas fa-cart-shopping"></i>
                </div>

                <h2 class="empty-cart-title">
                    Giỏ hàng của bạn đang trống
                </h2>

                <p class="empty-cart-text">
                    Hãy thêm sản phẩm vào giỏ hàng để tiếp tục mua sắm
                </p>

                <a href="<?php echo BASE_URL; ?>/public/pages/products.php"
                    class="btn btn-success empty-cart-btn">

                    <i class="fas fa-store me-2"></i>
                    Mua sắm ngay

                </a>

            </div>

        </div>

    <?php endif; ?>

</div>

<style>
    /* EMPTY CART */

    .empty-cart-wrapper {

        padding-top: 10px;

        padding-bottom: 20px;
    }

    .empty-cart-box {

        width: 100%;

        background: #d9f2ff;

        border: 1px solid #b9e5f7;

        border-radius: 12px;

        padding: 45px 25px;

        text-align: center;
    }

    .empty-cart-icon {

        font-size: 55px;

        color: #0b5f73;

        margin-bottom: 12px;
    }

    .empty-cart-title {

        font-size: 30px;

        font-weight: 700;

        color: #0f172a;

        margin-bottom: 10px;
    }

    .empty-cart-text {

        font-size: 16px;

        color: #475569;

        margin-bottom: 22px;
    }

    .empty-cart-btn {

        padding: 10px 24px;

        border-radius: 10px;

        font-size: 15px;

        font-weight: 600;
    }

    /* CART TABLE */

    /* WRAPPER */

    .cart-table-wrapper {

        height: 290px;

        overflow-y: auto;

        overflow-x: hidden;

        border-radius: 12px;

        border: 1px solid #e9ecef;

        position: relative;
    }

    /* TABLE */

    .cart-table-wrapper table {

        margin-bottom: 0;
    }

    /* HEADER STICKY */

    .cart-table-wrapper thead th {

        position: sticky;

        top: 0;

        z-index: 20;

        background: #f8f9fa;

        font-weight: 700;

        color: #212529;

        border-bottom: 1px solid #dee2e6;

        padding-top: 14px;

        padding-bottom: 14px;

        box-shadow: 0 2px 4px rgba(0, 0, 0, .04);
    }

    /* ITEM */

    .cart-table-wrapper tbody tr {

        transition: .2s;
    }

    .cart-table-wrapper tbody tr:hover {

        background: #f8fafc;
    }

    /* ẢNH */

    .cart-table-wrapper img {

        width: 60px;

        height: 60px;

        object-fit: cover;

        border-radius: 10px;

        border: 1px solid #eee;
    }

    /* SCROLLBAR */

    .cart-table-wrapper::-webkit-scrollbar {

        width: 8px;
    }

    .cart-table-wrapper::-webkit-scrollbar-thumb {

        background: #cbd5e1;

        border-radius: 20px;
    }

    .cart-table-wrapper::-webkit-scrollbar-thumb:hover {

        background: #94a3b8;
    }

    #custom-toast {

        position: fixed;

        top: 20px;

        right: 20px;

        z-index: 9999;
    }

    .toast-message {

        min-width: 280px;

        padding: 14px 18px;

        margin-bottom: 10px;

        border-radius: 10px;

        color: white;

        font-weight: 500;

        box-shadow: 0 4px 12px rgba(0, 0, 0, .15);

        animation: slideIn .3s ease;
    }

    .toast-success {

        background: #198754;
    }

    .toast-error {

        background: #dc3545;
    }

    @keyframes slideIn {

        from {

            transform: translateX(100px);

            opacity: 0;
        }

        to {

            transform: translateX(0);

            opacity: 1;
        }
    }

    /* CONFIRM */

    #custom-confirm {

        position: fixed;

        inset: 0;

        background: rgba(0, 0, 0, .5);

        display: none;

        align-items: center;

        justify-content: center;

        z-index: 10000;
    }

    .confirm-box {

        background: white;

        width: 400px;

        max-width: 90%;

        border-radius: 12px;

        padding: 24px;
    }
</style>

<script>
    let freeShippingEvent =
        <?php echo $free_shipping_event ? 'true' : 'false'; ?>;

    let discountAmount =
        <?php echo $discount_amount; ?>;

    let shippingFeeDefault =
        <?php echo $shipping_fee_default; ?>;

    let freeShippingLimit =
        <?php echo $free_shipping_limit; ?>;

    // FORMAT TIỀN
    function formatCurrency(number) {

        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(number);

    }

    // TÍNH TỔNG
    function updateTotal() {

        let total = 0;

        let productSaving = 0;

        document.querySelectorAll('tbody tr')
            .forEach(function(row) {

                let checkbox =
                    row.querySelector('.item-checkbox');

                let quantityInput =
                    row.querySelector('.quantity-input');

                let itemPriceElement =
                    row.querySelector('.item-price');

                if (!checkbox.checked) {

                    return;
                }

                let price =
                    parseFloat(
                        quantityInput.dataset.price
                    );

                let originalPrice =
                    parseFloat(
                        quantityInput.dataset.originalPrice
                    );

                let quantity =
                    parseInt(quantityInput.value) || 1;

                let max =
                    parseInt(quantityInput.dataset.stock) || 1;

                // CHẶN VƯỢT TỒN
                if (quantity > max) {

                    quantity = max;

                    quantityInput.value = max;

                    showToast(
                        'Chỉ còn ' + max + ' sản phẩm trong kho',
                        'error'
                    );
                }

                let itemTotal =
                    price * quantity;

                itemPriceElement.innerText =
                    formatCurrency(itemTotal);

                total += itemTotal;

                // TIẾT KIỆM TỪ GIÁ GỐC
                productSaving +=
                    (originalPrice - price) * quantity;
            });

        /*
        |-----------------------------------
        | SHIP
        |-----------------------------------
        */
        let shipping = 0;

        if (!freeShippingEvent) {

            if (total > 0 && total < freeShippingLimit) {

                shipping = shippingFeeDefault;
            }
        }

        /*
        |-----------------------------------
        | TẠM TÍNH
        |-----------------------------------
        */
        document.getElementById('subtotalText')
            .innerText = formatCurrency(total);

        /*
        |-----------------------------------
        | SHIP TEXT
        |-----------------------------------
        */
        if (shipping > 0) {

            document.getElementById('shippingText')
                .innerText = formatCurrency(shipping);

        } else {

            document.getElementById('shippingText')
                .innerHTML =
                '<span class="text-success">Miễn phí</span>';
        }

        /*
        |-----------------------------------
        | TOTAL
        |-----------------------------------
        */
        let finalTotal =
            total - discountAmount + shipping;

        if (finalTotal < 0) {

            finalTotal = 0;
        }

        document.getElementById('totalText')
            .innerText =
            formatCurrency(finalTotal);

        /*
        |-----------------------------------
        | TỔNG TIẾT KIỆM
        |-----------------------------------
        */
        let saved =
            productSaving + discountAmount;

        if (freeShippingEvent) {

            saved += 25000;
        }

        let savingText =
            document.getElementById('savingText');

        if (savingText) {

            savingText.innerText =
                'Bạn đã tiết kiệm ' +
                formatCurrency(saved);
        }
    }

    // CHECK ALL
    document.getElementById('checkAll')
        .addEventListener('change', function() {

            let checked = this.checked;

            document.querySelectorAll('.item-checkbox')
                .forEach(function(item) {

                    item.checked = checked;

                });

            updateTotal();

        });

    // CHECK TỪNG ITEM
    document.querySelectorAll('.item-checkbox')
        .forEach(function(item) {

            item.addEventListener('change', function() {

                updateTotal();

            });

        });

    document.querySelectorAll('.quantity-input')
        .forEach(function(input) {

            input.addEventListener('change', function() {

                updateTotal();

                // AUTO SAVE SỐ LƯỢNG
                let form =
                    document.getElementById('cartForm');

                let formData =
                    new FormData();

                // CHỈ GỬI quantity
                document.querySelectorAll('.quantity-input')
                    .forEach(function(qtyInput) {

                        let name = qtyInput.name;

                        let value = qtyInput.value;

                        formData.append(name, value);

                    });

                fetch(window.location.href, {

                    method: 'POST',

                    body: formData
                });
            });

        });

    // TOAST
    function showToast(message, type = 'success') {

        let toast = document.createElement('div');

        toast.className =
            'toast-message toast-' + type;

        toast.innerText = message;

        document.getElementById('custom-toast')
            .appendChild(toast);

        setTimeout(function() {

            toast.remove();

        }, 3000);
    }

    // CONFIRM
    function confirmRemove(url) {

        document.getElementById('custom-confirm')
            .style.display = 'flex';

        document.getElementById('confirm-message')
            .innerText =
            'Bạn có chắc muốn xóa sản phẩm này?';

        document.getElementById('confirm-ok')
            .onclick = function() {

                window.location.href = url;
            };

        return false;
    }

    function closeConfirm() {

        document.getElementById('custom-confirm')
            .style.display = 'none';
    }

    // LOAD LẦN ĐẦU
    updateTotal();
</script>

<!-- TOAST -->
<div id="custom-toast"></div>

<!-- MODAL XÁC NHẬN -->
<div id="custom-confirm">

    <div class="confirm-box">

        <h5>Xác nhận</h5>

        <p id="confirm-message"></p>

        <div class="d-flex gap-2 justify-content-end mt-4">

            <button
                class="btn btn-secondary"
                onclick="closeConfirm()">
                Hủy
            </button>

            <button
                class="btn btn-danger"
                id="confirm-ok">
                Xóa
            </button>

        </div>

    </div>

</div>

<?php include '../includes/footer.php'; ?>