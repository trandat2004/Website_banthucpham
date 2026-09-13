<?php
require_once '../includes/config.php';

if (!isset($_SESSION['order_success'])) {
    redirect('/public/pages/products.php');
}

$order_code = $_SESSION['order_success'];
unset($_SESSION['order_success']);

$db = getDB();

$stmt = $db->prepare("
    SELECT *
    FROM donhang
    WHERE ma_don_hang = ?
    LIMIT 1
");

$stmt->execute([$order_code]);

$order = $stmt->fetch();

$page_title = 'Đặt hàng thành công';

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm text-center">
                <div class="card-body py-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 80px;"></i>
                    </div>
                    <h2 class="mb-3">Đặt hàng thành công!</h2>
                    <p class="lead">Cảm ơn bạn đã đặt hàng tại Nghĩa Thành Food</p>
                    <p>Mã đơn hàng của bạn: <strong class="h4 text-success"><?php echo $order_code; ?></strong></p>
                    <p>Chúng tôi sẽ liên hệ với bạn trong thời gian sớm nhất để xác nhận đơn hàng.</p>
                    <?php if ($order): ?>

                        <div class="bg-light rounded p-3 mt-4 text-start">

                            <h5 class="mb-3 text-success">
                                Chi tiết thanh toán
                            </h5>

                            <div class="d-flex justify-content-between mb-2">
                                <span>Tạm tính:</span>

                                <strong>
                                    <?php echo formatPrice($order['tong_tien']); ?>
                                </strong>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span>Phí vận chuyển:</span>

                                <strong>

                                    <?php
                                    if ($order['phi_ship'] > 0) {

                                        echo formatPrice($order['phi_ship']);
                                    } else {

                                        echo '<span class="text-success">Miễn phí</span>';
                                    }
                                    ?>

                                </strong>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">
                                    Tổng thanh toán:
                                </span>

                                <strong class="text-danger h5">
                                    <?php echo formatPrice($order['tong_thanh_toan']); ?>
                                </strong>
                            </div>

                        </div>

                    <?php endif; ?>
                    <div class="mt-4">
                        <a href="<?php echo BASE_URL; ?>/public/pages/profile.php?order_id=<?php echo $order['id']; ?>#orders-tab"
                            class="btn btn-success">
                            <i class="fas fa-chart-line me-2"></i>Xem đơn hàng của tôi
                        </a>
                        <a href="<?php echo BASE_URL; ?>/public/pages/products.php" class="btn btn-outline-success ms-2">
                            <i class="fas fa-shopping-bag me-2"></i>Tiếp tục mua sắm
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>