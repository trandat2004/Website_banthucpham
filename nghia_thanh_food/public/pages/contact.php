<?php
require_once '../includes/config.php';

$page_title = 'Liên hệ';

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {

    $db = getDB();

    $ho_ten = safeInput($_POST['ho_ten'] ?? '');
    $email = safeInput($_POST['email'] ?? '');
    $dien_thoai = safeInput($_POST['dien_thoai'] ?? '');
    $tieu_de = safeInput($_POST['tieu_de'] ?? '');
    $noi_dung = safeInput($_POST['noi_dung'] ?? '');
    
    // Lấy user_id nếu đã đăng nhập
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // Validate
    if(empty($ho_ten) || empty($email) || empty($tieu_de) || empty($noi_dung)){
        $error = "Vui lòng nhập đầy đủ thông tin bắt buộc!";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ!";
    } else {
        try {
            date_default_timezone_set('Asia/Ho_Chi_Minh');
            $now = date('Y-m-d H:i:s');

            $stmt = $db->prepare("
                INSERT INTO lienhe
                (
                    ho_ten,
                    email,
                    dien_thoai,
                    tieu_de,
                    noi_dung,
                    user_id,
                    trang_thai,
                    ngay_gui
                )
                VALUES
                (
                    :ho_ten,
                    :email,
                    :dien_thoai,
                    :tieu_de,
                    :noi_dung,
                    :user_id,
                    0,
                    :ngay_gui
                )
            ");

            $result = $stmt->execute([
                ':ho_ten'     => $ho_ten,
                ':email'      => $email,
                ':dien_thoai' => $dien_thoai,
                ':tieu_de'    => $tieu_de,
                ':noi_dung'   => $noi_dung,
                ':user_id'    => $user_id,
                ':ngay_gui'   => $now
            ]);

            if($result){
                $success = "Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong thời gian sớm nhất.";
                // Reset form
                $ho_ten = '';
                $email = '';
                $dien_thoai = '';
                $tieu_de = '';
                $noi_dung = '';
            } else {
                $error = "Không thể gửi liên hệ. Vui lòng thử lại!";
            }
        } catch(PDOException $e){
            $error = "Lỗi database: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<style>
.contact-hero{
    background: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)), url('<?php echo BASE_URL; ?>/public/assets/images/login-bg.jpg');
    background-size:cover;
    background-position:center;
    padding:90px 20px;
    border-radius:0 0 40px 40px;
    text-align:center;
    color:#fff;
    margin-bottom:50px;
}
.contact-hero h1{ font-size:52px; font-weight:800; margin-bottom:15px; }
.contact-hero p{ font-size:18px; opacity:0.95; }
.contact-card{ border:none; border-radius:28px; overflow:hidden; background:#fff; box-shadow:0 10px 35px rgba(0,0,0,0.08); }
.contact-info{ background:linear-gradient(180deg,#0b5d39,#198754); color:#fff; padding:40px; height:100%; }
.contact-info h3{ font-weight:800; margin-bottom:30px; }
.info-item{ display:flex; gap:18px; margin-bottom:28px; }
.info-icon{ width:55px; height:55px; border-radius:18px; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.info-icon i{ font-size:22px; }
.info-content h6{ font-weight:700; margin-bottom:6px; }
.info-content p{ margin:0; opacity:0.92; line-height:1.7; }
.contact-form{ padding:40px; }
.contact-form h3{ font-weight:800; color:#0b5d39; margin-bottom:30px; }
.form-label{ font-weight:700; color:#14532d; margin-bottom:10px; }
.form-control{ height:55px; border-radius:16px; border:1px solid #e5e7eb; padding:0 18px; background:#f9fafb; }
textarea.form-control{ height:auto; padding-top:15px; }
.form-control:focus{ box-shadow:none; border-color:#198754; background:#fff; }
.contact-btn{ height:56px; border:none; border-radius:50px; background:linear-gradient(to right,#78c841,#005f3c); color:#fff; font-weight:700; font-size:17px; padding:0 35px; transition:0.3s; }
.contact-btn:hover{ transform:translateY(-2px); opacity:0.95; }
.contact-map{ border:none; border-radius:28px; overflow:hidden; box-shadow:0 10px 35px rgba(0,0,0,0.08); }
.alert{ border:none; border-radius:16px; }
@media(max-width:768px){
    .contact-hero{ padding:70px 20px; }
    .contact-hero h1{ font-size:38px; }
    .contact-form, .contact-info{ padding:28px; }
}
</style>

<div class="contact-hero">
    <h1>Liên hệ với chúng tôi</h1>
    <p>Chúng tôi luôn sẵn sàng hỗ trợ và giải đáp mọi thắc mắc của bạn</p>
</div>

<div class="container mb-5">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="contact-card h-100">
                <div class="contact-info">
                    <h3>Thông tin liên hệ</h3>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="info-content">
                            <h6>Địa chỉ</h6>
                            <p><?php echo getConfig('company_address'); ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                        <div class="info-content">
                            <h6>Điện thoại</h6>
                            <p><?php echo getConfig('company_phone'); ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-envelope"></i></div>
                        <div class="info-content">
                            <h6>Email</h6>
                            <p><?php echo getConfig('company_email'); ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-clock"></i></div>
                        <div class="info-content">
                            <h6>Giờ làm việc</h6>
                            <p>Thứ Hai - Thứ Bảy: 8:00 - 20:00<br>Chủ Nhật: 9:00 - 17:00</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="contact-card">
                <div class="contact-form">
                    <h3>Gửi tin nhắn cho chúng tôi</h3>
                    <?php if($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Họ và tên</label>
                                <input type="text" name="ho_ten" class="form-control" value="<?php echo htmlspecialchars($ho_ten ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" name="dien_thoai" class="form-control" value="<?php echo htmlspecialchars($dien_thoai ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề</label>
                            <input type="text" name="tieu_de" class="form-control" value="<?php echo htmlspecialchars($tieu_de ?? ''); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Nội dung</label>
                            <textarea name="noi_dung" class="form-control" rows="6" required><?php echo htmlspecialchars($noi_dung ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="contact-btn"><i class="fas fa-paper-plane me-2"></i> Gửi liên hệ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-5">
        <div class="col-12">
            <div class="contact-map">
                <iframe src="https://www.google.com/maps?q=S%E1%BB%91%2025%20Song%20H%C3%A0o%2C%20Ph%C6%B0%E1%BB%9Dng%20Tr%E1%BA%A7n%20Quang%20Kh%E1%BA%A3i%2C%20Th%C3%A0nh%20ph%E1%BB%91%20Nam%20%C4%90%E1%BB%8Bnh%2C%20Nam%20%C4%90%E1%BB%8Bnh&output=embed" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>