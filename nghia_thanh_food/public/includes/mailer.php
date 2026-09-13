<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

function sendOTP($email, $otp)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;


        $mail->Username = 'nghiathanhfood4@gmail.com';

        // 🔴 APP PASSWORD 
        $mail->Password = 'xrqu flsp xxpp tjid';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->CharSet = 'UTF-8';

        $mail->setFrom('nghiathanhfood4@gmail.com', 'Nghia Thanh Food');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Mã OTP xác thực tài khoản';

        $mail->Body = "
            <div style='font-family:Arial'>
                <h2>Mã OTP xác thực</h2>
                <p>Mã OTP của bạn là:</p>
                <h1 style='color:#2f9e44'>$otp</h1>
                <p>Mã có hiệu lực trong 1 phút.</p>
            </div>
        ";

        return $mail->send();
    } catch (Exception $e) {
        error_log("MAIL ERROR: " . $mail->ErrorInfo);
        return false;
    }
}
