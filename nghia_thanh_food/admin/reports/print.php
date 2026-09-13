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

$status_class = [
    'cho_xac_nhan' => 'bg-warning text-dark',
    'dang_xu_ly' => 'bg-info text-white',
    'dang_giao' => 'bg-primary text-white',
    'hoan_thanh' => 'bg-success text-white',
    'da_huy' => 'bg-danger text-white'
];

// Lấy dữ liệu
$stmt = $db->prepare("SELECT COALESCE(SUM(tong_thanh_toan),0) as total FROM donhang WHERE trang_thai = 'hoan_thanh' AND DATE(ngay_dat) BETWEEN :from AND :to");
$stmt->execute([':from' => $from, ':to' => $to]);
$doanhthu = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM donhang WHERE DATE(ngay_dat) BETWEEN :from AND :to");
$stmt->execute([':from' => $from, ':to' => $to]);
$tongdon = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE vai_tro = 0");
$stmt->execute();
$khachhang = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM donhang WHERE trang_thai = 'cho_xac_nhan' AND DATE(ngay_dat) BETWEEN :from AND :to");
$stmt->execute([':from' => $from, ':to' => $to]);
$don_choxuly = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM donhang WHERE trang_thai = 'da_huy' AND DATE(ngay_dat) BETWEEN :from AND :to");
$stmt->execute([':from' => $from, ':to' => $to]);
$don_huy = $stmt->fetch()['total'];

$tyle_huy = $tongdon > 0 ? round(($don_huy / $tongdon) * 100, 1) : 0;

// Top sản phẩm
$sql_top = "SELECT 
                sanpham.ten_san_pham,
                SUM(chitietdonhang.so_luong) as da_ban
            FROM chitietdonhang
            JOIN sanpham ON chitietdonhang.san_pham_id = sanpham.id
            JOIN donhang ON chitietdonhang.don_hang_id = donhang.id
            WHERE donhang.trang_thai = 'hoan_thanh'
            AND DATE(donhang.ngay_dat) BETWEEN :from AND :to
            GROUP BY sanpham.id
            ORDER BY da_ban DESC
            LIMIT 10";
$stmt = $db->prepare($sql_top);
$stmt->execute([':from' => $from, ':to' => $to]);
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Đơn hàng
$stmt = $db->prepare("SELECT ma_don_hang, ho_ten_nguoi_nhan, tong_thanh_toan, trang_thai, ngay_dat 
                      FROM donhang 
                      WHERE DATE(ngay_dat) BETWEEN :from AND :to 
                      ORDER BY ngay_dat DESC 
                      LIMIT 50");
$stmt->execute([':from' => $from, ':to' => $to]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>In báo cáo - Nghĩa Thành Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 0;
                padding: 20px;
            }

            .card {
                border: 1px solid #ddd;
                box-shadow: none;
            }
        }

        body {
            background: white;
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        .print-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2e7d32;
            padding-bottom: 15px;
        }

        .print-title {
            font-size: 28px;
            font-weight: bold;
            color: #2e7d32;
        }

        .kpi-print {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            gap: 15px;
        }

        .kpi-item {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            background: #f9f9f9;
        }

        .kpi-number {
            font-size: 24px;
            font-weight: bold;
            color: #2e7d32;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #2e7d32;
            color: white;
        }

        .section-title {
            color: #2e7d32;
            margin: 20px 0 10px 0;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #999;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
    </style>
</head>

<body>
    <div class="no-print mb-3">
        <button class="btn btn-primary" onclick="window.print();">🖨️ In báo cáo</button>
        <button class="btn btn-secondary" onclick="window.close();">Đóng</button>
    </div>

    <div class="print-header">
        <div class="print-title">🍜 Nghĩa Thành Food</div>
        <div>Báo cáo hoạt động kinh doanh</div>
        <div>Từ: <?= date('d/m/Y', strtotime($from)) ?> - Đến: <?= date('d/m/Y', strtotime($to)) ?></div>
        <div>Ngày in: <?= date('d/m/Y H:i:s') ?></div>
    </div>

    <div class="kpi-print">
        <div class="kpi-item">
            <div>💰 Doanh thu</div>
            <div class="kpi-number"><?= formatPrice($doanhthu) ?></div>
        </div>
        <div class="kpi-item">
            <div>📦 Tổng đơn</div>
            <div class="kpi-number"><?= number_format($tongdon, 0, ',', '.') ?></div>
        </div>
        <div class="kpi-item">
            <div>👥 Khách hàng</div>
            <div class="kpi-number"><?= number_format($khachhang, 0, ',', '.') ?></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <div class="kpi-item">
                <div>⏳ Đơn chờ xử lý</div>
                <div class="kpi-number"><?= $don_choxuly ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="kpi-item">
                <div>❌ Đơn đã hủy</div>
                <div class="kpi-number"><?= $don_huy ?> (<?= $tyle_huy ?>%)</div>
            </div>
        </div>
    </div>

    <h4 class="section-title">🏆 Top sản phẩm bán chạy</h4>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tên sản phẩm</th>
                <th>Đã bán</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1;
            foreach ($topProducts as $p): ?>
                <tr>
                    <td><?= $i++ ?>.</td>
                    <td><?= htmlspecialchars($p['ten_san_pham']) ?></td>
                    <td><?= $p['da_ban'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 class="section-title">📋 Danh sách đơn hàng</h4>
    <table>
        <thead>
            <tr>
                <th>Mã đơn</th>
                <th>Khách hàng</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th>Ngày đặt</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $o):
                $display_status = $status_display[$o['trang_thai']] ?? $o['trang_thai'];
            ?>
                <tr>
                    <td><?= htmlspecialchars($o['ma_don_hang']) ?></td>
                    <td><?= htmlspecialchars($o['ho_ten_nguoi_nhan']) ?></td>
                    <td><?= formatPrice($o['tong_thanh_toan']) ?></td>
                    <td><?= $display_status ?></td>
                    <td><?= date('d/m/Y', strtotime($o['ngay_dat'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        Báo cáo được tạo bởi hệ thống Nghĩa Thành Food
    </div>
</body>

</html>