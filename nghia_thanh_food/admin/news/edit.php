<?php
require_once '../includes/config.php';

$db = getDB();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $db->prepare("
    SELECT * FROM tintuc
    WHERE id = ?
");

$stmt->execute([$id]);

$news = $stmt->fetch();

if (!$news) {
    die('Tin tức không tồn tại');
}

/* UPDATE */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $tieu_de = trim($_POST['tieu_de']);
    $noi_dung = trim($_POST['noi_dung']);
    $hinh_anh = trim($_POST['hinh_anh']);
    $trang_thai = intval($_POST['trang_thai']);

    $stmt = $db->prepare("
        UPDATE tintuc
        SET
            tieu_de = ?,
            noi_dung = ?,
            hinh_anh = ?,
            trang_thai = ?,
            ngay_cap_nhat = CURRENT_TIMESTAMP
        WHERE id = ?
    ");

    session_start();
    $stmt->execute([
        $tieu_de,
        $noi_dung,
        $hinh_anh,
        $trang_thai,
        $id
    ]);

    $_SESSION['success_message'] = 'Cập nhật tin tức thành công!';

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
                        Chỉnh sửa tin tức
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
                                value="<?php echo htmlspecialchars($news['tieu_de']); ?>"
                                required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Tên ảnh
                            </label>

                            <input
                                type="text"
                                name="hinh_anh"
                                class="form-control"
                                value="<?php echo htmlspecialchars($news['hinh_anh']); ?>">

                            <small class="text-muted">
                                Ví dụ: sale.jpg
                            </small>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Nội dung
                            </label>

                            <textarea
                                name="noi_dung"
                                rows="10"
                                class="form-control"
                                required><?php echo htmlspecialchars($news['noi_dung']); ?></textarea>

                        </div>

                        <div class="mb-4">

                            <label class="form-label">
                                Trạng thái
                            </label>

                            <select
                                name="trang_thai"
                                class="form-select">

                                <option
                                    value="1"
                                    <?php echo $news['trang_thai'] == 1 ? 'selected' : ''; ?>>
                                    Hiển thị
                                </option>

                                <option
                                    value="0"
                                    <?php echo $news['trang_thai'] == 0 ? 'selected' : ''; ?>>
                                    Ẩn
                                </option>

                            </select>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-success">

                            <i class="fas fa-save me-2"></i>

                            Cập nhật tin tức

                        </button>

                        <a
                            href="list.php"
                            class="btn btn-secondary">

                            Quay lại

                        </a>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

<?php include '../includes/footer.php'; ?>