<?php
session_start();

unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_role']);

/* CHUYỂN HƯỚNG VỀ TRANG ĐĂNG NHẬP ADMIN */
header("Location: login.php");
exit();
?>