<?php

require_once __DIR__ . '/../includes/check_auth.php';

require_once '../includes/config.php';

$db = getDB();

$id = intval($_GET['id'] ?? 0);

if ($id) {

    $stmt = $db->prepare("
        DELETE FROM khuyenmai
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $_SESSION['success_message'] = 'Đã xóa khuyến mãi!';
}

redirect('/admin/promotions/list.php');
