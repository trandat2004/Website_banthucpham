<?php
    require_once __DIR__ . '/../includes/check_auth.php';

    $page_title = 'Thêm sản phẩm';

    require_once '../includes/config.php';

    include '../includes/header.php';
    include '../includes/sidebar.php';

    $db = getDB();

    $error = '';

    /* DANH MỤC */

    $stmt = $db->query("
    SELECT *
    FROM danhmuc
    WHERE trang_thai = 1
");

    $categories = $stmt->fetchAll();

    /* THÊM SẢN PHẨM */

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $ma_san_pham = safeInput($_POST['ma_san_pham']);

    $ten_san_pham = safeInput($_POST['ten_san_pham']);

    $danh_muc_id = intval($_POST['danh_muc_id']) ?: null;

    $gia_ban = floatval($_POST['gia_ban']);

    $gia_khuyen_mai = ! empty($_POST['gia_khuyen_mai'])
        ? floatval($_POST['gia_khuyen_mai'])
        : null;

    $so_luong = intval($_POST['so_luong']);

    $don_vi_tinh = safeInput($_POST['don_vi_tinh']);

    $mo_ta_ngan = safeInput($_POST['mo_ta_ngan']);

    $mo_ta_chi_tiet = safeInput($_POST['mo_ta_chi_tiet']);

    $trang_thai = isset($_POST['trang_thai']) ? 1 : 0;

    $san_pham_noi_bat = isset($_POST['san_pham_noi_bat']) ? 1 : 0;

    $san_pham_moi = isset($_POST['san_pham_moi']) ? 1 : 0;

    $hinh_anh = '';

    /* KIỂM TRA MÃ SP */

    $check = $db->prepare("
        SELECT id
        FROM sanpham
        WHERE ma_san_pham = ?
    ");

    $check->execute([$ma_san_pham]);

    if ($check->fetch()) {

        $error = 'Mã sản phẩm đã tồn tại. Vui lòng nhập mã khác!';
    } else {

        /* UPLOAD ẢNH */

        if (
            isset($_FILES['hinh_anh']) &&
            $_FILES['hinh_anh']['error'] == 0
        ) {

            $file_tmp = $_FILES['hinh_anh']['tmp_name'];

            $file_name = time() . '_' . basename($_FILES['hinh_anh']['name']);

            $upload_path = BASE_PATH . '/public/assets/images/products/' . $file_name;

            move_uploaded_file($file_tmp, $upload_path);

            $hinh_anh = $file_name;
        }

        /* INSERT */

        $stmt = $db->prepare("
            INSERT INTO sanpham
            (
                ma_san_pham,
                ten_san_pham,
                danh_muc_id,
                gia_ban,
                gia_khuyen_mai,
                so_luong,
                don_vi_tinh,
                mo_ta_ngan,
                mo_ta_chi_tiet,
                hinh_anh,
                trang_thai,
                san_pham_noi_bat,
                san_pham_moi
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $ma_san_pham,
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
        ]);

        // Lấy ID sản phẩm vừa thêm
        $product_id = $db->lastInsertId();

        // =============================================
        // GỬI THÔNG BÁO SẢN PHẨM MỚI CHO TẤT CẢ KHÁCH HÀNG
        // =============================================

        // Tạo link đến chi tiết sản phẩm (trang user)
        $product_link = BASE_URL . '/public/pages/product-detail.php?id=' . $product_id;

        // Gửi thông báo đến tất cả user
        sendNotificationToAllUsers(
            $db,
            'san_pham',
            'tao_moi',
            $product_id,
            '🆕 Sản phẩm mới: ' . $ten_san_pham,
            'Sản phẩm "' . $ten_san_pham . '" vừa được cập nhật. Hãy khám phá ngay!',
            $product_link,
            $hinh_anh
        );

        $_SESSION['success_message'] = 'Thêm sản phẩm thành công!';

        redirect('/admin/products/list.php');
    }
    }

?>

<style>
    .product-wrapper {

        height: calc(100vh - 70px);

        overflow: hidden;
    }

    .product-scroll {

        height: calc(100vh - 120px);

        overflow-y: auto;

        padding-right: 6px;
    }

    .product-scroll::-webkit-scrollbar {

        width: 8px;
    }

    .product-scroll::-webkit-scrollbar-thumb {

        background: #d1d5db;

        border-radius: 20px;
    }

    .product-card {

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

    .upload-box {

        border: 2px dashed #d1d5db;

        border-radius: 18px;

        padding: 25px;

        text-align: center;

        background: #f9fafb;
    }

    .upload-box i {

        font-size: 42px;

        color: #9ca3af;

        margin-bottom: 10px;
    }
</style>

<div class="col-md-10 main-content p-4 product-wrapper">

    <div class="product-scroll">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="fw-bold mb-1">
                    Thêm sản phẩm mới
                </h2>

                <p class="text-muted mb-0">
                    Tạo sản phẩm mới cho hệ thống
                </p>

            </div>

        </div>

        <?php if (! empty($error)): ?>

            <div class="alert alert-danger">

                <?php echo $error; ?>

            </div>

        <?php endif; ?>

        <div class="card product-card">

            <div class="card-body p-4">

                <form
                    method="POST"
                    enctype="multipart/form-data">

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Mã sản phẩm
                            </label>

                            <input
                                type="text"
                                name="ma_san_pham"
                                class="form-control"
                                required
                                value="<?php echo $_POST['ma_san_pham'] ?? ''; ?>">

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Tên sản phẩm
                            </label>

                            <input
                                type="text"
                                name="ten_san_pham"
                                class="form-control"
                                required
                                value="<?php echo $_POST['ten_san_pham'] ?? ''; ?>">

                        </div>

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
                                        <?php
                                            if (
                                                isset($_POST['danh_muc_id']) &&
                                                $_POST['danh_muc_id'] == $cat['id']
                                            ) {
                                                echo 'selected';
                                            }

                                        ?>>

                                        <?php echo htmlspecialchars($cat['ten_danh_muc']); ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Đơn vị tính
                            </label>

                            <input
                                type="text"
                                name="don_vi_tinh"
                                class="form-control"
                                value="<?php echo $_POST['don_vi_tinh'] ?? 'kg'; ?>">

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Giá bán
                            </label>

                            <input
                                type="number"
                                name="gia_ban"
                                class="form-control"
                                required
                                value="<?php echo $_POST['gia_ban'] ?? ''; ?>">

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Giá khuyến mãi
                            </label>

                            <input
                                type="number"
                                name="gia_khuyen_mai"
                                class="form-control"
                                value="<?php echo $_POST['gia_khuyen_mai'] ?? ''; ?>">

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Số lượng tồn kho
                            </label>

                            <input
                                type="number"
                                name="so_luong"
                                class="form-control"
                                value="<?php echo $_POST['so_luong'] ?? '0'; ?>">

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Ảnh sản phẩm
                            </label>

                            <div class="upload-box">

                                <i class="fas fa-cloud-upload-alt"></i>

                                <div class="mb-2">

                                    Chọn ảnh sản phẩm

                                </div>

                                <input
                                    type="file"
                                    name="hinh_anh"
                                    class="form-control"
                                    accept="image/*">

                            </div>

                        </div>

                        <div class="col-md-12 mb-3">

                            <label class="form-label">
                                Mô tả ngắn
                            </label>

                            <textarea
                                name="mo_ta_ngan"
                                class="form-control"
                                rows="2"><?php echo $_POST['mo_ta_ngan'] ?? ''; ?></textarea>

                        </div>

                        <div class="col-md-12 mb-3">

                            <label class="form-label">
                                Mô tả chi tiết
                            </label>

                            <textarea
                                name="mo_ta_chi_tiet"
                                class="form-control"
                                rows="5"><?php echo $_POST['mo_ta_chi_tiet'] ?? ''; ?></textarea>

                        </div>

                        <div class="col-md-12 mb-4">

                            <div class="form-check form-check-inline">

                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="trang_thai"
                                    value="1"
                                    checked>

                                <label class="form-check-label">

                                    Hiển thị

                                </label>

                            </div>

                            <div class="form-check form-check-inline">

                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="san_pham_noi_bat"
                                    value="1">

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
                                    checked>

                                <label class="form-check-label">

                                    Sản phẩm mới

                                </label>

                            </div>

                        </div>

                        <div class="col-12">

                            <button
                                type="submit"
                                class="btn btn-success px-4">

                                <i class="fas fa-save me-2"></i>

                                Lưu sản phẩm

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

<?php include '../includes/footer.php'; ?>