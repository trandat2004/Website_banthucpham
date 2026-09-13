<?php
// ĐẶT MÚI GIỜ VIỆT NAM
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once '../includes/config.php';
require_once '../includes/mailer.php';

if (isLoggedIn()) {
    redirect('/index.php');
}

$db = getDB();
$error = '';
$success = '';
$email = '';
$show_otp_form = false; // Hiển thị form OTP + mật khẩu mới
$expiry_time = null;

// ========== KIỂM TRA XEM CÓ PHẢI TỪ TRANG KHÁC CHUYỂN SANG KHÔNG ==========
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
$is_from_forgot = (strpos($referrer, 'forgot-password.php') !== false);

if (!$is_from_forgot && !empty($referrer)) {
    unset($_SESSION['forgot_email_temp']);
    unset($_SESSION['reset_password']);
}

if (empty($referrer)) {
    unset($_SESSION['forgot_email_temp']);
    unset($_SESSION['reset_password']);
}
// ========== KẾT THÚC KIỂM TRA ==========

// Xóa OTP cũ hết hạn
$db->prepare("DELETE FROM maotp WHERE thoi_gian_het_han < datetime('now', 'localtime')")->execute();

// Lấy email đã lưu (nếu có)
if (isset($_SESSION['forgot_email_temp'])) {
    $email = $_SESSION['forgot_email_temp'];
    $show_otp_form = true;

    // Lấy thời gian hết hạn OTP
    $stmt = $db->prepare("
        SELECT thoi_gian_het_han FROM maotp 
        WHERE email = ? AND muc_dich = 'reset_password' AND da_su_dung = 0 
        AND thoi_gian_het_han > datetime('now', 'localtime')
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$email]);
    $otp_valid = $stmt->fetch();

    if ($otp_valid) {
        $expiry_time = strtotime($otp_valid['thoi_gian_het_han']);
    } else {
        // OTP đã hết hạn, quay lại form nhập email
        $show_otp_form = false;
        unset($_SESSION['forgot_email_temp']);
        unset($_SESSION['reset_password']);
        $error = 'Mã OTP đã hết hạn. Vui lòng thử lại.';
    }
}

// Bước 1: Nhập email và gửi OTP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_otp'])) {
    $email = safeInput($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Vui lòng nhập email hợp lệ';
    } else {
        $stmt = $db->prepare("SELECT id, ho_ten, email_verified FROM users WHERE email = ? AND trang_thai = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Email không tồn tại trong hệ thống';
        } elseif ($user['email_verified'] == 0) {
            $error = 'Tài khoản chưa được xác thực email. Vui lòng <a href="register.php">đăng ký</a> lại.';
        } else {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

            $db->prepare("DELETE FROM maotp WHERE email = ? AND muc_dich = 'reset_password'")->execute([$email]);

            $stmt = $db->prepare("INSERT INTO maotp (email, ma_otp, muc_dich, thoi_gian_het_han, ngay_tao) VALUES (?, ?, 'reset_password', ?, datetime('now', 'localtime'))");
            $stmt->execute([$email, $otp, $expires_at]);

            $_SESSION['reset_password'] = [
                'user_id' => $user['id'],
                'email' => $email,
                'ho_ten' => $user['ho_ten']
            ];

            $_SESSION['forgot_email_temp'] = $email;

            if (sendOTP($email, $otp)) {
                $show_otp_form = true;
                $expiry_time = strtotime($expires_at);
                $success = "Mã OTP đã được gửi đến email <strong>$email</strong>. Vui lòng kiểm tra hộp thư.";
            } else {
                $error = "Không thể gửi email xác thực. Vui lòng thử lại sau.";
                $db->prepare("DELETE FROM maotp WHERE email = ? AND muc_dich = 'reset_password'")->execute([$email]);
                unset($_SESSION['reset_password']);
                unset($_SESSION['forgot_email_temp']);
            }
        }
    }
}

// Bước 2: Xác thực OTP + Đổi mật khẩu (gộp chung)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password_submit'])) {
    $otp_input = trim($_POST['otp_code'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = $_SESSION['reset_password']['email'] ?? '';

    // Validate mật khẩu
    if (empty($new_password) || strlen($new_password) < 6) {
        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
    } elseif (empty($otp_input)) {
        $error = 'Vui lòng nhập mã OTP';
    } elseif (empty($email)) {
        $error = 'Phiên đặt lại mật khẩu đã hết hạn. Vui lòng thử lại.';
        $show_otp_form = false;
        unset($_SESSION['reset_password']);
        unset($_SESSION['forgot_email_temp']);
    } else {
        // Kiểm tra OTP
        $stmt = $db->prepare("
            SELECT * FROM maotp 
            WHERE email = ? AND ma_otp = ? AND muc_dich = 'reset_password' AND da_su_dung = 0 
            AND thoi_gian_het_han > datetime('now', 'localtime')
        ");
        $stmt->execute([$email, $otp_input]);
        $otp_record = $stmt->fetch();

        if (!$otp_record) {
            // Kiểm tra xem có phải hết hạn không
            $stmt2 = $db->prepare("
                SELECT * FROM maotp 
                WHERE email = ? AND muc_dich = 'reset_password' AND da_su_dung = 0
                ORDER BY id DESC LIMIT 1
            ");
            $stmt2->execute([$email]);
            $otp_check = $stmt2->fetch();

            if ($otp_check && strtotime($otp_check['thoi_gian_het_han']) < time()) {
                $error = '⏰ Mã OTP đã hết hạn. Vui lòng nhấn "Gửi lại OTP" để nhận mã mới.';
            } else {
                $error = '❌ Mã OTP không đúng. Vui lòng thử lại.';
            }
        } else {
            // OTP đúng, tiến hành đổi mật khẩu
            $db->prepare("UPDATE maotp SET da_su_dung = 1 WHERE id = ?")->execute([$otp_record['id']]);

            $reset = $_SESSION['reset_password'];
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            $update = $db->prepare("UPDATE users SET mat_khau = ?, ngay_cap_nhat = datetime('now', 'localtime') WHERE id = ?");
            if ($update->execute([$hashed, $reset['user_id']])) {
                // Xóa session
                unset($_SESSION['reset_password']);
                unset($_SESSION['forgot_email_temp']);

                $_SESSION['reset_success'] = 'Mật khẩu đã được đổi thành công. Vui lòng đăng nhập.';
                header('Location: login.php?reset=success');
                exit();
            } else {
                $error = 'Có lỗi xảy ra, vui lòng thử lại.';
            }
        }
    }

    // Nếu có lỗi, vẫn giữ lại expiry_time
    if ($show_otp_form && isset($_SESSION['forgot_email_temp'])) {
        $stmt = $db->prepare("
            SELECT thoi_gian_het_han FROM maotp 
            WHERE email = ? AND muc_dich = 'reset_password' AND da_su_dung = 0 
            AND thoi_gian_het_han > datetime('now', 'localtime')
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$_SESSION['forgot_email_temp']]);
        $otp_valid = $stmt->fetch();
        if ($otp_valid) {
            $expiry_time = strtotime($otp_valid['thoi_gian_het_han']);
        }
    }
}

// Gửi lại OTP
if (isset($_GET['resend']) && isset($_SESSION['reset_password'])) {
    $email = $_SESSION['reset_password']['email'];
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    $db->prepare("DELETE FROM maotp WHERE email = ? AND muc_dich = 'reset_password'")->execute([$email]);
    $stmt = $db->prepare("INSERT INTO maotp (email, ma_otp, muc_dich, thoi_gian_het_han, ngay_tao) VALUES (?, ?, 'reset_password', ?, datetime('now', 'localtime'))");
    $stmt->execute([$email, $otp, $expires_at]);

    if (sendOTP($email, $otp)) {
        $expiry_time = strtotime($expires_at);
        $success = "Đã gửi lại mã OTP mới. Vui lòng kiểm tra email.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error = "Không thể gửi lại email.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Hủy OTP (quay lại form nhập email)
if (isset($_GET['cancel_otp'])) {
    unset($_SESSION['reset_password']);
    unset($_SESSION['forgot_email_temp']);
    redirect('/public/pages/forgot-password.php');
}

$page_title = 'Quên mật khẩu';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<style>
    .forgot-page {
        min-height: 100vh;
        background: url('<?php echo BASE_URL; ?>/public/assets/images/login-bg.jpg') center center/cover no-repeat;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 80px 15px;
        position: relative;
    }

    .forgot-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.25);
    }

    .forgot-box {
        width: 100%;
        max-width: 560px;
        background: rgba(255, 255, 255, 0.30);
        backdrop-filter: blur(12px);
        border-radius: 35px;
        padding: 70px 40px 35px;
        position: relative;
        border: 1px solid rgba(255, 255, 255, 0.35);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.18);
        z-index: 2;
    }

    .forgot-leaf {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        overflow: hidden;
        position: absolute;
        top: -45px;
        left: 50%;
        transform: translateX(-50%);
        border: 6px solid white;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
    }

    .forgot-leaf img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .forgot-title {
        text-align: center;
        font-size: 2.2rem;
        font-weight: 800;
        color: #0b5d39;
        margin-bottom: 35px;
    }

    .forgot-form label {
        font-size: 1.2rem;
        font-weight: 600;
        color: #184d33;
        margin-bottom: 10px;
    }

    .forgot-input {
        height: 55px;
        border-radius: 18px;
        border: none;
        background: rgba(255, 255, 255, 0.85);
        padding: 0 20px;
        font-size: 1.1rem;
        color: #14532d;
    }

    .forgot-input:focus {
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.2);
        outline: none;
    }

    .forgot-btn {
        height: 55px;
        border: none;
        border-radius: 40px;
        background: linear-gradient(to right, #7cc242, #005f3b);
        color: white;
        font-size: 1.3rem;
        font-weight: 700;
        width: 100%;
        transition: 0.3s;
    }

    .forgot-btn:hover {
        transform: translateY(-2px);
        opacity: 0.95;
    }

    .back-link {
        text-align: center;
        margin-top: 30px;
    }

    .back-link a {
        color: #0b5d39;
        font-weight: 600;
        text-decoration: none;
    }

    .alert {
        border-radius: 15px;
    }

    .otp-input {
        text-align: center;
        font-size: 28px;
        letter-spacing: 10px;
        font-weight: bold;
    }

    .otp-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .otp-actions .forgot-btn {
        flex: 2;
    }

    .btn-outline-secondary {
        flex: 1;
        border-radius: 40px;
        padding: 12px;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.9);
        color: #0b5d39;
        border: 1px solid #0b5d39;
    }

    .otp-timer {
        font-size: 14px;
        margin-top: 8px;
        text-align: center;
    }

    .timer-text {
        font-weight: bold;
        color: #ff6600;
        font-size: 16px;
    }

    .timer-expired {
        color: red;
        font-weight: bold;
    }

    .reset-password-fields {
        margin-top: 20px;
        border-top: 1px dashed rgba(255, 255, 255, 0.5);
        padding-top: 20px;
    }

    hr {
        margin: 20px 0;
        border-color: rgba(255, 255, 255, 0.3);
    }
</style>

<div class="forgot-page">
    <div class="forgot-overlay"></div>
    <div class="forgot-box">
        <div class="forgot-leaf">
            <img src="<?php echo BASE_URL; ?>/public/assets/images/leaf.png" alt="">
        </div>

        <?php if (!$show_otp_form): ?>
            <!-- FORM NHẬP EMAIL -->
            <h1 class="forgot-title">Quên mật khẩu</h1>
            <p class="text-center mb-4">Nhập email đã đăng ký để nhận mã OTP</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" class="forgot-form">
                <div class="mb-4">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control forgot-input"
                        value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <button type="submit" name="send_otp" class="forgot-btn">Gửi mã OTP</button>
            </form>

        <?php else: ?>
            <!-- FORM NHẬP OTP + MẬT KHẨU MỚI (GỘP CHUNG) -->
            <h1 class="forgot-title">Đặt lại mật khẩu</h1>
            <p class="text-center mb-3">Mã OTP đã được gửi đến</p>
            <p class="text-center mb-4"><strong><?php echo htmlspecialchars($_SESSION['reset_password']['email'] ?? ''); ?></strong></p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" class="forgot-form">

                <div class="reset-password-fields">
                    <div class="mb-3">
                        <label>Mật khẩu mới</label>
                        <input type="password" name="new_password" class="form-control forgot-input" required minlength="6">
                    </div>
                    <div class="mb-4">
                        <label>Xác nhận mật khẩu</label>
                        <input type="password" name="confirm_password" class="form-control forgot-input" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Mã OTP (6 số)</label>
                    <input type="text" name="otp_code" id="otp_code" class="form-control forgot-input otp-input"
                        maxlength="6" pattern="[0-9]{6}" placeholder="______" required autofocus>
                    <div class="otp-timer" id="otpTimer">
                        <span>⏱️ Mã OTP có hiệu lực còn: </span>
                        <span id="timerDisplay" class="timer-text">--:--</span>
                    </div>
                </div>

                <div class="otp-actions">
                    <button type="submit" name="reset_password_submit" class="forgot-btn" id="resetBtn">Đổi mật khẩu</button>
                    <a href="?cancel_otp=1" class="btn-outline-secondary">← Hủy</a>
                </div>

                <div class="text-center mt-3">
                    <a href="?resend=1" id="resendLink" style="color: #ff6600;">⟳ Gửi lại mã OTP</a>
                </div>
            </form>
        <?php endif; ?>

        <div class="back-link">
            <a href="login.php"><i class="fas fa-arrow-left me-2"></i>Quay lại đăng nhập</a>
        </div>
    </div>
</div>

<?php if ($show_otp_form && $expiry_time !== null): ?>
    <script>
        // Đếm ngược thời gian OTP
        const expiryTime = <?php echo $expiry_time; ?> * 1000;

        function updateTimer() {
            const now = new Date().getTime();
            const distance = expiryTime - now;

            const timerDisplay = document.getElementById('timerDisplay');
            const resetBtn = document.getElementById('resetBtn');
            const resendLink = document.getElementById('resendLink');
            const otpInput = document.getElementById('otp_code');

            if (distance <= 0) {
                timerDisplay.innerHTML = 'ĐÃ HẾT HẠN';
                timerDisplay.className = 'timer-expired';
                if (resetBtn) {
                    resetBtn.disabled = true;
                    resetBtn.style.opacity = '0.5';
                    resetBtn.style.cursor = 'not-allowed';
                }
                if (otpInput) {
                    otpInput.disabled = true;
                    otpInput.style.opacity = '0.5';
                }
                if (resendLink) {
                    resendLink.style.display = 'inline-block';
                }
                return;
            }

            const minutes = Math.floor(distance / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            timerDisplay.innerHTML = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            timerDisplay.className = 'timer-text';
        }

        setInterval(updateTimer, 1000);
        updateTimer();
    </script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>