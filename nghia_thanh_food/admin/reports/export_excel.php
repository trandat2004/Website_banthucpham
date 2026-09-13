<?php
require_once __DIR__ . '/../includes/config.php';

if (!isAdminLoggedIn()) {
    redirect('/admin/auth/login.php');
}

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$db = getDB();

// Map trạng thái
$status_display = [
    'cho_xac_nhan' => 'Chờ xác nhận',
    'dang_xu_ly' => 'Đang xử lý',
    'dang_giao' => 'Đang giao',
    'hoan_thanh' => 'Hoàn thành',
    'da_huy' => 'Đã hủy'
];

// Tạo file Excel sử dụng PHPSpreadsheet (nếu có) hoặc HTML + CSS
// Vì dễ dàng auto-fit, tôi sẽ dùng HTML với CSS để xuất Excel

$filename = "baocao_{$from}_{$to}.xls";
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// HTML với CSS để Excel auto-fit cột
echo '<html>';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<style>
    th { background-color: #2e7d32; color: white; padding: 10px; text-align: center; }
    td { padding: 8px; border: 1px solid #ddd; }
    table { border-collapse: collapse; width: 100%; }
    .title { font-size: 18px; font-weight: bold; color: #2e7d32; margin-bottom: 10px; }
    .subtitle { font-size: 14px; color: #666; margin-bottom: 20px; }
    .kpi-box { background: #f5f5f5; padding: 10px; margin-bottom: 20px; }
    @page { size: landscape; }
</style>';
echo '</head>';
echo '<body>';

// ========== TỔNG QUAN ==========
echo '<h2 class="title">📊 BÁO CÁO NGHĨA THÀNH FOOD</h2>';
echo '<div class="subtitle">Thời gian: ' . date('d/m/Y', strtotime($from)) . ' - ' . date('d/m/Y', strtotime($to)) . '</div>';
echo '<div class="subtitle">Ngày xuất: ' . date('d/m/Y H:i:s') . '</div>';

// Doanh thu
$stmt = $db->prepare("SELECT COALESCE(SUM(tong_thanh_toan),0) as total FROM donhang WHERE trang_thai = 'hoan_thanh' AND DATE(ngay_dat) BETWEEN :from AND :to");
$stmt->execute([':from' => $from, ':to' => $to]);
$doanhthu = $stmt->fetch()['total'];

// Tổng đơn
$stmt = $db->prepare("SELECT COUNT(*) as total FROM donhang WHERE DATE(ngay_dat) BETWEEN :from AND :to");
$stmt->execute([':from' => $from, ':to' => $to]);
$tongdon = $stmt->fetch()['total'];

// Khách hàng
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE vai_tro = 0");
$stmt->execute();
$khachhang = $stmt->fetch()['total'];

echo '<div class="kpi-box">';
echo '<table style="width: 100%;">';
echo '<tr style="background: none;">';
echo '<th style="background: #e8f5e9; color: #2e7d32;">📈 Doanh thu</th>';
echo '<th style="background: #e3f2fd; color: #1565c0;">📦 Tổng đơn</th>';
echo '<th style="background: #f3e5f5; color: #6a1b9a;">👥 Khách hàng</th>';
echo '</tr>';
echo '<tr style="text-align: center;">';
echo '<td style="font-size: 20px; font-weight: bold;">' . formatPrice($doanhthu) . '</td>';
echo '<td style="font-size: 20px; font-weight: bold;">' . number_format($tongdon, 0, ',', '.') . '</td>';
echo '<td style="font-size: 20px; font-weight: bold;">' . number_format($khachhang, 0, ',', '.') . '</td>';
echo '</tr>';
echo '</table>';
echo '</div>';

// ========== DANH SÁCH ĐƠN HÀNG ==========
echo '<h3 class="title" style="margin-top: 30px;">📋 DANH SÁCH ĐƠN HÀNG</h3>';
echo '<table>';
echo '<thead>';
echo '<tr>';
echo '<th>Mã đơn</th>';
echo '<th>Khách hàng</th>';
echo '<th>Email</th>';
echo '<th>Điện thoại</th>';
echo '<th>Địa chỉ</th>';
echo '<th>Tổng tiền</th>';
echo '<th>Trạng thái</th>';
echo '<th>Ngày đặt</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

$stmt = $db->prepare("SELECT ma_don_hang, ho_ten_nguoi_nhan, email_nguoi_nhan, dien_thoai_nguoi_nhan, dia_chi_giao_hang, tong_thanh_toan, trang_thai, ngay_dat 
                      FROM donhang 
                      WHERE DATE(ngay_dat) BETWEEN :from AND :to 
                      ORDER BY ngay_dat DESC");
$stmt->execute([':from' => $from, ':to' => $to]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($orders as $o) {
    $display_status = $status_display[$o['trang_thai']] ?? $o['trang_thai'];
    echo '<tr>';
    echo '<td>' . htmlspecialchars($o['ma_don_hang']) . '</td>';
    echo '<td>' . htmlspecialchars($o['ho_ten_nguoi_nhan']) . '</td>';
    echo '<td>' . htmlspecialchars($o['email_nguoi_nhan']) . '</td>';
    echo '<td>' . htmlspecialchars($o['dien_thoai_nguoi_nhan']) . '</td>';
    echo '<td>' . htmlspecialchars($o['dia_chi_giao_hang']) . '</td>';
    echo '<td style="text-align: right;">' . formatPrice($o['tong_thanh_toan']) . '</td>';
    echo '<td>' . $display_status . '</td>';
    echo '<td>' . date('d/m/Y', strtotime($o['ngay_dat'])) . '</td>';
    echo '</tr>';
}
echo '</tbody>';
echo '</table>';

// ========== TOP SẢN PHẨM ==========
echo '<h3 class="title" style="margin-top: 30px;">🏆 TOP SẢN PHẨM BÁN CHẠY</h3>';
echo '<table>';
echo '<thead>';
echo '<tr>';
echo '<th>STT</th>';
echo '<th>Tên sản phẩm</th>';
echo '<th>Danh mục</th>';
echo '<th>Số lượng bán</th>';
echo '<th>Doanh thu</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

$sql_top = "SELECT 
                sanpham.ten_san_pham,
                danhmuc.ten_danh_muc,
                COALESCE(SUM(chitietdonhang.so_luong), 0) as da_ban,
                COALESCE(SUM(chitietdonhang.thanh_tien), 0) as doanhthu
            FROM sanpham
            LEFT JOIN danhmuc ON sanpham.danh_muc_id = danhmuc.id
            LEFT JOIN chitietdonhang ON sanpham.id = chitietdonhang.san_pham_id
            LEFT JOIN donhang ON chitietdonhang.don_hang_id = donhang.id 
                AND donhang.trang_thai = 'hoan_thanh'
                AND DATE(donhang.ngay_dat) BETWEEN :from AND :to
            GROUP BY sanpham.id
            HAVING da_ban > 0
            ORDER BY da_ban DESC
            LIMIT 20";
$stmt = $db->prepare($sql_top);
$stmt->execute([':from' => $from, ':to' => $to]);
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stt = 1;
foreach ($topProducts as $p) {
    echo '<tr>';
    echo '<td>' . $stt++ . '</td>';
    echo '<td>' . htmlspecialchars($p['ten_san_pham']) . '</td>';
    echo '<td>' . htmlspecialchars($p['ten_danh_muc']) . '</td>';
    echo '<td style="text-align: center;">' . number_format($p['da_ban']) . '</td>';
    echo '<td style="text-align: right;">' . formatPrice($p['doanhthu']) . '</td>';
    echo '</tr>';
}
echo '</tbody>';
echo '</table>';

echo '</body>';
echo '</html>';
