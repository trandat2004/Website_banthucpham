<?php
require_once __DIR__ . '/../includes/check_auth.php';

$page_title = 'Sửa khuyến mãi';

require_once '../includes/config.php';

include '../includes/header.php';
include '../includes/sidebar.php';

$db = getDB();

$id = intval($_GET['id'] ?? 0);

if (!$id) {

    redirect('/admin/promotions/list.php');
}

/*
|---------------------------------------------------
| LOAD KHUYẾN MÃI
|---------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT *
    FROM khuyenmai
    WHERE id = ?
");

$stmt->execute([$id]);

$promotion = $stmt->fetch();

if (!$promotion) {

    redirect('/admin/promotions/list.php');
}

/*
|---------------------------------------------------
| LOAD DANH MỤC
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
| LOAD SẢN PHẨM
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
| DANH SÁCH ĐÃ CHỌN
|---------------------------------------------------
*/

$selected_ids = [];

if (!empty($promotion['danh_sach_ap_dung'])) {

    $selected_ids = explode(',', $promotion['danh_sach_ap_dung']);
}

/*
|---------------------------------------------------
| UPDATE
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
    | FORMAT GIỜ VIỆT NAM
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
    | VALIDATE
    |---------------------------------------------------
    */

    if ($ngay_ket_thuc <= $ngay_bat_dau) {

        $error = 'Ngày kết thúc phải lớn hơn ngày bắt đầu!';
    } else {

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
        | UPDATE DB
        |---------------------------------------------------
        */

        $update_stmt = $db->prepare("
            UPDATE khuyenmai
            SET
                ten_khuyen_mai = ?,
                loai_khuyen_mai = ?,
                gia_tri = ?,
                don_hang_toi_thieu = ?,
                ap_dung_cho = ?,
                danh_sach_ap_dung = ?,
                ngay_bat_dau = ?,
                ngay_ket_thuc = ?,
                trang_thai = ?
            WHERE id = ?
        ");

        $update_stmt->execute([
            $ten_khuyen_mai,
            $loai_khuyen_mai,
            $gia_tri,
            $don_hang_toi_thieu,
            $ap_dung_cho,
            $danh_sach_ap_dung,
            $ngay_bat_dau,
            $ngay_ket_thuc,
            $trang_thai,
            $id
        ]);

        $_SESSION['success_message'] = 'Đã cập nhật khuyến mãi!';

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
                    Sửa khuyến mãi
                </h2>

                <p class="text-muted mb-0">
                    Cập nhật chương trình khuyến mãi
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
                                required
                                value="<?php echo htmlspecialchars($promotion['ten_khuyen_mai']); ?>">

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

                                <option value="giam_phan_tram"
                                    <?php if ($promotion['loai_khuyen_mai'] == 'giam_phan_tram') echo 'selected'; ?>>
                                    Giảm phần trăm
                                </option>

                                <option value="giam_tien_don"
                                    <?php if ($promotion['loai_khuyen_mai'] == 'giam_tien_don') echo 'selected'; ?>>
                                    Giảm tiền đơn
                                </option>

                                <option value="free_ship"
                                    <?php if ($promotion['loai_khuyen_mai'] == 'free_ship') echo 'selected'; ?>>
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
                                value="<?php echo $promotion['gia_tri']; ?>">

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Đơn hàng tối thiểu
                            </label>

                            <input
                                type="number"
                                name="don_hang_toi_thieu"
                                class="form-control"
                                value="<?php echo $promotion['don_hang_toi_thieu']; ?>">

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Áp dụng cho
                            </label>

                            <select
                                name="ap_dung_cho"
                                class="form-select"
                                id="applyType">

                                <option value="tat_ca"
                                    <?php if ($promotion['ap_dung_cho'] == 'tat_ca') echo 'selected'; ?>>
                                    Tất cả
                                </option>

                                <option value="san_pham"
                                    <?php if ($promotion['ap_dung_cho'] == 'san_pham') echo 'selected'; ?>>
                                    Theo sản phẩm
                                </option>

                                <option value="danh_muc"
                                    <?php if ($promotion['ap_dung_cho'] == 'danh_muc') echo 'selected'; ?>>
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
                                required
                                value="<?php echo date('Y-m-d\TH:i', strtotime($promotion['ngay_bat_dau'])); ?>">

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Ngày kết thúc
                            </label>

                            <input
                                type="datetime-local"
                                name="ngay_ket_thuc"
                                class="form-control"
                                required
                                value="<?php echo date('Y-m-d\TH:i', strtotime($promotion['ngay_ket_thuc'])); ?>">

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
                                            value="<?php echo $p['id']; ?>"
                                            <?php if (in_array($p['id'], $selected_ids)) echo 'checked'; ?>>

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
                                            value="<?php echo $cat['id']; ?>"
                                            <?php if (in_array($cat['id'], $selected_ids)) echo 'checked'; ?>>

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
                                    <?php if ($promotion['trang_thai']) echo 'checked'; ?>>

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

                                Cập nhật

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

    function toggleApplyBox() {

        productBox.classList.add('hidden');

        categoryBox.classList.add('hidden');

        if (applyType.value === 'san_pham') {

            productBox.classList.remove('hidden');
        }

        if (applyType.value === 'danh_muc') {

            categoryBox.classList.remove('hidden');
        }
    }

    toggleApplyBox();

    applyType.addEventListener('change', toggleApplyBox);

    const promotionType = document.getElementById('promotionType');

    const valueBox = document.getElementById('valueBox');

    function toggleValueBox() {

        if (promotionType.value === 'free_ship') {

            valueBox.style.display = 'none';

        } else {

            valueBox.style.display = 'block';
        }
    }

    toggleValueBox();

    promotionType.addEventListener('change', toggleValueBox);
</script>

<?php include '../includes/footer.php'; ?>