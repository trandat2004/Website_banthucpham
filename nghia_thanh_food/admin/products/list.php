<?php
require_once __DIR__ . '/../includes/check_auth.php';

$page_title = 'Sản phẩm';

require_once '../includes/config.php';

include '../includes/header.php';
include '../includes/sidebar.php';

$db = getDB();

/* XÓA */

if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $stmt = $db->prepare("
        DELETE FROM sanpham
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $_SESSION['success_message'] = 'Đã xóa sản phẩm thành công!';

    redirect('/admin/products/list.php');
}

/* DANH SÁCH */

$stmt = $db->query("
    SELECT
        sanpham.*,
        danhmuc.ten_danh_muc
    FROM sanpham
    LEFT JOIN danhmuc
    ON sanpham.danh_muc_id = danhmuc.id
    ORDER BY sanpham.id DESC
");

$products = $stmt->fetchAll();
?>

<style>
    .product-wrapper {

        height: calc(100vh - 70px);

        overflow: hidden;
    }

    .product-scroll {

        height: calc(100vh - 125px);

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

        border-radius: 20px;

        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }

    .table {

        width: 100%;

        min-width: 100%;

        margin-bottom: 0;

        border-collapse: separate;

        border-spacing: 0;
    }

    .table-responsive {

        width: 100%;

        margin: 0;

        padding: 0;
    }

    .product-card {

        width: 100%;
    }

    .table thead {

        position: sticky;

        top: 0;

        z-index: 30;
    }

    .table thead tr {

        background: #f8fafc;
    }

    .table thead th {

        background: #6ae990;

        padding: 18px 16px;

        border-bottom: 1px solid #e5e7eb;

        font-weight: 700;

        color: #374151;

        white-space: nowrap;

        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
    }

    .table tbody tr {

        vertical-align: middle;
    }

    .product-thumb {

        width: 70px;

        height: 70px;

        border-radius: 14px;

        object-fit: cover;

        border: 1px solid #e5e7eb;
    }

    .product-empty {

        width: 70px;

        height: 70px;

        border-radius: 14px;

        background: #f3f4f6;

        display: flex;

        align-items: center;

        justify-content: center;

        color: #9ca3af;

        font-size: 22px;
    }

    .price {

        font-weight: 700;

        color: #16a34a;
    }

    .badge-status {

        padding: 8px 14px;

        border-radius: 30px;

        font-size: 12px;
    }

    .action-btn {

        width: 36px;

        height: 36px;

        border-radius: 10px;

        display: inline-flex;

        align-items: center;

        justify-content: center;
    }

    .custom-alert {

        position: fixed;

        top: 20px;

        right: 20px;

        z-index: 9999;

        min-width: 320px;

        border: none;

        border-radius: 16px;

        padding: 16px 22px;
        background: linear-gradient(135deg,
                #16a34a,
                #15803d);

        color: #fff;

        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);

        animation: slideInRight .4s ease;
    }

    /* animation vào */

    @keyframes slideInRight {

        from {

            opacity: 0;

            transform: translateX(100px);
        }

        to {

            opacity: 1;

            transform: translateX(0);
        }
    }

    /* animation ra */

    .fade-out {

        animation: fadeOut .4s ease forwards;
    }

    @keyframes fadeOut {

        from {

            opacity: 1;

            transform: translateX(0);
        }

        to {

            opacity: 0;

            transform: translateX(100px);
        }
    }
</style>

<div class="col-md-10 main-content p-4 product-wrapper">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                Quản lý sản phẩm
            </h2>

            <p class="text-muted mb-0">
                Tổng cộng <?php echo count($products); ?> sản phẩm
            </p>

        </div>

        <a
            href="create.php"
            class="btn btn-success">

            <i class="fas fa-plus me-2"></i>

            Thêm sản phẩm

        </a>

    </div>

    <?php if (isset($_SESSION['success_message'])): ?>

        <div class="alert alert-success custom-alert" id="successAlert">

            <i class="fas fa-check-circle me-2"></i>

            <?php
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>

        </div>

    <?php endif; ?>

    <div class="card product-card">

        <div class="card-body p-0">

            <div class="table-responsive product-scroll">

                <table class="table table-hover align-middle mb-0">

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Ảnh</th>

                            <th>Mã SP</th>

                            <th>Tên sản phẩm</th>

                            <th>Danh mục</th>

                            <th>Giá bán</th>

                            <th>Tồn kho</th>

                            <th>Trạng thái</th>

                            <th width="120">
                                Thao tác
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (count($products) > 0): ?>

                            <?php foreach ($products as $p): ?>

                                <tr>

                                    <td>

                                        #<?php echo $p['id']; ?>

                                    </td>

                                    <td>

                                        <?php if (!empty($p['hinh_anh'])): ?>

                                            <img
                                                src="<?php echo BASE_URL; ?>/public/assets/images/products/<?php echo $p['hinh_anh']; ?>"
                                                class="product-thumb">

                                        <?php else: ?>

                                            <div class="product-empty">

                                                <i class="fas fa-image"></i>

                                            </div>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <strong>

                                            <?php echo $p['ma_san_pham']; ?>

                                        </strong>

                                    </td>

                                    <td>

                                        <?php echo htmlspecialchars($p['ten_san_pham']); ?>

                                    </td>

                                    <td>

                                        <?php echo $p['ten_danh_muc'] ?: 'Chưa phân loại'; ?>

                                    </td>

                                    <td class="price">

                                        <?php echo formatPrice($p['gia_ban']); ?>

                                    </td>

                                    <td>

                                        <?php echo number_format($p['so_luong']); ?>

                                    </td>

                                    <td>

                                        <?php if ($p['trang_thai']): ?>

                                            <span class="badge bg-success badge-status">

                                                Hiển thị

                                            </span>

                                        <?php else: ?>

                                            <span class="badge bg-secondary badge-status">

                                                Ẩn

                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <a
                                            href="edit.php?id=<?php echo $p['id']; ?>"
                                            class="btn btn-warning btn-sm action-btn">

                                            <i class="fas fa-edit"></i>

                                        </a>

                                        <a
                                            href="?delete=<?php echo $p['id']; ?>"
                                            class="btn btn-danger btn-sm action-btn"
                                            onclick="return confirm('Xóa sản phẩm này?')">

                                            <i class="fas fa-trash"></i>

                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="9" class="text-center py-5">

                                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>

                                    <div class="fw-bold">

                                        Chưa có sản phẩm nào

                                    </div>

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<script>
    const alertBox = document.getElementById('successAlert');

    if (alertBox) {

        setTimeout(() => {

            alertBox.classList.add('fade-out');

            setTimeout(() => {

                alertBox.remove();

            }, 400);

        }, 3000);
    }
</script>

<?php include '../includes/footer.php'; ?>