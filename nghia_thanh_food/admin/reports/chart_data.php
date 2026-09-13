<?php
require_once __DIR__ . '/../includes/config.php';

if (!isAdminLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$db = getDB();

// Doanh thu chart theo ngày
$sql_revenue = "SELECT 
                    DATE(ngay_dat) as ngay,
                    COALESCE(SUM(tong_thanh_toan), 0) as tong
                FROM donhang
                WHERE trang_thai = 'hoan_thanh'
                AND DATE(ngay_dat) BETWEEN :from AND :to
                GROUP BY DATE(ngay_dat)
                ORDER BY ngay";
$stmt = $db->prepare($sql_revenue);
$stmt->execute([':from' => $from, ':to' => $to]);
$revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Order status chart
$status_list = [
    'cho_xac_nhan' => 'Chờ xác nhận',
    'dang_xu_ly' => 'Đang xử lý',
    'dang_giao' => 'Đang giao',
    'hoan_thanh' => 'Hoàn thành',
    'da_huy' => 'Đã hủy'
];

$status_labels = [];
$status_values = [];

foreach ($status_list as $key => $label) {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM donhang WHERE trang_thai = :status AND DATE(ngay_dat) BETWEEN :from AND :to");
    $stmt->execute([':status' => $key, ':from' => $from, ':to' => $to]);
    $count = $stmt->fetch()['total'];

    $status_labels[] = $label;
    $status_values[] = $count;
}

echo json_encode([
    'revenue' => [
        'labels' => array_column($revenue, 'ngay'),
        'values' => array_column($revenue, 'tong')
    ],
    'orderStatus' => [
        'labels' => $status_labels,
        'values' => $status_values
    ]
]);
