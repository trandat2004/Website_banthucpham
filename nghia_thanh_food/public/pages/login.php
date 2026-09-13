<?php
require_once '../includes/config.php';

if (isLoggedIn()) {
    redirect('/index.php');
}

$db = getDB();
$error = '';
$success_message = '';
$email = ''; // Lưu email để hiển thị lại

// ========== KIỂM TRA XEM CÓ PHẢI TỪ TRANG KHÁC CHUYỂN SANG KHÔNG ==========
// Lấy URL trang trước đó (referrer)
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
$current_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Nếu không phải từ chính trang đăng nhập (tức là từ trang khác nhảy sang)
$is_from_login = (strpos($referrer, 'login.php') !== false);

if (!$is_from_login && !empty($referrer)) {
    // Xóa dữ liệu email đã lưu
    unset($_SESSION['login_email_temp']);
}

// Nếu không có referrer (người dùng vào trực tiếp bằng URL)
if (empty($referrer)) {
    unset($_SESSION['login_email_temp']);
}
// ========== KẾT THÚC KIỂM TRA ==========

// Lấy thông báo thành công từ URL (chỉ hiển thị 1 lần)
if (isset($_GET['success']) && $_GET['success'] == 'registered') {
    $success_message = '✅ Đăng ký tài khoản thành công! Vui lòng đăng nhập.';
    // Xóa parameter success trên URL để không hiển thị lại khi refresh
    echo '<script>history.replaceState({}, "", "login.php");</script>';
}

// Lấy email đã lưu trong session (nếu có)
if (isset($_SESSION['login_email_temp'])) {
    $email = $_SESSION['login_email_temp'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = safeInput($_POST['email']);
    $password = $_POST['password'];

    // Lưu email vào session để hiển thị lại khi có lỗi
    $_SESSION['login_email_temp'] = $email;

    if (empty($email) || empty($password)) {
        $error = '❌ Vui lòng nhập đầy đủ thông tin';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND trang_thai = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // KIỂM TRA EMAIL ĐÃ XÁC THỰC CHƯA
            if ($user['email_verified'] == 0) {
                $error = '❌ Tài khoản chưa được xác thực email. Vui lòng kiểm tra hộp thư (cả spam) hoặc <a href="register.php">đăng ký lại</a>.';
            } elseif ($user['vai_tro'] == 0 && password_verify($password, $user['mat_khau'])) {
                // Đăng nhập thành công - XÓA dữ liệu tạm
                unset($_SESSION['login_email_temp']);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['ho_ten'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['vai_tro'];

                // LOAD GIỎ HÀNG CŨ
                if (isset($_SESSION['saved_carts'][$user['id']])) {
                    $_SESSION['cart'] = $_SESSION['saved_carts'][$user['id']];
                } else {
                    $_SESSION['cart'] = [];
                }

                if (isset($_SESSION['redirect_after_login'])) {
                    $redirect = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    redirect($redirect);
                } else {
                    redirect('/index.php');
                }
            } else {
                $error = '❌ Mật khẩu không đúng';
            }
        } else {
            $error = '❌ Email không tồn tại hoặc tài khoản bị khóa';
        }
    }
}

$page_title = 'Đăng nhập';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<style>
    .login-page {
        min-height: 100vh;
        background: url('<?php echo BASE_URL; ?>/public/assets/images/login-bg.jpg') center center/cover no-repeat;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 80px 15px;
        position: relative;
    }

    .login-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.25);
    }

    .login-box {
        width: 100%;
        max-width: 560px;
        background: rgba(255, 255, 255, 0.30);
        backdrop-filter: blur(12px);
        border-radius: 35px;
        padding: 70px 40px 35px;
        position: relative;
        margin-top: 30px;
        border: 1px solid rgba(255, 255, 255, 0.35);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.18);
        z-index: 2;
    }

    .login-leaf {
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

    .login-leaf img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .login-title {
        text-align: center;
        font-size: 2.2rem;
        font-weight: 800;
        color: #0b5d39;
        margin-bottom: 45px;
    }

    .login-form label {
        font-size: 1.2rem;
        font-weight: 600;
        color: #184d33;
        margin-bottom: 10px;
    }

    .login-input {
        height: 55px;
        border-radius: 18px;
        border: none;
        background: rgba(255, 255, 255, 0.85);
        padding: 0 60px 0 20px;
        font-size: 1.1rem;
        color: #14532d;
    }

    .login-input:focus {
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.2);
        outline: none;
    }

    .input-wrap {
        position: relative;
        margin-bottom: 30px;
    }

    .input-wrap i {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #0b5d39;
        font-size: 1.2rem;
    }

    .login-btn {
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

    .login-btn:hover {
        transform: translateY(-2px);
        opacity: 0.95;
    }

    .login-links {
        text-align: center;
        margin-top: 30px;
        font-size: 1.05rem;
    }

    .login-links a {
        color: #ff6b00;
        font-weight: 700;
        text-decoration: none;
    }

    .forgot-password {
        display: inline-block;
        margin-top: 12px;
        color: #0b5d39 !important;
        font-weight: 600 !important;
    }

    .alert {
        border-radius: 15px;
    }

    /* Alert động - tự động ẩn sau 5 giây */
    .alert-success {
        animation: fadeOut 5s ease forwards;
    }

    @keyframes fadeOut {
        0% {
            opacity: 1;
        }

        70% {
            opacity: 1;
        }

        100% {
            opacity: 0;
            visibility: hidden;
            display: none;
        }
    }

    @media(max-width:768px) {
        .login-box {
            padding: 80px 25px 40px;
        }

        .login-title {
            font-size: 2.1rem;
        }
    }
</style>

<div class="login-page">
    <div class="login-overlay"></div>
    <div class="login-box">
        <div class="login-leaf">
            <img src="<?php echo BASE_URL; ?>/public/assets/images/leaf.png" alt="">
        </div>

        <h1 class="login-title">Đăng nhập</h1>

        <!-- HIỂN THỊ THÔNG BÁO THÀNH CÔNG (chỉ hiển thị 1 lần) -->
        <?php if ($success_message): ?>
            <div class="alert alert-success" id="successAlert">
                <?php echo $success_message; ?>
            </div>
            <script>
                // Tự động ẩn thông báo thành công sau 5 giây
                setTimeout(function() {
                    var alert = document.getElementById('successAlert');
                    if (alert) {
                        alert.style.display = 'none';
                    }
                }, 5000);
            </script>
        <?php endif; ?>

        <!-- HIỂN THỊ THÔNG BÁO LỖI -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <label>Email</label>
            <div class="input-wrap">
                <input type="email" name="email" class="form-control login-input"
                    placeholder="Nhập email..."
                    value="<?php echo htmlspecialchars($email); ?>"
                    required>
                <i class="fas fa-user"></i>
            </div>

            <label>Password</label>
            <div class="input-wrap">
                <input type="password" name="password" class="form-control login-input"
                    placeholder="Nhập mật khẩu..."
                    value=""
                    required>
                <i class="fas fa-lock"></i>
            </div>

            <button type="submit" class="login-btn">
                Đăng nhập
            </button>
        </form>

        <div class="login-links">
            <p>
                Bạn chưa có tài khoản?
                <a href="<?php echo BASE_URL; ?>/public/pages/register.php">
                    Đăng ký ngay tại đây
                </a>
            </p>
            <a href="<?php echo BASE_URL; ?>/public/pages/forgot-password.php" class="forgot-password">Quên mật khẩu?</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>