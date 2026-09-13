<?php
require_once '../includes/config.php';

$page_title = 'Tin tức';

$db = getDB();
$news_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

/*
|--------------------------------------------------------------------------
| CHI TIẾT TIN TỨC
|--------------------------------------------------------------------------
*/

if ($news_id > 0) {

    // tăng lượt xem
    $update_view = $db->prepare("
        UPDATE tintuc
        SET luot_xem = luot_xem + 1
        WHERE id = ?
    ");
    $update_view->execute([$news_id]);

    // lấy bài viết
    $stmt = $db->prepare("
        SELECT *
        FROM tintuc
        WHERE id = ?
        AND trang_thai = 1
    ");

    $stmt->execute([$news_id]);

    $news_detail = $stmt->fetch();

    if ($news_detail) {

        $page_title = $news_detail['tieu_de'];

        include '../includes/header.php';
        include '../includes/navbar.php';
?>

        <div class="container my-5">

            <div class="row">

                <div class="col-lg-9 mx-auto">

                    <!-- breadcrumb -->

                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">

                            <li class="breadcrumb-item">
                                <a href="<?php echo BASE_URL; ?>/index.php">
                                    Trang chủ
                                </a>
                            </li>

                            <li class="breadcrumb-item">
                                <a href="<?php echo BASE_URL; ?>/public/pages/news.php">
                                    Tin tức
                                </a>
                            </li>

                            <li class="breadcrumb-item active">
                                <?php echo htmlspecialchars($news_detail['tieu_de']); ?>
                            </li>

                        </ol>
                    </nav>

                    <!-- bài viết -->

                    <div class="news-detail-wrapper">

                        <span class="news-badge">
                            Nghĩa Thành Food
                        </span>

                        <h1 class="news-detail-title">
                            <?php echo htmlspecialchars($news_detail['tieu_de']); ?>
                        </h1>

                        <div class="news-meta">

                            <span>
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('d/m/Y', strtotime($news_detail['ngay_dang'])); ?>
                            </span>

                            <span>
                                <i class="far fa-eye"></i>
                                <?php echo number_format($news_detail['luot_xem']); ?> lượt xem
                            </span>

                        </div>

                        <!-- ảnh -->

                        <img
                            src="<?php echo BASE_URL; ?>/public/assets/images/news/<?php echo $news_detail['hinh_anh']; ?>"
                            class="news-detail-image"
                            alt="<?php echo htmlspecialchars($news_detail['tieu_de']); ?>">

                        <!-- nội dung -->

                        <div class="news-content">
                            <?php echo $news_detail['noi_dung']; ?>
                        </div>

                        <!-- quay lại -->

                        <div class="mt-5">

                            <a href="<?php echo BASE_URL; ?>/public/pages/news.php"
                                class="btn btn-success news-back-btn">

                                <i class="fas fa-arrow-left me-2"></i>
                                Quay lại danh sách tin tức

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

<?php
        include '../includes/footer.php';
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| DANH SÁCH TIN TỨC
|--------------------------------------------------------------------------
*/

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

$limit = 9;
$offset = ($page - 1) * $limit;

$stmt = $db->prepare("
    SELECT COUNT(*) as total
    FROM tintuc
    WHERE trang_thai = 1
");

$stmt->execute();

$total_news = $stmt->fetch()['total'];

$total_pages = ceil($total_news / $limit);

$stmt = $db->prepare("
    SELECT *
    FROM tintuc
    WHERE trang_thai = 1
    ORDER BY ngay_dang DESC
    LIMIT $limit OFFSET $offset
");

$stmt->execute();

$news_list = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container my-5">

    <!-- heading -->

    <div class="text-center mb-5">

        <span class="news-page-badge">
            Tin tức & sự kiện
        </span>

        <h1 class="news-page-title">
            Tin tức Nghĩa Thành Food
        </h1>

        <p class="news-page-desc">
            Cập nhật hoạt động mới nhất, chương trình ưu đãi
            và những kiến thức hữu ích về thực phẩm sạch.
        </p>

    </div>

    <!-- list -->

    <div class="row g-4">

        <?php foreach ($news_list as $news): ?>

            <div class="col-lg-4 col-md-6">

                <div class="card news-card h-100">

                    <!-- ảnh -->

                    <div class="news-thumb-wrapper">

                        <img
                            src="<?php echo BASE_URL; ?>/public/assets/images/news/<?php echo $news['hinh_anh']; ?>"
                            class="card-img-top news-thumb"
                            alt="<?php echo htmlspecialchars($news['tieu_de']); ?>">

                    </div>

                    <!-- body -->

                    <div class="card-body d-flex flex-column">

                        <div class="news-date">

                            <i class="far fa-calendar-alt me-2"></i>

                            <?php echo date('d/m/Y', strtotime($news['ngay_dang'])); ?>

                        </div>

                        <h4 class="news-card-title">

                            <?php echo htmlspecialchars($news['tieu_de']); ?>

                        </h4>

                        <p class="news-card-desc">

                            <?php
                            echo mb_substr(
                                strip_tags($news['noi_dung']),
                                0,
                                140
                            );
                            ?>

                        </p>

                        <div class="mt-auto">

                            <a href="?id=<?php echo $news['id']; ?>"
                                class="btn news-btn">

                                Đọc tiếp
                                <i class="fas fa-arrow-right ms-2"></i>

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

    <!-- không có bài -->

    <?php if (count($news_list) == 0): ?>

        <div class="alert alert-warning text-center mt-5">

            Chưa có bài viết nào.

        </div>

    <?php endif; ?>

    <!-- phân trang -->

    <?php if ($total_pages > 1): ?>

        <nav class="mt-5">

            <ul class="pagination justify-content-center">

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>

                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">

                        <a class="page-link"
                            href="?page=<?php echo $i; ?>">

                            <?php echo $i; ?>

                        </a>

                    </li>

                <?php endfor; ?>

            </ul>

        </nav>

    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>