<?php
    require_once '../includes/config.php';
    require_once '../includes/promotion_helper.php';

    $db = getDB();

    /*
|---------------------------------------------------
| LOAD SHIPPING CONFIG
|---------------------------------------------------
*/

    $stmt = $db->query("
    SELECT ten_cau_hinh, gia_tri
    FROM cauhinh
    WHERE ten_cau_hinh IN (
        'shipping_fee',
        'free_shipping_limit'
    )
");

    $shipping_config = [];

    while ($row = $stmt->fetch()) {

    $shipping_config[$row['ten_cau_hinh']] =
        $row['gia_tri'];
    }

    $default_shipping_fee =
    intval(
    $shipping_config['shipping_fee'] ?? 25000
    );

    $free_shipping_limit =
    intval(
    $shipping_config['free_shipping_limit'] ?? 200000
    );

    $checkout_items = [];
    $total          = 0;

    $phi_van_chuyen      = 0;
    $tong_thanh_toan     = 0;
    $discount_amount     = 0;
    $discount_percent    = 0;
    $free_shipping_event = false;

    // =========================
    // MUA NGAY
    // =========================
    if (isset($_GET['buy_now'])) {

    $product_id = intval($_GET['id']);
    $quantity   = intval($_GET['quantity']);

    $db = getDB();

    $stmt = $db->prepare("
        SELECT * FROM sanpham
        WHERE id = ? AND trang_thai = 1
    ");

    $stmt->execute([$product_id]);

    $product = $stmt->fetch();

    if ($product) {

        $price_data =
            getFinalProductPrice($db, $product);

        $checkout_items[] = [
            'id'               => $product['id'],
            'name'             => $product['ten_san_pham'],
            'price'            => $price_data['original_price'],
            'final_price'      => $price_data['final_price'],
            'discount_percent' => $price_data['discount_percent'],
            'quantity'         => $quantity,
        ];

        $total =
            $price_data['final_price']
             * $quantity;
    }

    } else {

    // =========================
    // NHẬN DANH SÁCH SP ĐƯỢC TÍCH
    // =========================

    if (isset($_POST['selected_items'])) {

        $_SESSION['selected_checkout_items']
        = $_POST['selected_items'];
    }

    // =========================
    // KHÔNG CÓ SP ĐƯỢC CHỌN
    // =========================

    if (
        ! isset($_SESSION['selected_checkout_items']) ||
        empty($_SESSION['selected_checkout_items'])
    ) {

        $_SESSION['checkout_error']
        = 'Vui lòng chọn ít nhất 1 sản phẩm';

        redirect('/public/pages/cart.php');
    }

    $selected_products =
        $_SESSION['selected_checkout_items'];

    // =========================
    // LẤY SẢN PHẨM TỪ CART
    // =========================

    foreach ($selected_products as $product_id) {

        if (isset($_SESSION['cart'][$product_id])) {

            $item =
                $_SESSION['cart'][$product_id];

            $checkout_items[$product_id]
            = $item;

            $total +=
                $item['final_price']
                 * $item['quantity'];
        }
    }

    // =========================
    // KHÔNG CÓ ITEM HỢP LỆ
    // =========================

    if (empty($checkout_items)) {

        $_SESSION['checkout_error']
        = 'Không có sản phẩm hợp lệ';

        redirect('/public/pages/cart.php');
    }
    }

    /*
|---------------------------------------------------
| LOAD KHUYẾN MÃI
|---------------------------------------------------
*/

    $current_time = date('Y-m-d H:i:s');

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
    ORDER BY id DESC
");

    $stmt->execute([
    $current_time,
    $current_time,
    ]);

    $promotions = $stmt->fetchAll();

    /*
|---------------------------------------------------
| GIẢM TIỀN / FREE SHIP
|---------------------------------------------------
*/

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

    $phi_van_chuyen = 0;

    } else if ($total >= $free_shipping_limit) {

    $phi_van_chuyen = 0;

    } else {

    $phi_van_chuyen = $default_shipping_fee;
    }

    /*
|---------------------------------------------------
| TỔNG
|---------------------------------------------------
*/

    $tong_thanh_toan =
    $total
     - $discount_amount
     + $phi_van_chuyen;

    if ($tong_thanh_toan < 0) {

    $tong_thanh_toan = 0;
    }

    // =========================
    // KIỂM TRA ĐĂNG NHẬP
    // =========================

    if (! isLoggedIn()) {

    $_SESSION['redirect_after_login']
    = $_SERVER['REQUEST_URI'];

    redirect('/public/pages/login.php');
    }

    // =========================
    // XỬ LÝ ĐẶT HÀNG
    // =========================

    if (
    $_SERVER['REQUEST_METHOD'] == 'POST'
    && isset($_POST['place_order'])
    ) {

    $db = getDB();

    $ho_ten     = safeInput($_POST['ho_ten']);
    $email      = safeInput($_POST['email']);
    $dien_thoai = safeInput($_POST['dien_thoai']);
    $dia_chi    = safeInput($_POST['dia_chi']);
    $thanh_pho  = safeInput($_POST['thanh_pho']);
    $ghi_chu    = safeInput($_POST['ghi_chu']);

    $phuong_thuc_thanh_toan = 'cod';

    $ma_don_hang =
    'DH' . date('YmdHis') . rand(100, 999);

    try {

        $db->beginTransaction();

        // =========================
        // THÊM ĐƠN HÀNG
        // =========================

        date_default_timezone_set('Asia/Ho_Chi_Minh');

        $now = date('Y-m-d H:i:s');

        $stmt = $db->prepare("
            INSERT INTO donhang (
                ma_don_hang,
                user_id,
                ho_ten_nguoi_nhan,
                email_nguoi_nhan,
                dien_thoai_nguoi_nhan,
                dia_chi_giao_hang,
                thanh_pho,
                ghi_chu,
                tong_tien,
                phi_ship,
                tong_thanh_toan,
                phuong_thuc_thanh_toan,
                trang_thai,
                ngay_dat,
                ngay_cap_nhat
            )
            VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $ma_don_hang,
            $_SESSION['user_id'],
            $ho_ten,
            $email,
            $dien_thoai,
            $dia_chi,
            $thanh_pho,
            $ghi_chu,
            $total,
            $phi_van_chuyen,
            $tong_thanh_toan,
            $phuong_thuc_thanh_toan,
            'cho_xac_nhan',
            $now,
            $now,
        ]);

        $don_hang_id = $db->lastInsertId();

        // =========================
        // THÊM CHI TIẾT ĐƠN HÀNG
        // =========================

        foreach ($checkout_items as $item) {

            $thanh_tien =
                $item['final_price']
                 * $item['quantity'];

            $stmt = $db->prepare("
                INSERT INTO chitietdonhang (
                    don_hang_id,
                    san_pham_id,
                    ten_san_pham,
                    gia,
                    so_luong,
                    thanh_tien
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $don_hang_id,
                $item['id'],
                $item['name'],
                $item['final_price'],
                $item['quantity'],
                $thanh_tien,
            ]);

            // =========================
            // TRỪ KHO
            // =========================

            $stmt = $db->prepare("
                UPDATE sanpham
                SET so_luong = so_luong - ?
                WHERE id = ?
            ");

            $stmt->execute([
                $item['quantity'],
                $item['id'],
            ]);
        }

        $db->commit();

        // =============================================
        // GỬI THÔNG BÁO KHI ĐẶT HÀNG THÀNH CÔNG
        // =============================================

        // Tạo link đến chi tiết đơn hàng trong profile
        $order_detail_link = BASE_URL . '/public/pages/profile.php?order_id=' . $don_hang_id . '#orders-tab';

        // Gửi thông báo cho chính user vừa đặt hàng (có link)
        sendNotification(
            $db,
            $_SESSION['user_id'],
            'don_hang',
            'tao_moi',
            $don_hang_id,
            '✅ Đơn hàng đã đặt thành công',
            'Đơn hàng #' . $ma_don_hang . ' đã được tạo với tổng thanh toán ' . formatPrice($tong_thanh_toan),
            $order_detail_link
        );

        // Lấy danh sách admin (vai_tro = 1)
        $stmt_admin = $db->prepare("SELECT id FROM users WHERE vai_tro = 1 AND trang_thai = 1");
        $stmt_admin->execute();
        $admin_ids = $stmt_admin->fetchAll(PDO::FETCH_COLUMN);

        // Gửi thông báo cho admin (không cần link hoặc có thể link đến admin)
        if (! empty($admin_ids)) {
            foreach ($admin_ids as $admin_id) {
                sendNotification(
                    $db,
                    $admin_id,
                    'don_hang',
                    'tao_moi',
                    $don_hang_id,
                    '🛒 ĐƠN HÀNG MỚI #' . $ma_don_hang,
                    'Khách hàng ' . $ho_ten . ' vừa đặt đơn hàng trị giá ' . formatPrice($tong_thanh_toan),
                    null
                );
            }
        }

        // =========================
        // XÓA CHỈ SP ĐÃ THANH TOÁN
        // =========================

        if (! isset($_GET['buy_now'])) {

            foreach ($checkout_items as $item) {

                unset($_SESSION['cart'][$item['id']]);
            }
        }

        // XÓA SESSION TẠM
        unset($_SESSION['selected_checkout_items']);

        $_SESSION['order_success']
        = $ma_don_hang;

        redirect('/public/pages/order-success.php');

    } catch (Exception $e) {

        $db->rollBack();

        $error =
        "Có lỗi xảy ra: " . $e->getMessage();
    }
    }

    $page_title = 'Thanh toán';

    include '../includes/header.php';
    include '../includes/navbar.php';

    // =========================
    // LẤY THÔNG TIN USER
    // =========================

    $db = getDB();

    $stmt = $db->prepare("
    SELECT * FROM users
    WHERE id = ?
");

    $stmt->execute([$_SESSION['user_id']]);

    $user = $stmt->fetch();
?>

<div class="container my-5">

    <h1 class="mb-4">
        Thanh toán đơn hàng
    </h1>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="row">

        <!-- FORM -->
        <div class="col-md-7">

            <div class="card shadow-sm mb-4">

                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        Thông tin giao hàng
                    </h5>
                </div>

                <div class="card-body">

                    <form method="POST"
                          action=""
                          id="checkoutForm">

                        <input type="hidden"
                               name="place_order"
                               value="1">

                        <div class="mb-3">
                            <label class="form-label">
                                Họ và tên
                            </label>

                            <input type="text"
                                   name="ho_ten"
                                   class="form-control"
                                   required
                                   value="<?php echo htmlspecialchars($user['ho_ten']); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Email
                            </label>

                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   required
                                   value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Số điện thoại
                            </label>

                            <input type="tel"
                                   name="dien_thoai"
                                   class="form-control"
                                   required
                                   value="<?php echo htmlspecialchars($user['dien_thoai']); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Địa chỉ
                            </label>

                            <input type="text"
                                   name="dia_chi"
                                   class="form-control"
                                   required
                                   value="<?php echo htmlspecialchars($user['dia_chi']); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Thành phố
                            </label>

                            <input type="text"
                                   name="thanh_pho"
                                   class="form-control"
                                   value="Hà Nội">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Ghi chú
                            </label>

                            <textarea name="ghi_chu"
                                      class="form-control"
                                      rows="3"></textarea>
                        </div>

                    </form>

                </div>
            </div>
        </div>

        <!-- ORDER -->
<div class="col-md-5">

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                Đơn hàng của bạn
            </h5>
        </div>

        <div class="card-body">

            <table class="table table-sm align-middle">

                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th class="text-center">SL</th>
                        <th class="text-end">Tiền</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($checkout_items as $item): ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($item['name']); ?>
                        </td>

                        <td class="text-center">
                            <?php echo $item['quantity']; ?>
                        </td>

                        <td class="text-end">

                            <strong class="text-danger">

                                <?php
                                    echo formatPrice(
                                        $item['final_price']
                                         * $item['quantity']
                                    );
                                ?>

                            </strong>

                        </td>

                    </tr>

                    <?php endforeach; ?>

                </tbody>

                <tfoot>

                    <tr>

                        <th colspan="2">
                            Tạm tính
                        </th>

                        <th class="text-end">

                            <?php
                                echo formatPrice($total);
                            ?>

                        </th>

                    </tr>

                    <?php if ($discount_amount > 0): ?>

                    <tr>

                        <th colspan="2">
                            Giảm giá đơn hàng
                        </th>

                        <th class="text-end text-danger">

                            -<?php
                                 echo formatPrice(
                                     $discount_amount
                                 );
                             ?>

                        </th>

                    </tr>

                    <?php endif; ?>

                    <tr>

                        <th colspan="2">
                            Phí vận chuyển
                        </th>

                        <th class="text-end">

                            <?php
                                if ($phi_van_chuyen > 0) {

                                    echo formatPrice(
                                        $phi_van_chuyen
                                    );

                                } else {

                                    echo '
                                <span class="text-success">
                                    Miễn phí
                                </span>
                                ';
                                }
                            ?>

                        </th>

                    </tr>

                    <tr>

                        <th colspan="2">
                            Tổng thanh toán
                        </th>

                        <th class="text-end text-danger">

                            <?php
                                echo formatPrice(
                                    $tong_thanh_toan
                                );
                            ?>

                        </th>

                    </tr>

                </tfoot>

            </table>

            <hr>

            <button type="submit"
                    form="checkoutForm"
                    class="btn btn-success w-100 btn-lg">

                Đặt hàng

            </button>

        </div>
    </div>
</div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>