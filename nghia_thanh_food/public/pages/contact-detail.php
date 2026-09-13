<?php
require_once '../includes/config.php';

if (!isLoggedIn()) {
    redirect('/public/pages/login.php');
}

$page_title = 'Chi tiết liên hệ';
$db = getDB();
$user_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    redirect('/public/pages/profile.php');
}

$stmt = $db->prepare("SELECT * FROM lienhe WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$contact = $stmt->fetch();

if (!$contact) {
    redirect('/public/pages/profile.php');
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<style>
.contact-detail-page {
    background: #f8fafc;
    min-height: 100vh;
    padding: 40px 0;
}
.detail-card {
    border: none;
    border-radius: 28px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}
.detail-header {
    background: linear-gradient(135deg, #166534, #15803d);
    padding: 20px 30px;
    color: white;
}
.detail-header h3 {
    margin: 0;
    font-weight: 700;
}
.detail-body {
    padding: 30px;
}
.info-group {
    margin-bottom: 25px;
}
.info-label {
    font-weight: 700;
    color: #166534;
    font-size: 16px;
    margin-bottom: 8px;
    display: block;
    border-left: 4px solid #16a34a;
    padding-left: 12px;
}
.info-content {
    background: #f9fafb;
    border-radius: 18px;
    padding: 18px;
    color: #1f2937;
    line-height: 1.65;
}
.reply-box {
    background: #ecfdf5;
    border-radius: 18px;
    padding: 20px;
    border: 1px solid #a7f3d0;
    margin-top: 25px;
}
.reply-header {
    font-weight: 700;
    color: #047857;
    margin-bottom: 12px;
    font-size: 18px;
}
.reply-meta {
    font-size: 13px;
    color: #059669;
    margin-top: 12px;
    border-top: 1px dashed #a7f3d0;
    padding-top: 10px;
}
.back-btn {
    margin-top: 30px;
    display: inline-block;
}
</style>

<div class="contact-detail-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="detail-card">
                    <div class="detail-header">
                        <h3><i class="fas fa-envelope-open-text me-2"></i> Chi tiết liên hệ</h3>
                    </div>
                    <div class="detail-body">
                        <div class="info-group">
                            <span class="info-label"><i class="fas fa-tag me-2"></i> Tiêu đề</span>
                            <div class="info-content"><?php echo htmlspecialchars($contact['tieu_de']); ?></div>
                        </div>
                        <div class="info-group">
                            <span class="info-label"><i class="fas fa-calendar-alt me-2"></i> Ngày gửi</span>
                            <div class="info-content"><?php echo date('d/m/Y H:i', strtotime($contact['ngay_gui'])); ?></div>
                        </div>
                        <div class="info-group">
                            <span class="info-label"><i class="fas fa-user me-2"></i> Người gửi</span>
                            <div class="info-content"><?php echo htmlspecialchars($contact['ho_ten']); ?> (<?php echo htmlspecialchars($contact['email']); ?>)</div>
                        </div>
                        <div class="info-group">
                            <span class="info-label"><i class="fas fa-comment-dots me-2"></i> Nội dung</span>
                            <div class="info-content"><?php echo nl2br(htmlspecialchars($contact['noi_dung'])); ?></div>
                        </div>

                        <?php if (!empty($contact['phan_hoi'])): ?>
                        <div class="reply-box">
                            <div class="reply-header">
                                <i class="fas fa-reply-all me-2"></i> Phản hồi từ Admin
                            </div>
                            <div><?php echo nl2br(htmlspecialchars($contact['phan_hoi'])); ?></div>
                            <div class="reply-meta">
                                <i class="fas fa-user-shield me-1"></i> <?php echo htmlspecialchars($contact['admin_phan_hoi']); ?> &nbsp;|&nbsp;
                                <i class="fas fa-clock me-1"></i> <?php echo date('d/m/Y H:i', strtotime($contact['ngay_phan_hoi'])); ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-clock me-2"></i> Chưa có phản hồi từ admin. Vui lòng chờ trong thời gian sớm nhất.
                        </div>
                        <?php endif; ?>

                        <div class="text-center">
                            <a href="/public/pages/profile.php" class="btn btn-outline-success back-btn">
                                <i class="fas fa-arrow-left me-2"></i> Quay lại trang tài khoản
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>