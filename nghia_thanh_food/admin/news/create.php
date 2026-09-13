<?php
    require_once '../includes/config.php';

    $db = getDB();

    /* THÊM TIN */

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $tieu_de    = trim($_POST['tieu_de']);
    $noi_dung   = trim($_POST['noi_dung']);
    $hinh_anh   = trim($_POST['hinh_anh']);
    $trang_thai = intval($_POST['trang_thai']);

    /* tạo slug */

    $tieu_de_khong_dau = strtolower($tieu_de);

    $tieu_de_khong_dau = preg_replace(
        '/[^a-zA-Z0-9]+/',
        '-',
        $tieu_de_khong_dau
    );

    date_default_timezone_set('Asia/Ho_Chi_Minh');

    $now = date('Y-m-d H:i:s');

    $stmt = $db->prepare("
        INSERT INTO tintuc (
            tieu_de,
            tieu_de_khong_dau,
            noi_dung,
            hinh_anh,
            trang_thai,
            ngay_dang,
            ngay_cap_nhat
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    session_start();

    $stmt->execute([
        $tieu_de,
        $tieu_de_khong_dau,
        $noi_dung,
        $hinh_anh,
        $trang_thai,
        $now,
        $now,
    ]);

    // Lấy ID tin tức vừa thêm
    $news_id = $db->lastInsertId();

    // =============================================
    // GỬI THÔNG BÁO TIN TỨC MỚI CHO TẤT CẢ KHÁCH HÀNG
    // =============================================

    // Tạo link đến chi tiết tin tức (trang user)
    $news_link = BASE_URL . '/public/pages/news.php?id=' . $news_id;

    // Gửi thông báo đến tất cả user (không phải admin)
    sendNotificationToAllUsers(
        $db,
        'tin_tuc', // loai
        'tao_moi', // hanh_dong
        $news_id,  // tham_chieu_id
        '📰 Tin tức mới: ' . $tieu_de,
        'Bài viết "' . $tieu_de . '" vừa được đăng tải. Mời bạn đọc ngay!',
        $news_link, // link
        $hinh_anh
    );

    $_SESSION['news_success'] = 'Thêm tin tức thành công!';

    header('Location: list.php');
    exit;
    }

    include '../includes/header.php';
?>

<div class="container-fluid">

    <div class="row">

        <?php include '../includes/sidebar.php'; ?>

        <div class="col-md-10 p-4">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <h2 class="fw-bold mb-4">
                        Thêm tin tức mới
                    </h2>

                    <form method="POST">

                        <div class="mb-3">

                            <label class="form-label">
                                Tiêu đề
                            </label>

                            <input
                                type="text"
                                name="tieu_de"
                                class="form-control"
                                required
                            >

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Tên ảnh
                            </label>

                            <input
                                type="text"
                                name="hinh_anh"
                                class="form-control"
                                placeholder="Ví dụ: sale.jpg"
                            >

                            <small class="text-muted">
                                Ảnh lưu trong:
                                public/assets/images/news/
                            </small>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Nội dung
                            </label>

                            <textarea
                                name="noi_dung"
                                rows="12"
                                class="form-control"
                                required
                            ></textarea>

                        </div>

                        <div class="mb-4">

                            <label class="form-label">
                                Trạng thái
                            </label>

                            <select
                                name="trang_thai"
                                class="form-select"
                            >

                                <option value="1">
                                    Hiển thị
                                </option>

                                <option value="0">
                                    Ẩn
                                </option>

                            </select>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-success"
                        >

                            <i class="fas fa-save me-2"></i>

                            Thêm tin tức

                        </button>

                        <a
                            href="list.php"
                            class="btn btn-secondary"
                        >

                            Quay lại

                        </a>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

<?php include '../includes/footer.php'; ?>