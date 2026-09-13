<?php
require_once __DIR__ . '/../includes/check_auth.php';

$page_title = 'Quản lý danh mục';

require_once '../includes/config.php';

include '../includes/header.php';
include '../includes/sidebar.php';

$db = getDB();

/*
|--------------------------------------------------------------------------
| XÓA DANH MỤC
|--------------------------------------------------------------------------
*/

if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $stmt = $db->prepare("
        DELETE FROM danhmuc
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $_SESSION['category_success'] = 'Đã xóa danh mục thành công';

    redirect('/admin/categories/list.php');
}

/*
|--------------------------------------------------------------------------
| LẤY DANH SÁCH DANH MỤC
|--------------------------------------------------------------------------
*/

$stmt = $db->query("
    SELECT *
    FROM danhmuc
    ORDER BY id DESC
");

$categories = $stmt->fetchAll();
?>

<?php if (isset($_SESSION['category_success'])): ?>

    <div class="custom-alert success-alert" id="autoCloseAlert">

        <i class="fas fa-check-circle"></i>

        <span>

            <?php
            echo $_SESSION['category_success'];
            unset($_SESSION['category_success']);
            ?>

        </span>

    </div>

<?php endif; ?>

<style>
    .main-content-scroll {

        height: calc(100vh - 70px);

        overflow: hidden;

        padding: 24px;
    }

    /*
|--------------------------------------------------------------------------
| ALERT SUCCESS
|--------------------------------------------------------------------------
*/

    .custom-alert {

        position: fixed;

        top: 25px;

        right: 25px;

        z-index: 99999;

        min-width: 320px;

        max-width: 420px;

        padding: 16px 22px;

        border-radius: 16px;

        display: flex;

        align-items: center;

        gap: 12px;

        font-weight: 600;

        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);

        animation: slideIn .35s ease;

        transition: all .4s ease;
    }

    .success-alert {

        background: #198754;

        color: #fff;
    }

    .custom-alert i {

        font-size: 22px;
    }

    .custom-alert.hide {

        opacity: 0;

        transform: translateX(120px);
    }

    @keyframes slideIn {

        from {

            opacity: 0;

            transform: translateX(120px);
        }

        to {

            opacity: 1;

            transform: translateX(0);
        }
    }

    /*
|--------------------------------------------------------------------------
| PAGE HEADER
|--------------------------------------------------------------------------
*/

    .page-header {

        display: flex;

        justify-content: space-between;

        align-items: center;

        margin-bottom: 24px;
    }

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

    .add-btn {

        border-radius: 14px;

        padding: 12px 18px;

        font-weight: 600;

        box-shadow: 0 6px 20px rgba(25, 135, 84, 0.18);
    }

    /*
|--------------------------------------------------------------------------
| TABLE WRAPPER
|--------------------------------------------------------------------------
*/

    .category-wrapper {

        background: #fff;

        border-radius: 24px;

        box-shadow: 0 10px 35px rgba(0, 0, 0, 0.06);

        height: calc(100vh - 125px);

        overflow: hidden;

        display: flex;

        flex-direction: column;
    }

    .category-table-scroll {

        flex: 1;

        overflow-y: auto;

        overflow-x: auto;
    }

    .category-table-scroll::-webkit-scrollbar {

        width: 8px;

        height: 8px;
    }

    .category-table-scroll::-webkit-scrollbar-thumb {

        background: #cbd5e1;

        border-radius: 20px;
    }

    /*
|--------------------------------------------------------------------------
| TABLE
|--------------------------------------------------------------------------
*/

    .custom-table {

        width: 100%;

        min-width: 1200px;

        margin-bottom: 0;
    }

    .custom-table thead {

        position: sticky;

        top: 0;

        z-index: 10;
    }

    .custom-table thead th {

        background: #198754;

        color: #fff;

        padding: 18px 16px;

        border: none;

        font-size: 14px;

        white-space: nowrap;
    }

    .custom-table tbody td {

        padding: 18px 16px;

        vertical-align: middle;

        border-color: #f1f5f9;
    }

    .custom-table tbody tr {

        transition: .2s;
    }

    .custom-table tbody tr:hover {

        background: #f8fafc;
    }

    /*
|--------------------------------------------------------------------------
| IMAGE
|--------------------------------------------------------------------------
*/

    .category-image {

        width: 70px;

        height: 70px;

        border-radius: 16px;

        object-fit: cover;

        border: 1px solid #e5e7eb;

        background: #fff;

        padding: 4px;
    }

    .no-image {

        width: 70px;

        height: 70px;

        border-radius: 16px;

        background: #f3f4f6;

        display: flex;

        align-items: center;

        justify-content: center;

        color: #9ca3af;

        font-size: 24px;
    }

    /*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

    .status-badge {

        padding: 8px 14px;

        border-radius: 999px;

        font-size: 13px;

        font-weight: 600;
    }

    .status-active {

        background: #dcfce7;

        color: #166534;
    }

    .status-inactive {

        background: #fee2e2;

        color: #991b1b;
    }

    /*
|--------------------------------------------------------------------------
| ACTION
|--------------------------------------------------------------------------
*/

    .action-btn {

        width: 40px;

        height: 40px;

        border-radius: 12px;

        display: inline-flex;

        align-items: center;

        justify-content: center;

        text-decoration: none;

        transition: .2s;

        margin-right: 6px;
    }

    .action-btn:hover {

        transform: translateY(-2px);
    }

    .btn-edit {

        background: #facc15;

        color: #111827;
    }

    .btn-delete {

        background: #ef4444;

        color: #fff;
    }

    /*
|--------------------------------------------------------------------------
| EMPTY
|--------------------------------------------------------------------------
*/

    .empty-box {

        text-align: center;

        padding: 80px 20px;

        color: #6b7280;
    }

    .empty-box i {

        font-size: 60px;

        margin-bottom: 15px;

        color: #d1d5db;
    }
</style>

<div class="col-md-10 main-content main-content-scroll">

    <!-- HEADER -->

    <div class="page-header">

        <div>

            <div class="page-title">

                Quản lý danh mục

            </div>

            <div class="page-subtitle">

                Danh sách danh mục sản phẩm trong hệ thống

            </div>

        </div>

        <a
            href="create.php"
            class="btn btn-success add-btn">

            <i class="fas fa-plus me-2"></i>

            Thêm danh mục

        </a>

    </div>

    <!-- TABLE -->

    <div class="category-wrapper">

        <div class="category-table-scroll">

            <table class="table custom-table align-middle">

                <thead>

                    <tr>

                        <th width="90">

                            ID

                        </th>

                        <th width="130">

                            Ảnh/Icon

                        </th>

                        <th width="300">

                            Tên danh mục

                        </th>

                        <th>

                            Mô tả

                        </th>

                        <th width="180">

                            Trạng thái

                        </th>

                        <th width="180" class="text-center">

                            Thao tác

                        </th>

                    </tr>

                </thead>

                <tbody>

                    <?php if (count($categories) > 0): ?>

                        <?php foreach ($categories as $cat): ?>

                            <tr>

                                <td>

                                    <strong>

                                        #<?php echo $cat['id']; ?>

                                    </strong>

                                </td>

                                <td>

                                    <?php if (!empty($cat['hinh_anh'])): ?>

                                        <img
                                            src="<?php echo BASE_URL; ?>/public/assets/images/categories/<?php echo $cat['hinh_anh']; ?>"
                                            class="category-image">

                                    <?php else: ?>

                                        <div class="no-image">

                                            <i class="fas fa-image"></i>

                                        </div>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <div class="fw-bold text-dark fs-6">

                                        <?php echo htmlspecialchars($cat['ten_danh_muc']); ?>

                                    </div>

                                </td>

                                <td>

                                    <?php if (!empty($cat['mo_ta'])): ?>

                                        <?php echo htmlspecialchars($cat['mo_ta']); ?>

                                    <?php else: ?>

                                        <span class="text-muted">

                                            Không có mô tả

                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php if ($cat['trang_thai']): ?>

                                        <span class="status-badge status-active">

                                            Hoạt động

                                        </span>

                                    <?php else: ?>

                                        <span class="status-badge status-inactive">

                                            Tạm dừng

                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td class="text-center">

                                    <a
                                        href="edit.php?id=<?php echo $cat['id']; ?>"
                                        class="action-btn btn-edit"
                                        title="Chỉnh sửa">

                                        <i class="fas fa-edit"></i>

                                    </a>

                                    <a
                                        href="?delete=<?php echo $cat['id']; ?>"
                                        class="action-btn btn-delete"
                                        onclick="return confirm('Bạn có chắc muốn xóa danh mục này?')"
                                        title="Xóa">

                                        <i class="fas fa-trash"></i>

                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="6">

                                <div class="empty-box">

                                    <i class="fas fa-folder-open"></i>

                                    <h4>

                                        Chưa có danh mục nào

                                    </h4>

                                    <p>

                                        Hãy thêm danh mục đầu tiên cho hệ thống

                                    </p>

                                </div>

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script>
    setTimeout(function() {

        const alertBox = document.getElementById('autoCloseAlert');

        if (alertBox) {

            alertBox.classList.add('hide');

            setTimeout(() => {

                alertBox.remove();

            }, 400);
        }

    }, 3000);
</script>

<?php include '../includes/footer.php'; ?>