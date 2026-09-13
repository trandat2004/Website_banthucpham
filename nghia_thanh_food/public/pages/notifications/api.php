<?php
require_once '../../includes/config.php';

if (! isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db      = getDB();
$user_id = $_SESSION['user_id'];
$action  = $_GET['action'] ?? $_POST['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    // Lấy danh sách thông báo cho dropdown
    case 'get':
        $stmt = $db->prepare("
            SELECT id, tieu_de, noi_dung, link, da_xem, ngay_tao, loai, tham_chieu_id,hinh_anh
            FROM thongbao
            WHERE user_id = ?
            ORDER BY CASE WHEN da_xem = 0 THEN 0 ELSE 1 END, ngay_tao DESC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Đếm số chưa đọc
        $stmt2 = $db->prepare("SELECT COUNT(*) FROM thongbao WHERE user_id = ? AND da_xem = 0");
        $stmt2->execute([$user_id]);
        $unread_count = $stmt2->fetchColumn();

        echo json_encode([
            'success'       => true,
            'unread_count'  => $unread_count,
            'notifications' => array_map(function ($n) {
                return [
                    'id'        => $n['id'],
                    'tieu_de'   => $n['tieu_de'],
                    'noi_dung'  => mb_substr($n['noi_dung'], 0, 80) . (mb_strlen($n['noi_dung']) > 80 ? '...' : ''),
                    'link'      => $n['link'],
                    'da_xem'    => $n['da_xem'],
                    'loai'      => $n['loai'],
                    'hinh_anh'  => $n['hinh_anh'],
                    'thoi_gian' => time_ago($n['ngay_tao']),
                ];
            }, $notifications),
        ]);
        break;

    // Đánh dấu một thông báo đã đọc
    case 'mark_read':
        $input    = json_decode(file_get_contents('php://input'), true);
        $notif_id = $input['id'] ?? 0;

        $stmt = $db->prepare("UPDATE thongbao SET da_xem = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notif_id, $user_id]);

        echo json_encode(['success' => true]);
        break;

    // Đánh dấu tất cả đã đọc
    case 'mark_all_read':
        $stmt = $db->prepare("UPDATE thongbao SET da_xem = 1 WHERE user_id = ? AND da_xem = 0");
        $stmt->execute([$user_id]);

        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}

function time_ago($datetime)
{
    $timestamp = strtotime($datetime);
    $diff      = time() - $timestamp;

    if ($diff < 60) {
        return 'Vài giây trước';
    }

    if ($diff < 3600) {
        return floor($diff / 60) . ' phút trước';
    }

    if ($diff < 86400) {
        return floor($diff / 3600) . ' giờ trước';
    }

    if ($diff < 604800) {
        return floor($diff / 86400) . ' ngày trước';
    }

    return date('d/m/Y', $timestamp);
}
