<?php
require_once '../includes/config.php';

$db = getDB();

$id = intval($_GET['id']);

$stmt = $db->prepare("
    DELETE FROM tintuc
    WHERE id = ?
");

$stmt->execute([$id]);

// ✅ THÊM DÒNG NÀY
$_SESSION['news_success'] = 'Xóa tin tức thành công!';

header("Location: list.php");
exit;

