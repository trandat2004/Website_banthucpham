<?php
session_start();

/*
|---------------------------------------------------
| LƯU GIỎ HÀNG THEO USER
|---------------------------------------------------
*/

if(
    isset($_SESSION['user_id']) &&
    isset($_SESSION['cart'])
){

    $_SESSION['saved_carts'][$_SESSION['user_id']]
        = $_SESSION['cart'];
}

/*
|---------------------------------------------------
| XÓA GIỎ HIỆN TẠI
|---------------------------------------------------
*/

$_SESSION['cart'] = [];

/*
|---------------------------------------------------
| XÓA USER
|---------------------------------------------------
*/

unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);
unset($_SESSION['user_role']);

/*
|---------------------------------------------------
| CHUYỂN HƯỚNG
|---------------------------------------------------
*/

header("Location: /public/pages/login.php");
exit();
?>
