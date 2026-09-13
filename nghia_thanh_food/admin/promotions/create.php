<?php
    require_once __DIR__ . '/../includes/check_auth.php';

    $page_title = 'Thêm khuyến mãi';

    require_once '../includes/config.php';

    include '../includes/header.php';
    include '../includes/sidebar.php';

    $db = getDB();

    $error = '';

    /*
|---------------------------------------------------
| LẤY DANH MỤC
|---------------------------------------------------
*/

    $category_stmt = $db->query("
    SELECT *
    FROM danhmuc
    WHERE trang_thai = 1
");

    $categories = $category_stmt->fetchAll();

    /*
|---------------------------------------------------
| LẤY SẢN PHẨM
|---------------------------------------------------
*/

    $product_stmt = $db->query("
    SELECT *
    FROM sanpham
    WHERE trang_thai = 1
");

    $products = $product_stmt->fetchAll();

    /*
|---------------------------------------------------
| THÊM KHUYẾN MÃI
|---------------------------------------------------
*/

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $ten_khuyen_mai = safeInput($_POST['ten_khuyen_mai']);

    $loai_khuyen_mai = safeInput($_POST['loai_khuyen_mai']);

    $gia_tri = floatval($_POST['gia_tri']);

    $don_hang_toi_thieu = floatval($_POST['don_hang_toi_thieu']);

    $ap_dung_cho = safeInput($_POST['ap_dung_cho']);

    /*
    |---------------------------------------------------
    | XỬ LÝ GIỜ VIỆT NAM
    |---------------------------------------------------
    */

    $ngay_bat_dau = date(
        'Y-m-d H:i:s',
        strtotime($_POST['ngay_bat_dau'])
    );

    $ngay_ket_thuc = date(
        'Y-m-d H:i:s',
        strtotime($_POST['ngay_ket_thuc'])
    );

    $trang_thai = isset($_POST['trang_thai']) ? 1 : 0;

    /*
    |---------------------------------------------------
    | VALIDATE THỜI GIAN
    |---------------------------------------------------
    */

    if ($ngay_ket_thuc <= $ngay_bat_dau) {

        $error = 'Ngày kết thúc phải lớn hơn ngày bắt đầu!';
    } else {

        /*
        |---------------------------------------------------
        | DANH SÁCH ÁP DỤNG
        |---------------------------------------------------
        */

        $danh_sach_ap_dung = '';

        if ($ap_dung_cho == 'san_pham') {

            $selected_products = $_POST['san_pham_ids'] ?? [];

            $danh_sach_ap_dung = implode(',', $selected_products);
        }

        if ($ap_dung_cho == 'danh_muc') {

            $selected_categories = $_POST['danh_muc_ids'] ?? [];

            $danh_sach_ap_dung = implode(',', $selected_categories);
        }

        /*
        |---------------------------------------------------
        | INSERT
        |---------------------------------------------------
        */

        $stmt = $db->prepare("
            INSERT INTO khuyenmai
            (
                ten_khuyen_mai,
                loai_khuyen_mai,
                gia_tri,
                don_hang_toi_thieu,
                ap_dung_cho,
                danh_sach_ap_dung,
                ngay_bat_dau,
                ngay_ket_thuc,
                trang_thai
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $ten_khuyen_mai,
            $loai_khuyen_mai,
            $gia_tri,
            $don_hang_toi_thieu,
            $ap_dung_cho,
            $danh_sach_ap_dung,
            $ngay_bat_dau,
            $ngay_ket_thuc,
            $trang_thai,
        ]);

        /*
|---------------------------------------------------
| GỬI THÔNG BÁO KHUYẾN MÃI
|---------------------------------------------------
*/

        $promotion_id = $db->lastInsertId();

        $link = '/public/pages/promotions-detail.php?id=' . $promotion_id;

        switch ($loai_khuyen_mai) {

            case 'free_ship':

                $tieu_de = '🚚 Khuyến mãi Free Ship mới';

                $noi_dung =
                $ten_khuyen_mai .
                ' - Miễn phí vận chuyển cho đơn từ ' .
                formatPrice($don_hang_toi_thieu);

                break;

            case 'giam_tien_don':

                $tieu_de = '💸 Khuyến mãi giảm tiền mới';

                $noi_dung =
                $ten_khuyen_mai .
                ' - Giảm ngay ' .
                formatPrice($gia_tri) .
                ' cho đơn từ ' .
                formatPrice($don_hang_toi_thieu);

                break;

            default:

                $tieu_de = '🔥 Khuyến mãi giảm giá mới';

                $noi_dung =
                    $ten_khuyen_mai .
                    ' - Giảm ' .
                    $gia_tri .
                    '% cho khách hàng';

                break;
        }

        sendNotificationToAllUsers(
            $db,
            'khuyen_mai',
            'tao_moi',
            $promotion_id,
            $tieu_de,
            $noi_dung,
            $link
        );

        $_SESSION['success_message'] = 'Đã tạo khuyến mãi thành công!';

        redirect('/admin/promotions/list.php');
    }
    }

?>

<style>
    .promotion-wrapper {

        height: calc(100vh - 70px);

        overflow: hidden;
    }

    .promotion-scroll {

        height: calc(100vh - 120px);

        overflow-y: auto;

        padding-right: 6px;
    }

    .promotion-card {

        border: none;

        border-radius: 22px;

        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }

    .form-control,
    .form-select {

        border-radius: 12px;

        padding: 12px;
    }

    .form-control:focus,
    .form-select:focus {

        border-color: #16a34a;

        box-shadow: none;
    }

    .checkbox-box {

        max-height: 220px;

        overflow-y: auto;

        border: 1px solid #e5e7eb;

        border-radius: 14px;

        padding: 14px;

        background: #f9fafb;
    }

    .hidden {

        display: none;
    }
</style>

<div class="col-md-10 main-content p-4 promotion-wrapper">

    <div class="promotion-scroll">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="fw-bold mb-1">
                    Thêm khuyến mãi
                </h2>

                <p class="text-muted mb-0">
                    Tạo chương trình khuyến mãi mới
                </p>

            </div>

        </div>

        <div class="card promotion-card">

            <div class="card-body p-4">

                <form method="POST">

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Tên khuyến mãi
                            </label>

                            <input
                                type="text"
                                name="ten_khuyen_mai"
                                class="form-control"
                                required>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Loại khuyến mãi
                            </label>

                            <select
                                name="loai_khuyen_mai"
                                class="form-select"
                                id="promotionType"
                                required>

                                <option value="">
                                    Chọn loại
                                </option>

                                <option value="giam_phan_tram">
                                    Giảm phần trăm
                                </option>

                                <option value="giam_tien_don">
                                    Giảm tiền đơn
                                </option>

                                <option value="free_ship">
                                    Free ship
                                </option>

                            </select>

                        </div>

                        <div class="col-md-6 mb-3" id="valueBox">

                            <label class="form-label">
                                Giá trị giảm
                            </label>

                            <input
                                type="number"
                                name="gia_tri"
                                class="form-control"
                                value="0">

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Đơn hàng tối thiểu
                            </label>

                            <input
                                type="number"
                                name="don_hang_toi_thieu"
                                class="form-control"
                                value="0">

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Áp dụng cho
                            </label>

                            <select
                                name="ap_dung_cho"
                                class="form-select"
                                id="applyType">

                                <option value="tat_ca">
                                    Tất cả
                                </option>

                                <option value="san_pham">
                                    Theo sản phẩm
                                </option>

                                <option value="danh_muc">
                                    Theo danh mục
                                </option>

                            </select>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Ngày bắt đầu
                            </label>

                            <input
                                type="datetime-local"
                                name="ngay_bat_dau"
                                class="form-control"
                                required>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Ngày kết thúc
                            </label>

                            <input
                                type="datetime-local"
                                name="ngay_ket_thuc"
                                class="form-control"
                                required>

                        </div>

                        <!-- SẢN PHẨM -->

                        <div class="col-md-12 mb-3 hidden" id="productBox">

                            <label class="form-label">
                                Chọn sản phẩm
                            </label>

                            <div class="checkbox-box">

                                <?php foreach ($products as $p): ?>

                                    <div class="form-check mb-2">

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="san_pham_ids[]"
                                            value="<?php echo $p['id']; ?>">

                                        <label class="form-check-label">

                                            <?php echo htmlspecialchars($p['ten_san_pham']); ?>

                                        </label>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </div>

                        <!-- DANH MỤC -->

                        <div class="col-md-12 mb-3 hidden" id="categoryBox">

                            <label class="form-label">
                                Chọn danh mục
                            </label>

                            <div class="checkbox-box">

                                <?php foreach ($categories as $cat): ?>

                                    <div class="form-check mb-2">

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="danh_muc_ids[]"
                                            value="<?php echo $cat['id']; ?>">

                                        <label class="form-check-label">

                                            <?php echo htmlspecialchars($cat['ten_danh_muc']); ?>

                                        </label>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </div>

                        <div class="col-md-12 mb-4">

                            <div class="form-check">

                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="trang_thai"
                                    value="1"
                                    checked>

                                <label class="form-check-label">

                                    Kích hoạt khuyến mãi

                                </label>

                            </div>

                        </div>

                        <div class="col-12">

                            <button
                                type="submit"
                                class="btn btn-success px-4">

                                <i class="fas fa-save me-2"></i>

                                Lưu khuyến mãi

                            </button>

                            <a
                                href="list.php"
                                class="btn btn-secondary">

                                Hủy

                            </a>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>

<script>
    const applyType = document.getElementById('applyType');

    const productBox = document.getElementById('productBox');

    const categoryBox = document.getElementById('categoryBox');

    applyType.addEventListener('change', function() {

        productBox.classList.add('hidden');

        categoryBox.classList.add('hidden');

        if (this.value === 'san_pham') {

            productBox.classList.remove('hidden');
        }

        if (this.value === 'danh_muc') {

            categoryBox.classList.remove('hidden');
        }
    });

    const promotionType = document.getElementById('promotionType');

    const valueBox = document.getElementById('valueBox');

    promotionType.addEventListener('change', function() {

        if (this.value === 'free_ship') {

            valueBox.style.display = 'none';

        } else {

            valueBox.style.display = 'block';
        }
    });
</script>

<?php include '../includes/footer.php'; ?>