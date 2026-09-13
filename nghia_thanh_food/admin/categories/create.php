<?php
    require_once __DIR__ . '/../includes/check_auth.php';

    $page_title = 'Thêm danh mục';

    require_once '../includes/config.php';

    include '../includes/header.php';
    include '../includes/sidebar.php';

    $db = getDB();

    $error = '';

    /*
|--------------------------------------------------------------------------
| THÊM DANH MỤC
|--------------------------------------------------------------------------
*/

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $ten_danh_muc = safeInput($_POST['ten_danh_muc']);

    $mo_ta = safeInput($_POST['mo_ta']);

    $trang_thai = isset($_POST['trang_thai']) ? 1 : 0;

    /*
    |--------------------------------------------------------------------------
    | CHECK TRÙNG TÊN DANH MỤC
    |--------------------------------------------------------------------------
    */

    $check = $db->prepare("
        SELECT id
        FROM danhmuc
        WHERE ten_danh_muc = ?
    ");

    $check->execute([$ten_danh_muc]);

    if ($check->fetch()) {

        $error = 'Tên danh mục đã tồn tại';
    } else {

        /*
        |--------------------------------------------------------------------------
        | XỬ LÝ ẢNH / ICON
        |--------------------------------------------------------------------------
        */

        $hinh_anh = '';

        if (isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] == 0) {

            $upload_dir = BASE_PATH . '/public/assets/images/categories/';

            if (! is_dir($upload_dir)) {

                mkdir($upload_dir, 0777, true);
            }

            $file_tmp = $_FILES['hinh_anh']['tmp_name'];

            $file_name = time() . '_' . basename($_FILES['hinh_anh']['name']);

            $target_file = $upload_dir . $file_name;

            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            $allow_types = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($imageFileType, $allow_types)) {

                if (move_uploaded_file($file_tmp, $target_file)) {

                    $hinh_anh = $file_name;
                }
            } else {

                $error = 'Chỉ hỗ trợ JPG, PNG, WEBP';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | INSERT
        |--------------------------------------------------------------------------
        */

        if (empty($error)) {

            $stmt = $db->prepare("
                INSERT INTO danhmuc(
                    ten_danh_muc,
                    mo_ta,
                    hinh_anh,
                    trang_thai
                )
                VALUES (?, ?, ?, ?)
            ");

            $insert = $stmt->execute([
                $ten_danh_muc,
                $mo_ta,
                $hinh_anh,
                $trang_thai,
            ]);

            if ($insert) {

                // Lấy ID danh mục vừa thêm
                $category_id = $db->lastInsertId();

                // =============================================
                // GỬI THÔNG BÁO DANH MỤC MỚI CHO TẤT CẢ KHÁCH HÀNG
                // =============================================

                // Tạo link đến trang sản phẩm với danh mục tương ứng
                $category_link = BASE_URL . '/public/pages/products.php?danh_muc_id=' . $category_id;

                // Gửi thông báo đến tất cả user (không phải admin)
                sendNotificationToAllUsers(
                    $db,
                    'danh_muc',   // loai
                    'tao_moi',    // hanh_dong
                    $category_id, // tham_chieu_id
                    '🏷️ Danh mục mới: ' . $ten_danh_muc,
                    'Danh mục "' . $ten_danh_muc . '" vừa được thêm vào hệ thống. Khám phá sản phẩm ngay!',
                    $category_link, // link
                    $hinh_anh
                );

                $_SESSION['category_success'] = 'Thêm danh mục thành công';

                redirect('/admin/categories/list.php');
            } else {

                $error = 'Có lỗi xảy ra khi thêm danh mục';
            }
        }
    }
    }
?>

<style>
    .main-content-scroll {

        height: calc(100vh - 70px);

        overflow-y: auto;

        padding-bottom: 60px;
    }

    .main-content-scroll::-webkit-scrollbar {

        width: 8px;
    }

    .main-content-scroll::-webkit-scrollbar-thumb {

        background: #cbd5e1;

        border-radius: 20px;
    }

    /*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

    .page-title {

        font-size: 30px;

        font-weight: 700;

        color: #111827;

        margin-bottom: 4px;
    }

    .page-subtitle {

        color: #6b7280;

        font-size: 15px;
    }

    /*
|--------------------------------------------------------------------------
| CARD
|--------------------------------------------------------------------------
*/

    .category-card {

        border: none;

        border-radius: 26px;

        overflow: hidden;

        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.06);
    }

    /*
|--------------------------------------------------------------------------
| FORM
|--------------------------------------------------------------------------
*/

    .form-label {

        font-weight: 600;

        color: #374151;

        margin-bottom: 8px;
    }

    .form-control,
    .form-select {

        border-radius: 14px;

        padding: 13px 15px;

        border: 1px solid #dbe3ea;
    }

    .form-control:focus,
    .form-select:focus {

        box-shadow: none;

        border-color: #198754;
    }

    /*
|--------------------------------------------------------------------------
| IMAGE UPLOAD
|--------------------------------------------------------------------------
*/

    .upload-box {

        border: 2px dashed #d1d5db;

        border-radius: 22px;

        padding: 35px;

        background: #f9fafb;

        text-align: center;

        transition: .25s;
    }

    .upload-box:hover {

        border-color: #198754;

        background: #f0fdf4;
    }

    .upload-icon {

        font-size: 52px;

        color: #9ca3af;

        margin-bottom: 15px;
    }

    .upload-title {

        font-size: 18px;

        font-weight: 700;

        color: #374151;

        margin-bottom: 8px;
    }

    .upload-subtitle {

        font-size: 14px;

        color: #6b7280;

        margin-bottom: 20px;
    }

    /*
|--------------------------------------------------------------------------
| IMAGE PREVIEW
|--------------------------------------------------------------------------
*/

    .preview-image {

        width: 100%;

        height: 260px;

        object-fit: cover;

        border-radius: 18px;

        border: 1px solid #e5e7eb;

        padding: 5px;

        background: #fff;
    }

    /*
|--------------------------------------------------------------------------
| BUTTON
|--------------------------------------------------------------------------
*/

    .save-btn {

        border-radius: 14px;

        padding: 12px 22px;

        font-weight: 600;
    }

    .cancel-btn {

        border-radius: 14px;

        padding: 12px 22px;

        font-weight: 600;
    }

    .form-check-input:checked {

        background-color: #198754;

        border-color: #198754;
    }
</style>

<div class="col-md-10 main-content main-content-scroll p-4">

    <!-- HEADER -->

    <div class="mb-4">

        <div class="page-title">

            Thêm danh mục mới

        </div>

        <div class="page-subtitle">

            Tạo danh mục và tải icon / hình ảnh hiển thị

        </div>

    </div>

    <!-- ERROR -->

    <?php if (! empty($error)): ?>

        <div class="alert alert-danger rounded-4">

            <?php echo $error; ?>

        </div>

    <?php endif; ?>

    <!-- CARD -->

    <div class="card category-card">

        <div class="card-body p-4">

            <form method="POST" enctype="multipart/form-data">

                <div class="row">

                    <!-- LEFT -->

                    <div class="col-lg-8">

                        <!-- TÊN -->

                        <div class="mb-4">

                            <label class="form-label">

                                Tên danh mục

                            </label>

                            <input
                                type="text"
                                name="ten_danh_muc"
                                class="form-control"
                                placeholder="Ví dụ: Rau củ sạch"
                                value="<?php echo htmlspecialchars($_POST['ten_danh_muc'] ?? ''); ?>"
                                required>

                        </div>

                        <!-- MÔ TẢ -->

                        <div class="mb-4">

                            <label class="form-label">

                                Mô tả danh mục

                            </label>

                            <textarea
                                name="mo_ta"
                                class="form-control"
                                rows="8"
                                placeholder="Nhập mô tả danh mục..."><?php echo htmlspecialchars($_POST['mo_ta'] ?? ''); ?></textarea>

                        </div>

                        <!-- STATUS -->

                        <div class="mb-4">

                            <div class="form-check">

                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="trang_thai"
                                    value="1"
                                    checked>

                                <label class="form-check-label">

                                    Kích hoạt danh mục

                                </label>

                            </div>

                        </div>

                    </div>

                    <!-- RIGHT -->

                    <div class="col-lg-4">

                        <label class="form-label mb-3">

                            Ảnh / Icon danh mục

                        </label>

                        <div class="upload-box">

                            <div class="upload-icon">

                                <i class="fas fa-cloud-upload-alt"></i>

                            </div>

                            <div class="upload-title">

                                Tải ảnh danh mục

                            </div>

                            <div class="upload-subtitle">

                                JPG, PNG, WEBP

                            </div>

                            <input
                                type="file"
                                name="hinh_anh"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.webp">

                        </div>

                    </div>

                    <!-- BUTTON -->

                    <div class="col-12 mt-4">

                        <button
                            type="submit"
                            class="btn btn-success save-btn">

                            <i class="fas fa-save me-2"></i>

                            Lưu danh mục

                        </button>

                        <a
                            href="list.php"
                            class="btn btn-secondary cancel-btn">

                            Hủy

                        </a>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>

<?php include '../includes/footer.php'; ?>