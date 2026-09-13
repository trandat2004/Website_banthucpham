<?php
require_once __DIR__ . '/../includes/check_auth.php';

$page_title = 'Sửa sản phẩm';

require_once '../includes/config.php';

include '../includes/header.php';
include '../includes/sidebar.php';

$db = getDB();

$id = intval($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM sanpham WHERE id = ?");
$stmt->execute([$id]);

$product = $stmt->fetch();

if (!$product) {
    redirect('/admin/products/list.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $ten_san_pham = safeInput($_POST['ten_san_pham']);
    $danh_muc_id = intval($_POST['danh_muc_id']) ?: null;

    $gia_ban = floatval($_POST['gia_ban']);

    $gia_khuyen_mai = !empty($_POST['gia_khuyen_mai'])
        ? floatval($_POST['gia_khuyen_mai'])
        : null;

    $so_luong = intval($_POST['so_luong']);

    $don_vi_tinh = safeInput($_POST['don_vi_tinh']);

    $mo_ta_ngan = safeInput($_POST['mo_ta_ngan']);

    $mo_ta_chi_tiet = safeInput($_POST['mo_ta_chi_tiet']);

    $trang_thai = isset($_POST['trang_thai']) ? 1 : 0;

    $san_pham_noi_bat = isset($_POST['san_pham_noi_bat']) ? 1 : 0;

    $san_pham_moi = isset($_POST['san_pham_moi']) ? 1 : 0;

    /*
    |--------------------------------------------------------------------------
    | XỬ LÝ ẢNH
    |--------------------------------------------------------------------------
    */

    $hinh_anh = $product['hinh_anh'] ?? '';

    if (isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] == 0) {

        $upload_dir = BASE_PATH . '/public/assets/images/products/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_tmp  = $_FILES['hinh_anh']['tmp_name'];

        $file_name = time() . '_' . basename($_FILES['hinh_anh']['name']);

        $target_file = $upload_dir . $file_name;

        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allow_types = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($imageFileType, $allow_types)) {

            if (move_uploaded_file($file_tmp, $target_file)) {

                /*
                |--------------------------------------------------------------------------
                | XÓA ẢNH CŨ
                |--------------------------------------------------------------------------
                */

                if (!empty($product['hinh_anh'])) {

                    $old_image = $upload_dir . $product['hinh_anh'];

                    if (file_exists($old_image)) {
                        @unlink($old_image);
                    }
                }

                $hinh_anh = $file_name;
            }
        } else {

            $error = 'Chỉ cho phép ảnh JPG, JPEG, PNG, WEBP';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CẬP NHẬT SẢN PHẨM
    |--------------------------------------------------------------------------
    */

    if (empty($error)) {

        $stmt = $db->prepare("
            UPDATE sanpham SET
                ten_san_pham = ?,
                danh_muc_id = ?,
                gia_ban = ?,
                gia_khuyen_mai = ?,
                so_luong = ?,
                don_vi_tinh = ?,
                mo_ta_ngan = ?,
                mo_ta_chi_tiet = ?,
                hinh_anh = ?,
                trang_thai = ?,
                san_pham_noi_bat = ?,
                san_pham_moi = ?,
                ngay_cap_nhat = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $update = $stmt->execute([
            $ten_san_pham,
            $danh_muc_id,
            $gia_ban,
            $gia_khuyen_mai,
            $so_luong,
            $don_vi_tinh,
            $mo_ta_ngan,
            $mo_ta_chi_tiet,
            $hinh_anh,
            $trang_thai,
            $san_pham_noi_bat,
            $san_pham_moi,
            $id
        ]);

        if ($update) {

            $_SESSION['success_message'] = 'Cập nhật sản phẩm thành công';

            redirect('/admin/products/list.php');
        } else {

            $error = 'Có lỗi xảy ra khi cập nhật sản phẩm';
        }
    }
}

$stmt = $db->query("
    SELECT *
    FROM danhmuc
    WHERE trang_thai = 1
");

$categories = $stmt->fetchAll();
?>

<style>
    .main-content-scroll {

        height: calc(100vh - 70px);

        overflow-y: auto;

        padding-bottom: 50px;
    }

    .main-content-scroll::-webkit-scrollbar {

        width: 8px;
    }

    .main-content-scroll::-webkit-scrollbar-thumb {

        background: #cbd5e1;

        border-radius: 20px;
    }

    .image-preview {

        width: 120px;

        height: 120px;

        object-fit: cover;

        border-radius: 12px;

        border: 1px solid #ddd;

        padding: 4px;

        background: #fff;
    }
</style>

<div class="col-md-10 main-content main-content-scroll p-4">

    <h2 class="mb-4">

        Sửa sản phẩm:
        <?php echo htmlspecialchars($product['ten_san_pham']); ?>

    </h2>

    <?php if (!empty($error)): ?>

        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>

    <?php endif; ?>

    <div class="card shadow-sm border-0">

        <div class="card-body">

            <form method="POST" enctype="multipart/form-data">

                <div class="row">

                    <!-- MÃ SẢN PHẨM -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Mã sản phẩm
                        </label>

                        <input
                            type="text"
                            class="form-control bg-light"
                            value="<?php echo htmlspecialchars($product['ma_san_pham']); ?>"
                            readonly>

                        <small class="text-muted">
                            Không thể chỉnh sửa mã sản phẩm
                        </small>

                    </div>

                    <!-- TÊN -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Tên sản phẩm
                        </label>

                        <input
                            type="text"
                            name="ten_san_pham"
                            class="form-control"
                            value="<?php echo htmlspecialchars($product['ten_san_pham']); ?>"
                            required>

                    </div>

                    <!-- DANH MỤC -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Danh mục
                        </label>

                        <select
                            name="danh_muc_id"
                            class="form-select">

                            <option value="">
                                Chọn danh mục
                            </option>

                            <?php foreach ($categories as $cat): ?>

                                <option
                                    value="<?php echo $cat['id']; ?>"
                                    <?php echo $product['danh_muc_id'] == $cat['id'] ? 'selected' : ''; ?>>

                                    <?php echo htmlspecialchars($cat['ten_danh_muc']); ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <!-- ĐƠN VỊ -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Đơn vị tính
                        </label>

                        <input
                            type="text"
                            name="don_vi_tinh"
                            class="form-control"
                            value="<?php echo htmlspecialchars($product['don_vi_tinh']); ?>">

                    </div>

                    <!-- GIÁ -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Giá bán
                        </label>

                        <input
                            type="number"
                            name="gia_ban"
                            class="form-control"
                            value="<?php echo intval($product['gia_ban']); ?>"
                            required>

                    </div>

                    <!-- GIÁ KM -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Giá khuyến mãi
                        </label>

                        <input
                            type="number"
                            name="gia_khuyen_mai"
                            class="form-control"
                            value="<?php echo intval($product['gia_khuyen_mai']); ?>">

                    </div>

                    <!-- SỐ LƯỢNG -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Số lượng tồn kho
                        </label>

                        <input
                            type="number"
                            name="so_luong"
                            class="form-control"
                            value="<?php echo intval($product['so_luong']); ?>">

                    </div>

                    <!-- ẢNH -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Hình ảnh sản phẩm
                        </label>

                        <input
                            type="file"
                            name="hinh_anh"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.webp">

                        <small class="text-muted">
                            Chọn ảnh JPG, PNG, WEBP
                        </small>

                    </div>

                    <!-- ẢNH HIỆN TẠI -->

                    <?php if (!empty($product['hinh_anh'])): ?>

                        <div class="col-md-12 mb-3">

                            <label class="form-label d-block">
                                Ảnh hiện tại
                            </label>

                            <img
                                src="<?php echo BASE_URL; ?>/public/assets/images/products/<?php echo $product['hinh_anh']; ?>"
                                class="image-preview">

                        </div>

                    <?php endif; ?>

                    <!-- MÔ TẢ NGẮN -->

                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Mô tả ngắn
                        </label>

                        <textarea
                            name="mo_ta_ngan"
                            class="form-control"
                            rows="2"><?php echo htmlspecialchars($product['mo_ta_ngan']); ?></textarea>

                    </div>

                    <!-- MÔ TẢ CHI TIẾT -->

                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Mô tả chi tiết
                        </label>

                        <textarea
                            name="mo_ta_chi_tiet"
                            class="form-control"
                            rows="6"><?php echo htmlspecialchars($product['mo_ta_chi_tiet']); ?></textarea>

                    </div>

                    <!-- CHECKBOX -->

                    <div class="col-md-12 mb-4">

                        <div class="form-check form-check-inline">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="trang_thai"
                                value="1"
                                <?php echo $product['trang_thai'] ? 'checked' : ''; ?>>

                            <label class="form-check-label">
                                Hiển thị
                            </label>

                        </div>

                        <div class="form-check form-check-inline">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="san_pham_noi_bat"
                                value="1"
                                <?php echo $product['san_pham_noi_bat'] ? 'checked' : ''; ?>>

                            <label class="form-check-label">
                                Sản phẩm nổi bật
                            </label>

                        </div>

                        <div class="form-check form-check-inline">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="san_pham_moi"
                                value="1"
                                <?php echo $product['san_pham_moi'] ? 'checked' : ''; ?>>

                            <label class="form-check-label">
                                Sản phẩm mới
                            </label>

                        </div>

                    </div>

                    <!-- BUTTON -->

                    <div class="col-12">

                        <button
                            type="submit"
                            class="btn btn-success">

                            <i class="fas fa-save me-2"></i>

                            Cập nhật sản phẩm

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

<?php include '../includes/footer.php'; ?>