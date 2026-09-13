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

// ========== KIỂM TRA XEM CÓ PHẢI TỪ TRANG KHÁC CHUYỂN SANG KHÔNG ==========
$referrer = $_SERVER['HTTP_REFERER'] ?? '';

if (!isset($_SESSION['register_step']) || $_SESSION['register_step'] != 'otp') {
    $is_from_register = (strpos($referrer, 'register.php') !== false);

    if (!$is_from_register && !empty($referrer)) {
        unset($_SESSION['form_temp']);
        unset($_SESSION['form_temp_time']);
    }

    if (empty($referrer)) {
        unset($_SESSION['form_temp']);
        unset($_SESSION['form_temp_time']);
    }
}
// ========== KẾT THÚC KIỂM TRA ==========

// Xử lý hủy OTP
if (isset($_GET['cancel_otp'])) {
    unset($_SESSION['temp_register']);
    unset($_SESSION['register_step']);
    unset($_SESSION['form_temp']);
    unset($_SESSION['form_temp_time']);
    redirect('/public/pages/register.php');
}

// Xóa OTP cũ hết hạn
$db->prepare("DELETE FROM maotp WHERE thoi_gian_het_han < datetime('now', 'localtime')")->execute();

// ========== KIỂM TRA OTP CÒN HIỆU LỰC ==========
$step = 'form';
$expiry_time = null;

if (isset($_SESSION['register_step']) && $_SESSION['register_step'] == 'otp') {
    if (!isset($_SESSION['temp_register']) || empty($_SESSION['temp_register']['email'])) {
        unset($_SESSION['register_step']);
        $step = 'form';
    } else {
        $email = $_SESSION['temp_register']['email'];
        $stmt = $db->prepare("
            SELECT thoi_gian_het_han FROM maotp 
            WHERE email = ? AND muc_dich = 'register' AND da_su_dung = 0 
            AND thoi_gian_het_han > datetime('now', 'localtime')
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$email]);
        $otp_valid = $stmt->fetch();

        if ($otp_valid) {
            $step = 'otp';
            $expiry_time = strtotime($otp_valid['thoi_gian_het_han']);
        } else {
            // OTP đã hết hạn
            unset($_SESSION['temp_register']);
            unset($_SESSION['register_step']);
            $step = 'form';
            $error = '⏰ Mã OTP đã hết hạn. Vui lòng đăng ký lại.';
        }
    }
}
// ========== KẾT THÚC KIỂM TRA ==========

// Xử lý đăng ký bước 1: Gửi OTP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['verify_otp'])) {
    $ho_ten = safeInput($_POST['ho_ten']);
    $email = safeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $dien_thoai = safeInput($_POST['dien_thoai']);
    $dia_chi = safeInput($_POST['dia_chi']);

    // Lưu tạm dữ liệu
    $form_data = [
        'ho_ten' => $ho_ten,
        'email' => $email,
        'dien_thoai' => $dien_thoai,
        'dia_chi' => $dia_chi
    ];
    $_SESSION['form_temp'] = $form_data;
    $_SESSION['form_temp_time'] = time();

    // Validate
    $has_error = false;
    if (empty($ho_ten) || empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
        $has_error = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
        $has_error = true;
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
        $has_error = true;
    } elseif ($password != $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
        $has_error = true;
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email đã được đăng ký';
            $has_error = true;
        }
    }

    if ($has_error) {
        $step = 'form';
    } else {
        // Tạo OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $db->prepare("DELETE FROM maotp WHERE email = ? AND muc_dich = 'register'")->execute([$email]);

        $stmt = $db->prepare("INSERT INTO maotp (email, ma_otp, muc_dich, thoi_gian_het_han, ngay_tao) VALUES (?, ?, 'register', ?, datetime('now', 'localtime'))");
        $stmt->execute([$email, $otp, $expires_at]);

        $_SESSION['temp_register'] = [
            'ho_ten' => $ho_ten,
            'email' => $email,
            'mat_khau' => password_hash($password, PASSWORD_DEFAULT),
            'dien_thoai' => $dien_thoai,
            'dia_chi' => $dia_chi
        ];

        unset($_SESSION['form_temp']);
        unset($_SESSION['form_temp_time']);

        if (sendOTP($email, $otp)) {
            $_SESSION['register_step'] = 'otp';
            $step = 'otp';
            $expiry_time = strtotime($expires_at);
            $success = "📧 Mã OTP đã gửi đến email $email. Vui lòng kiểm tra hộp thư (cả spam).";
        } else {
            $error = "Không thể gửi email xác thực. Vui lòng thử lại sau.";
            $db->prepare("DELETE FROM maotp WHERE email = ? AND muc_dich = 'register'")->execute([$email]);
            unset($_SESSION['temp_register']);
            $step = 'form';
        }
    }
}

// Xử lý xác thực OTP - PHÂN BIỆT RÕ SAI VÀ HẾT HẠN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_otp'])) {
    $otp_input = trim($_POST['otp_code'] ?? '');
    $email = $_SESSION['temp_register']['email'] ?? '';

    if (empty($otp_input)) {
        $error = 'Vui lòng nhập mã OTP';
    } else {
        // Kiểm tra OTP có tồn tại và đúng không (chưa quan tâm hết hạn trước)
        $stmt = $db->prepare("
            SELECT * FROM maotp 
            WHERE email = ? AND ma_otp = ? AND muc_dich = 'register' AND da_su_dung = 0
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$email, $otp_input]);
        $otp_record = $stmt->fetch();

        if (!$otp_record) {
            // Không tìm thấy OTP nào khớp
            $error = '❌ Mã OTP không đúng. Vui lòng thử lại.';
        } else {
            // Có OTP khớp, kiểm tra hết hạn
            $now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
            $expires = new DateTime($otp_record['thoi_gian_het_han'], new DateTimeZone('Asia/Ho_Chi_Minh'));

            if ($expires < $now) {
                // OTP đã hết hạn
                $error = '⏰ Mã OTP đã hết hạn. Vui lòng nhấn "Gửi lại mã OTP" để nhận mã mới.';
                // Đánh dấu OTP đã hết hạn (không dùng được nữa)
                $db->prepare("UPDATE maotp SET da_su_dung = 1 WHERE id = ?")->execute([$otp_record['id']]);
            } else {
                // OTP đúng và còn hạn -> xác thực thành công
                $db->prepare("UPDATE maotp SET da_su_dung = 1 WHERE id = ?")->execute([$otp_record['id']]);

                $temp = $_SESSION['temp_register'];
                $now = date('Y-m-d H:i:s');

                $stmt = $db->prepare("
                    INSERT INTO users 
                    (ho_ten, email, mat_khau, dien_thoai, dia_chi, email_verified, vai_tro, ngay_tao, ngay_cap_nhat) 
                    VALUES (?, ?, ?, ?, ?, 1, 0, ?, ?)
                ");
                $result = $stmt->execute([$temp['ho_ten'], $temp['email'], $temp['mat_khau'], $temp['dien_thoai'], $temp['dia_chi'], $now, $now]);

                if ($result) {
                    unset($_SESSION['temp_register']);
                    unset($_SESSION['register_step']);
                    unset($_SESSION['form_temp']);
                    unset($_SESSION['form_temp_time']);

                    header('Location: login.php?success=registered');
                    exit();
                } else {
                    $error = 'Lỗi tạo tài khoản. Vui lòng thử lại.';
                }
            }
        }
    }

    // Sau khi xử lý xong (dù sai hay hết hạn), vẫn giữ lại expiry_time từ database nếu còn OTP hợp lệ
    if ($step == 'otp') {
        $stmt = $db->prepare("
            SELECT thoi_gian_het_han FROM maotp 
            WHERE email = ? AND muc_dich = 'register' AND da_su_dung = 0 
            AND thoi_gian_het_han > datetime('now', 'localtime')
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$_SESSION['temp_register']['email'] ?? '']);
        $otp_valid = $stmt->fetch();
        if ($otp_valid) {
            $expiry_time = strtotime($otp_valid['thoi_gian_het_han']);
        }
    }
}

// Gửi lại OTP
if (isset($_GET['resend']) && isset($_SESSION['temp_register'])) {
    $email = $_SESSION['temp_register']['email'];
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    $db->prepare("DELETE FROM maotp WHERE email = ? AND muc_dich = 'register'")->execute([$email]);
    $stmt = $db->prepare("INSERT INTO maotp (email, ma_otp, muc_dich, thoi_gian_het_han, ngay_tao) VALUES (?, ?, 'register', ?, datetime('now', 'localtime'))");
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

// Hiển thị thông báo từ resend
if (isset($_SESSION['resend_success'])) {
    $success = $_SESSION['resend_success'];
    unset($_SESSION['resend_success']);
}
if (isset($_SESSION['resend_error'])) {
    $error = $_SESSION['resend_error'];
    unset($_SESSION['resend_error']);
}

$page_title = 'Đăng ký';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<style>
    .register-page {
        min-height: 100vh;
        background: linear-gradient(rgba(0, 0, 0, 0.25), rgba(0, 0, 0, 0.25)), url('<?php echo BASE_URL; ?>/public/assets/images/login-bg.jpg');
        background-size: cover;
        background-position: center;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 30px 15px;
    }

    .register-box {
        width: 100%;
        max-width: 460px;
        background: rgba(255, 255, 255, 0.30);
        backdrop-filter: blur(14px);
        border-radius: 28px;
        padding: 28px 28px 24px;
        position: relative;
        border: 1px solid rgba(255, 255, 255, 0.35);
        box-shadow: 0 10px 35px rgba(0, 0, 0, 0.18);
    }

    .register-logo {
        width: 82px;
        height: 82px;
        border-radius: 50%;
        overflow: hidden;
        position: absolute;
        top: -30px;
        left: 50%;
        transform: translateX(-50%);
        border: 4px solid #fff;
        background: #fff;
        box-shadow: 0 5px 18px rgba(0, 0, 0, 0.15);
        z-index: 5;
    }

    .register-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .register-title {
        text-align: center;
        margin-top: 28px;
        margin-bottom: 18px;
    }

    .register-title h2 {
        font-size: 30px;
        font-weight: 800;
        color: #0b5d39;
        margin-bottom: 8px;
    }

    .register-title p {
        color: #555;
        margin: 0;
    }

    .form-label {
        font-weight: 600;
        color: #0b5d39;
        margin-bottom: 8px;
    }

    .form-control {
        height: 46px;
        border-radius: 12px;
        border: none;
        background: rgba(255, 255, 255, 0.9);
        padding: 0 16px;
        font-size: 14px;
    }

    textarea.form-control {
        height: auto;
        padding-top: 14px;
    }

    .form-control:focus {
        box-shadow: none;
        border: 2px solid #2f9e44;
        background: #fff;
    }

    .register-btn {
        width: 100%;
        height: 48px;
        border: none;
        border-radius: 50px;
        background: linear-gradient(90deg, #78c841, #005f3c);
        color: #fff;
        font-size: 16px;
        font-weight: 700;
        margin-top: 10px;
        transition: 0.3s;
    }

    .register-btn:hover {
        transform: translateY(-2px);
        opacity: 0.95;
    }

    .bottom-link {
        text-align: center;
        margin-top: 22px;
        font-size: 15px;
    }

    .bottom-link a {
        color: #ff6600;
        font-weight: 700;
        text-decoration: none;
    }

    .alert {
        border-radius: 14px;
        border: none;
    }

    .otp-input {
        text-align: center;
        font-size: 28px;
        letter-spacing: 10px;
        font-weight: bold;
    }

    .btn-outline-secondary {
        border-radius: 50px;
        padding: 10px 20px;
        font-weight: 600;
    }

    .otp-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .otp-actions .register-btn {
        flex: 2;
    }

    .otp-actions .btn-outline-secondary {
        flex: 1;
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
</style>

<div class="register-page">
    <div class="register-box">
        <div class="register-logo">
            <img src="<?php echo BASE_URL; ?>/public/assets/images/leaf.png" alt="">
        </div>

        <?php if ($step == 'form'): ?>
            <div class="register-title">
                <h2>Đăng ký</h2>
                <p>Tạo tài khoản để mua sắm dễ dàng hơn</p>
            </div>
        <?php else: ?>
            <div class="register-title">
                <h2>Xác thực OTP</h2>
                <p>Nhập mã xác thực đã được gửi đến</p>
                <p><strong><?php echo htmlspecialchars($_SESSION['temp_register']['email'] ?? ''); ?></strong></p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($step == 'form'): ?>
            <!-- FORM ĐĂNG KÝ -->
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Họ và tên</label>
                    <input type="text" name="ho_ten" class="form-control" placeholder="Nhập họ tên..."
                        value="<?php
                                if (isset($_SESSION['temp_register']['ho_ten'])) {
                                    echo htmlspecialchars($_SESSION['temp_register']['ho_ten']);
                                } elseif (isset($_SESSION['form_temp']['ho_ten'])) {
                                    echo htmlspecialchars($_SESSION['form_temp']['ho_ten']);
                                }
                                ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="Nhập email..."
                        value="<?php
                                if (isset($_SESSION['temp_register']['email'])) {
                                    echo htmlspecialchars($_SESSION['temp_register']['email']);
                                } elseif (isset($_SESSION['form_temp']['email'])) {
                                    echo htmlspecialchars($_SESSION['form_temp']['email']);
                                }
                                ?>" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Mật khẩu</label>
                        <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Xác nhận</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="dien_thoai" class="form-control" placeholder="Nhập số điện thoại..."
                        value="<?php
                                if (isset($_SESSION['temp_register']['dien_thoai'])) {
                                    echo htmlspecialchars($_SESSION['temp_register']['dien_thoai']);
                                } elseif (isset($_SESSION['form_temp']['dien_thoai'])) {
                                    echo htmlspecialchars($_SESSION['form_temp']['dien_thoai']);
                                }
                                ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Địa chỉ</label>
                    <textarea name="dia_chi" class="form-control" rows="3" placeholder="Nhập địa chỉ..."><?php
                                                                                                            if (isset($_SESSION['temp_register']['dia_chi'])) {
                                                                                                                echo htmlspecialchars($_SESSION['temp_register']['dia_chi']);
                                                                                                            } elseif (isset($_SESSION['form_temp']['dia_chi'])) {
                                                                                                                echo htmlspecialchars($_SESSION['form_temp']['dia_chi']);
                                                                                                            }
                                                                                                            ?></textarea>
                </div>

                <button type="submit" class="register-btn">Đăng ký tài khoản</button>
            </form>

            <div class="bottom-link">
                Đã có tài khoản?
                <a href="<?php echo BASE_URL; ?>/public/pages/login.php">Đăng nhập ngay</a>
            </div>

        <?php else: ?>
            <!-- FORM NHẬP OTP -->
            <form method="POST" id="otpForm">
                <div class="mb-3">
                    <label class="form-label">Mã OTP (6 số)</label>
                    <input type="text" name="otp_code" id="otp_code" class="form-control otp-input" maxlength="6" pattern="[0-9]{6}" placeholder="______" required autofocus>
                    <div class="otp-timer" id="otpTimer">
                        <span id="timerLabel">⏱️ Mã OTP có hiệu lực còn: </span>
                        <span id="timerDisplay" class="timer-text">--:--</span>
                    </div>
                    <small class="form-text text-muted d-block mt-1 text-center">
                        Kiểm tra cả hộp thư Spam nếu không thấy email
                    </small>
                </div>

                <div class="otp-actions">
                    <button type="submit" name="verify_otp" class="register-btn" id="verifyBtn">Xác thực</button>
                    <a href="?cancel_otp=1" class="btn btn-outline-secondary" style="display: flex; align-items: center; justify-content: center; text-decoration: none; background: rgba(255,255,255,0.9); color: #0b5d39; border: 1px solid #0b5d39;">← Quay lại</a>
                </div>

                <div class="text-center mt-3">
                    <a href="?resend=1" id="resendLink" class="text-decoration-none" style="color: #ff6600;">⟳ Gửi lại mã OTP</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($step == 'otp' && $expiry_time !== null): ?>
    <script>
        // Đếm ngược thời gian OTP
        const expiryTime = <?php echo $expiry_time; ?> * 1000;

        function updateTimer() {
            const now = new Date().getTime();
            const distance = expiryTime - now;

            const timerDisplay = document.getElementById('timerDisplay');
            const verifyBtn = document.getElementById('verifyBtn');
            const resendLink = document.getElementById('resendLink');
            const otpInput = document.getElementById('otp_code');

            if (distance <= 0) {
                timerDisplay.innerHTML = 'ĐÃ HẾT HẠN';
                timerDisplay.className = 'timer-expired';
                verifyBtn.disabled = true;
                verifyBtn.style.opacity = '0.5';
                verifyBtn.style.cursor = 'not-allowed';
                otpInput.disabled = true;
                otpInput.style.opacity = '0.5';
                resendLink.style.display = 'inline-block';

                const timerDiv = document.getElementById('otpTimer');
                if (!document.getElementById('expiredMsg')) {
                    const expiredMsg = document.createElement('div');
                    expiredMsg.className = 'alert alert-danger mt-2';
                    expiredMsg.style.fontSize = '12px';
                    expiredMsg.style.padding = '8px';
                    expiredMsg.id = 'expiredMsg';
                    expiredMsg.innerHTML = '⚠️ Mã OTP đã hết hạn. Vui lòng nhấn "Gửi lại mã OTP" để nhận mã mới.';
                    timerDiv.parentNode.insertBefore(expiredMsg, timerDiv.nextSibling);
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