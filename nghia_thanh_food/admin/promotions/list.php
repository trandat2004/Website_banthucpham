<?php
require_once __DIR__ . '/../includes/check_auth.php';

$page_title = 'Khuyến mãi';

require_once '../includes/config.php';

include '../includes/header.php';
include '../includes/sidebar.php';

$db = getDB();

/*
|---------------------------------------------------
| XÓA KHUYẾN MÃI
|---------------------------------------------------
*/

if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $stmt = $db->prepare("
        DELETE FROM khuyenmai
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $_SESSION['success_message'] = 'Đã xóa khuyến mãi!';

    redirect('/admin/promotions/list.php');
}

/*
|---------------------------------------------------
| DANH SÁCH
|---------------------------------------------------
*/

$stmt = $db->query("
    SELECT *
    FROM khuyenmai
    ORDER BY id DESC
");

$promotions = $stmt->fetchAll();

?>

<style>
    .promotion-wrapper {

        height: calc(100vh - 70px);

        overflow: hidden;
    }

    .promotion-scroll {

        height: calc(100vh - 125px);

        overflow-y: auto;

        padding-right: 6px;
    }

    .promotion-scroll::-webkit-scrollbar {

        width: 8px;
    }

    .promotion-scroll::-webkit-scrollbar-thumb {

        background: #d1d5db;

        border-radius: 20px;
    }

    .promotion-card {

        border: none;

        border-radius: 20px;

        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }

    .table thead {

        position: sticky;

        top: 0;

        z-index: 30;
    }

    .table thead th {

        background: #6ae990;

        padding: 18px 16px;

        border-bottom: 1px solid #e5e7eb;

        font-weight: 700;

        color: #374151;

        white-space: nowrap;
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

<div class="col-md-10 main-content p-4 promotion-wrapper">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                Quản lý khuyến mãi
            </h2>

            <p class="text-muted mb-0">
                Tổng cộng <?php echo count($promotions); ?> chương trình
            </p>

        </div>

        <a
            href="create.php"
            class="btn btn-success">

            <i class="fas fa-plus me-2"></i>

            Thêm khuyến mãi

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

    <div class="card promotion-card">

        <div class="card-body p-0">

            <div class="table-responsive promotion-scroll">

                <table class="table table-hover align-middle mb-0">

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Tên khuyến mãi</th>

                            <th>Loại</th>

                            <th>Giá trị</th>

                            <th>Đơn tối thiểu</th>

                            <th>Thời gian</th>

                            <th>Trạng thái</th>

                            <th width="120">
                                Thao tác
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (count($promotions) > 0): ?>

                            <?php foreach ($promotions as $km): ?>

                                <?php

                                $now = date('Y-m-d H:i:s');

                                /*
|---------------------------------------------------
| XÁC ĐỊNH TRẠNG THÁI
|---------------------------------------------------
*/

                                $status_text = '';
                                $status_class = '';

                                if ($km['trang_thai'] == 0) {

                                    $status_text = 'Đã dừng';

                                    $status_class = 'bg-secondary';
                                } else if ($now < $km['ngay_bat_dau']) {

                                    $status_text = 'Sắp diễn ra';

                                    $status_class = 'bg-warning text-dark';
                                } else if ($now > $km['ngay_ket_thuc']) {

                                    $status_text = 'Đã kết thúc';

                                    $status_class = 'bg-dark';
                                } else {

                                    $status_text = 'Đang hoạt động';

                                    $status_class = 'bg-success';
                                }

                                ?>

                                <tr>

                                    <td>
                                        #<?php echo $km['id']; ?>
                                    </td>

                                    <td>

                                        <strong>
                                            <?php echo htmlspecialchars($km['ten_khuyen_mai']); ?>
                                        </strong>

                                    </td>

                                    <td>

                                        <?php

                                        switch ($km['loai_khuyen_mai']) {

                                            case 'giam_phan_tram':
                                                echo 'Giảm %';
                                                break;

                                            case 'giam_tien_don':
                                                echo 'Giảm tiền';
                                                break;

                                            case 'free_ship':
                                                echo 'Free ship';
                                                break;
                                        }

                                        ?>

                                    </td>

                                    <td>

                                        <?php

                                        if ($km['loai_khuyen_mai'] == 'giam_phan_tram') {

                                            echo $km['gia_tri'] . '%';
                                        } else {

                                            echo formatPrice($km['gia_tri']);
                                        }

                                        ?>

                                    </td>

                                    <td>

                                        <?php echo formatPrice($km['don_hang_toi_thieu']); ?>

                                    </td>

                                    <td>

                                        <div class="small">

                                            <?php echo date('d/m/Y H:i', strtotime($km['ngay_bat_dau'])); ?>

                                            <br>

                                            <?php echo date('d/m/Y H:i', strtotime($km['ngay_ket_thuc'])); ?>
                                        </div>

                                    </td>

                                    <td>

                                        <span class="badge <?php echo $status_class; ?> badge-status">

                                            <?php echo $status_text; ?>

                                        </span>

                                    </td>

                                    <td>

                                        <a
                                            href="edit.php?id=<?php echo $km['id']; ?>"
                                            class="btn btn-warning btn-sm action-btn">

                                            <i class="fas fa-edit"></i>

                                        </a>

                                        <a
                                            href="?delete=<?php echo $km['id']; ?>"
                                            class="btn btn-danger btn-sm action-btn"
                                            onclick="return confirm('Xóa khuyến mãi này?')">

                                            <i class="fas fa-trash"></i>

                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="8" class="text-center py-5">

                                    <i class="fas fa-tags fa-3x text-muted mb-3"></i>

                                    <div class="fw-bold">

                                        Chưa có khuyến mãi nào

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