<?php

require_once dirname(__DIR__, 2) . '/public/includes/config.php';

if(isAdmin()) {
    redirect('/admin/index.php');
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = safeInput($_POST['email']);
    $password = $_POST['password'];

    $db = getDB();

    $stmt = $db->prepare("
        SELECT *
        FROM users
        WHERE email = ?
        AND vai_tro = 1
        AND trang_thai = 1
    ");

    $stmt->execute([$email]);

    $admin = $stmt->fetch();

    if($admin && password_verify($password, $admin['mat_khau'])) {

        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['ho_ten'];
        $_SESSION['admin_role'] = $admin['vai_tro'];

        redirect('/admin/index.php');

    } else {

        $error = 'Email hoặc mật khẩu không đúng!';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Đăng nhập quản trị</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    >

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{

            min-height:100vh;

            font-family:'Poppins',sans-serif;

            background:
                linear-gradient(
                    135deg,
                    rgba(15,23,42,.95),
                    rgba(22,101,52,.95)
                ),
                url('https://images.unsplash.com/photo-1556740749-887f6717d7e4?q=80&w=1974&auto=format&fit=crop');

            background-size:cover;
            background-position:center;

            overflow:hidden;

            position:relative;
        }

        body::before{

            content:'';

            position:absolute;

            width:500px;
            height:500px;

            background:rgba(34,197,94,.15);

            border-radius:50%;

            top:-150px;
            right:-120px;

            filter:blur(20px);
        }

        body::after{

            content:'';

            position:absolute;

            width:400px;
            height:400px;

            background:rgba(255,255,255,.08);

            border-radius:50%;

            bottom:-120px;
            left:-100px;

            filter:blur(10px);
        }

        .login-wrapper{

            min-height:100vh;

            display:flex;
            align-items:center;
            justify-content:center;

            padding:30px;

            position:relative;
            z-index:10;
        }

        .login-card{

            width:100%;
            max-width:470px;

            background:rgba(255,255,255,.12);

            backdrop-filter:blur(18px);

            border:1px solid rgba(255,255,255,.15);

            border-radius:32px;

            padding:40px;

            box-shadow:0 20px 60px rgba(0,0,0,.35);
        }

        .brand-logo{

            width:85px;
            height:85px;

            border-radius:24px;

            margin:auto;

            background:linear-gradient(135deg,#22c55e,#15803d);

            display:flex;
            align-items:center;
            justify-content:center;

            color:#fff;

            font-size:34px;

            box-shadow:0 10px 25px rgba(34,197,94,.35);
        }

        .login-title{

            font-size:34px;
            font-weight:800;

            color:#fff;

            margin-top:25px;
            margin-bottom:8px;

            text-align:center;
        }

        .login-sub{

            text-align:center;

            color:rgba(255,255,255,.75);

            margin-bottom:35px;

            font-size:15px;
        }

        .form-label{

            color:#fff;

            font-weight:600;

            margin-bottom:10px;
        }

        .input-group{

            background:rgba(255,255,255,.08);

            border:1px solid rgba(255,255,255,.12);

            border-radius:18px;

            overflow:hidden;

            margin-bottom:22px;
        }

        .input-group-text{

            background:transparent;

            border:none;

            color:rgba(255,255,255,.75);

            padding-left:18px;
        }

        .form-control{

            background:transparent !important;

            border:none !important;

            color:#fff !important;

            height:58px;

            font-size:15px;

            box-shadow:none !important;
        }

        .form-control::placeholder{

            color:rgba(255,255,255,.45);
        }

        .btn-login{

            width:100%;

            height:58px;

            border:none;

            border-radius:18px;

            background:linear-gradient(
                135deg,
                #22c55e,
                #15803d
            );

            color:#fff;

            font-size:16px;
            font-weight:700;

            transition:.3s;

            margin-top:10px;
        }

        .btn-login:hover{

            transform:translateY(-2px);

            box-shadow:0 12px 25px rgba(34,197,94,.35);
        }

        .alert-custom{

            background:rgba(239,68,68,.15);

            border:1px solid rgba(239,68,68,.25);

            color:#fff;

            border-radius:16px;

            padding:14px 18px;

            margin-bottom:22px;

            font-size:14px;
        }

        .bottom-text{

            text-align:center;

            margin-top:28px;

            color:rgba(255,255,255,.55);

            font-size:14px;
        }

        @media(max-width:576px){

            .login-card{

                padding:28px;
            }

            .login-title{

                font-size:28px;
            }
        }

    </style>

</head>

<body>

    <div class="login-wrapper">

        <div class="login-card">

            <div class="brand-logo">

                <i class="fas fa-store"></i>

            </div>

            <div class="login-title">

                Nghĩa Thành Food

            </div>

            <div class="login-sub">

                Hệ thống quản trị website thương mại điện tử

            </div>

            <?php if($error): ?>

                <div class="alert-custom">

                    <i class="fas fa-circle-exclamation me-2"></i>

                    <?php echo $error; ?>

                </div>

            <?php endif; ?>

            <form method="POST">

                <label class="form-label">
                    Email quản trị
                </label>

                <div class="input-group">

                    <span class="input-group-text">

                        <i class="fas fa-envelope"></i>

                    </span>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        placeholder="Nhập email quản trị..."
                        required
                    >

                </div>

                <label class="form-label">
                    Mật khẩu
                </label>

                <div class="input-group">

                    <span class="input-group-text">

                        <i class="fas fa-lock"></i>

                    </span>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="Nhập mật khẩu..."
                        required
                    >

                </div>

                <button
                    type="submit"
                    class="btn-login"
                >

                    <i class="fas fa-right-to-bracket me-2"></i>

                    Đăng nhập quản trị

                </button>

            </form>

            <div class="bottom-text">

                © <?php echo date('Y'); ?> Nghĩa Thành Food Admin Panel

            </div>

        </div>

    </div>

</body>

</html>