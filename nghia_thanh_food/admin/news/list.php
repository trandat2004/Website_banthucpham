<?php
require_once '../includes/config.php';

$db = getDB();

$stmt = $db->query("
    SELECT *
    FROM tintuc
    ORDER BY ngay_dang DESC
");

$news_list = $stmt->fetchAll();

$total_news = count($news_list);

include '../includes/header.php';

?>
<style>
    .custom-toast {
        position: fixed;
        top: 20px;
        right: 20px;

        width: auto;
        max-width: 300px;

        padding: 12px 16px;

        background: linear-gradient(135deg, #16a34a, #166534);
        color: #fff;

        border-radius: 12px;

        font-size: 14px;
        font-weight: 600;

        box-shadow: 0 10px 25px rgba(0, 0, 0, .2);

        z-index: 999999;

        display: inline-flex;
        align-items: center;
        gap: 8px;

        white-space: nowrap;

        animation: toastShow .3s ease;
    }

    /* animation */
    @keyframes toastShow {
        from {
            opacity: 0;
            transform: translateY(-15px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
<?php if (isset($_SESSION['news_success'])): ?>

    <div class="custom-toast" id="successToast">

        <i class="fas fa-circle-check me-2"></i>

        <?php
        echo $_SESSION['news_success'];
        unset($_SESSION['news_success']);
        ?>

    </div>

<?php endif; ?>
<style>
    .news-page {
        height: 100vh;
        overflow: hidden;
        background: #f4f6f9;
    }

    .news-wrapper {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .news-top {
        flex-shrink: 0;
    }

    /* MINI STATS */

    .mini-stats-card {
        background: linear-gradient(135deg, #16a34a, #166534);

        border-radius: 16px;

        padding: 10px 18px;

        min-width: 150px;

        color: #fff;

        box-shadow: 0 10px 25px rgba(22, 163, 74, .25);

        position: relative;
        overflow: hidden;
    }

    .mini-stats-card::after {
        content: '';

        position: absolute;

        right: -15px;
        top: -15px;

        width: 70px;
        height: 70px;

        background: rgba(255, 255, 255, .12);

        border-radius: 50%;
    }

    .mini-stats-label {
        font-size: 13px;
        opacity: .9;
        margin-bottom: 2px;
    }

    .mini-stats-value {
        font-size: 26px;
        font-weight: 800;
        line-height: 1;
    }

    .add-news-btn {
        height: 54px;

        border-radius: 14px;

        padding: 0 26px;

        font-weight: 700;

        font-size: 16px;

        display: flex;
        align-items: center;
        justify-content: center;
    }

    .news-card {
        flex: 1;
        min-height: 0;

        background: #fff;
        border-radius: 22px;
        border: none;

        overflow: hidden;

        box-shadow: 0 8px 30px rgba(0, 0, 0, .05);
    }

    .news-table-wrapper {
        height: 100%;
        overflow-y: auto;
        overflow-x: auto;
    }

    /* SCROLLBAR */

    .news-table-wrapper::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .news-table-wrapper::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 20px;
    }

    /* TABLE */

    .news-table {
        margin: 0;
    }

    .news-table thead {
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .news-table thead th {
        background: #f8fafc !important;

        padding: 16px;

        border: none;

        position: sticky;
        top: 0;

        white-space: nowrap;
    }

    .news-table tbody td {
        padding: 16px;
        vertical-align: middle;
    }

    .news-table tbody tr {
        transition: .2s;
    }

    .news-table tbody tr:hover {
        background: #f9fafb;
    }

    .news-title {
        font-weight: 700;
        color: #111827;
    }

    .news-image {
        width: 100px;
        height: 70px;

        object-fit: cover;
        border-radius: 10px;
    }

    /* TOAST */

    .custom-toast {
        position: fixed;

        top: 20px;
        right: 25px;

        background: linear-gradient(135deg, #16a34a, #166534);

        color: #fff;

        padding: 14px 22px;

        border-radius: 16px;

        font-weight: 600;

        box-shadow: 0 10px 30px rgba(0, 0, 0, .18);

        z-index: 99999;

        display: flex;
        align-items: center;

        animation: toastShow .35s ease;
    }

    @keyframes toastShow {

        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div class="container-fluid">

    <div class="row">

        <?php include '../includes/sidebar.php'; ?>

        <div class="col-md-10 p-4 news-page">

            <div class="news-wrapper">

                <?php if (isset($_SESSION['success_message'])): ?>

                    <div class="custom-toast" id="successToast">

                        <i class="fas fa-circle-check me-2"></i>

                        <?php
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                        ?>

                    </div>

                <?php endif; ?>

                <div class="news-top mb-4">

                    <div class="d-flex justify-content-between align-items-center">

                        <h2 class="fw-bold mb-0">
                            Quản lý Tin tức
                        </h2>

                        <div class="d-flex align-items-center gap-3">

                            <div class="mini-stats-card">

                                <div class="mini-stats-label">
                                    Tổng tin tức
                                </div>

                                <div class="mini-stats-value">
                                    <?php echo number_format($total_news); ?>
                                </div>

                            </div>

                            <a
                                href="create.php"
                                class="btn btn-success add-news-btn">
                                <i class="fas fa-plus me-2"></i>
                                Thêm tin tức
                            </a>

                        </div>

                    </div>

                </div>

                <div class="news-card">

                    <div class="news-table-wrapper">

                        <table class="table table-hover align-middle news-table">

                            <thead>

                                <tr>

                                    <th>ID</th>

                                    <th>Ảnh</th>

                                    <th>Tiêu đề</th>

                                    <th>Lượt xem</th>

                                    <th>Ngày đăng</th>

                                    <th>Trạng thái</th>

                                    <th width="180">
                                        Hành động
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($news_list as $news): ?>

                                    <tr>

                                        <td>
                                            <?php echo $news['id']; ?>
                                        </td>

                                        <td>

                                            <img
                                                src="<?php echo BASE_URL; ?>/public/assets/images/news/<?php echo $news['hinh_anh']; ?>"
                                                class="news-image">

                                        </td>

                                        <td>

                                            <div class="news-title">
                                                <?php echo htmlspecialchars($news['tieu_de']); ?>
                                            </div>

                                        </td>

                                        <td>
                                            <?php echo $news['luot_xem']; ?>
                                        </td>

                                        <td>

                                            <?php
                                            echo date(
                                                'd/m/Y',
                                                strtotime($news['ngay_dang'])
                                            );
                                            ?>

                                        </td>

                                        <td>

                                            <?php if ($news['trang_thai'] == 1): ?>

                                                <span class="badge bg-success">
                                                    Hiển thị
                                                </span>

                                            <?php else: ?>

                                                <span class="badge bg-danger">
                                                    Ẩn
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td>

                                            <a
                                                href="edit.php?id=<?php echo $news['id']; ?>"
                                                class="btn btn-warning btn-sm">

                                                <i class="fas fa-edit"></i>

                                            </a>

                                            <a
                                                href="delete.php?id=<?php echo $news['id']; ?>"
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('Bạn có chắc muốn xóa?')">

                                                <i class="fas fa-trash"></i>

                                            </a>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<script>
    setTimeout(function() {

        const toast = document.getElementById('successToast');

        if (toast) {

            toast.style.transition = '0.4s';

            toast.style.opacity = '0';

            toast.style.transform = 'translateY(-20px)';

            setTimeout(() => {

                toast.remove();

            }, 400);
        }

    }, 3000);
</script>

<?php include '../includes/footer.php'; ?>