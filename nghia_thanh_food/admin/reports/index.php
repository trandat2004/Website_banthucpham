<?php
require_once __DIR__ . '/../includes/config.php';

$from_date = date('Y-m-01');
$to_date = date('Y-m-d');
$db = getDB();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo & Thống kê - Nghĩa Thành Food</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script> <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            overflow-x: hidden;
        }

        /* Container chính - không dùng margin-left vì sidebar đã có col-md-2 */
        .reports-container {
            padding: 20px;
            width: 100%;
        }

        /* Report Header */
        .report-header {
            background: white;
            border-radius: 20px;
            padding: 20px 28px;
            margin-bottom: 28px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        /* Filter Bar */
        .filter-bar {
            background: white;
            border-radius: 20px;
            padding: 20px 24px;
            margin-bottom: 28px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        /* Card Stats */
        .card-stats {
            background: white;
            border-radius: 24px;
            padding: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0, 0, 0, 0.05);
            cursor: pointer;
        }

        .card-stats:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
        }

        .stats-icon {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
        }

        /* Chart Card */
        .chart-card {
            background: white;
            border-radius: 24px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        /* Buttons */
        .btn-filter {
            background: #2e7d32;
            border: none;
            border-radius: 40px;
            padding: 8px 28px;
            font-weight: 500;
        }

        .btn-filter:hover {
            background: #1b5e20;
        }

        .btn-outline-secondary {
            border-radius: 40px;
            margin-right: 8px;
        }

        /* Comparison */
        .comparison-up {
            color: #2e7d32;
            background: #e8f5e9;
            border-radius: 40px;
            padding: 4px 12px;
            display: inline-block;
            font-size: 12px;
            font-weight: 500;
        }

        .comparison-down {
            color: #c62828;
            background: #ffebee;
            border-radius: 40px;
            padding: 4px 12px;
            display: inline-block;
            font-size: 12px;
            font-weight: 500;
        }

        /* Status Badge */
        .status-badge {
            padding: 5px 12px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Top Product Image */
        .top-product-img {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 12px;
            background: #f5f5f5;
        }

        /* Progress Bar */
        .progress {
            height: 8px;
            border-radius: 10px;
        }

        /* Pagination */
        .pagination .page-link {
            border-radius: 8px;
            margin: 0 3px;
            color: #2e7d32;
        }

        .pagination .active .page-link {
            background: #2e7d32;
            border-color: #2e7d32;
            color: white;
        }

        .bg-purple {
            background: #f3e5f5;
            color: #6a1b9a;
        }

        /* Loading Spinner */
        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #2e7d32;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Fix cho bảng trong card */
        .table-responsive {
            overflow-x: auto;
        }

        .list-group-item {
            background: transparent;
        }

        .quick-filter.active-filter {

            background: #2e7d32 !important;

            color: white !important;

            border-color: #2e7d32 !important;

            box-shadow: 0 4px 10px rgba(46, 125, 50, .25);

            font-weight: 600;
        }

        .quick-filter {

            transition: all .2s ease;
        }

        .quick-filter:hover {

            transform: translateY(-1px);
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (col-md-2) -->
            <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

            <!-- Nội dung chính (col-md-10) -->
            <div class="col-md-10 p-0">
                <div class="reports-container">
                    <!-- HEADER -->
                    <div class="report-header">
                        <div>
                            <h2 class="mb-1 fw-bold" style="color: #1a3e2f;">
                                <i class="fas fa-chart-line me-2 text-success"></i>Báo cáo & Thống kê
                            </h2>
                            <p class="text-muted mb-0">Theo dõi hiệu quả hoạt động kinh doanh của cửa hàng</p>
                        </div>
                        <div class="btn-action-group mt-2 mt-sm-0">
                            <button class="btn btn-success btn-sm" id="exportExcelBtn">
                                <i class="fas fa-file-excel me-1"></i> Xuất Excel
                            </button>
                            <button class="btn btn-secondary btn-sm" id="printReportBtn">
                                <i class="fas fa-print me-1"></i> In báo cáo
                            </button>
                        </div>
                    </div>

                    <!-- BỘ LỌC -->
                    <div class="filter-bar">
                        <div class="row align-items-end g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">
                                    <i class="far fa-calendar-alt me-1"></i> Từ ngày
                                </label>
                                <input type="date" id="from_date" class="form-control" value="<?= $from_date ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">
                                    <i class="far fa-calendar-alt me-1"></i> Đến ngày
                                </label>
                                <input type="date" id="to_date" class="form-control" value="<?= $to_date ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold d-none d-md-block">&nbsp;</label>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-outline-secondary btn-sm quick-filter" data-type="today">Hôm nay</button>
                                    <button class="btn btn-outline-secondary btn-sm quick-filter" data-type="7days">7 ngày</button>
                                    <button class="btn btn-outline-secondary btn-sm quick-filter" data-type="month">Tháng này</button>
                                    <button class="btn btn-outline-secondary btn-sm quick-filter" data-type="year">Năm nay</button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold d-none d-md-block">&nbsp;</label>
                                <button class="btn btn-filter w-100 text-white" id="applyFilterBtn">
                                    <i class="fas fa-sync-alt me-1"></i> Áp dụng
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- KPI CARDS -->
                    <div class="row g-4 mb-4" id="kpiContainer">
                        <div class="col-12 text-center py-5">
                            <div class="loading-spinner"></div>
                            <p class="mt-2 text-muted">Đang tải dữ liệu...</p>
                        </div>
                    </div>

                    <!-- CHART ROW -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-7">
                            <div class="chart-card">
                                <h5 class="fw-bold mb-3">
                                    <i class="fas fa-chart-line me-2 text-success"></i>Doanh thu theo ngày
                                </h5>
                                <canvas id="revenueChart" height="200" style="max-height: 300px;"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="chart-card">
                                <h5 class="fw-bold mb-3">
                                    <i class="fas fa-chart-pie me-2 text-primary"></i>Tình trạng đơn hàng
                                </h5>
                                <canvas id="orderStatusChart" height="200" style="max-height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- SO SÁNH KỲ TRƯỚC + TOP SP + SẮP HẾT -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="chart-card">
                                <h5 class="fw-bold mb-3">
                                    <i class="fas fa-chart-simple me-2 text-info"></i>So sánh kỳ trước
                                </h5>
                                <div id="comparisonContent">Đang tải...</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="chart-card">
                                <h5 class="fw-bold mb-3">
                                    <i class="fas fa-fire me-2 text-warning"></i>Top sản phẩm bán chạy
                                </h5>
                                <div id="topProductsList">Đang tải...</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="chart-card">
                                <h5 class="fw-bold mb-3">
                                    <i class="fas fa-exclamation-triangle me-2 text-danger"></i>Sản phẩm sắp hết
                                </h5>
                                <div id="lowStockList">Đang tải...</div>
                            </div>
                        </div>
                    </div>

                    <!-- KHÁCH HÀNG MUA NHIỀU + DOANH THU THEO DANH MỤC -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="chart-card">
                                <h5 class="fw-bold mb-3">
                                    <i class="fas fa-crown me-2 text-warning"></i>Khách hàng mua nhiều nhất
                                </h5>
                                <div id="topCustomersList">Đang tải...</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-card">
                                <h5 class="fw-bold mb-3">
                                    <i class="fas fa-chart-simple me-2 text-info"></i>Doanh thu theo danh mục
                                </h5>
                                <div id="categoryRevenueList">Đang tải...</div>
                            </div>
                        </div>
                    </div>

                    <!-- BẢNG DANH SÁCH ĐƠN HÀNG -->
                    <div class="chart-card">
                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                            <h5 class="fw-bold mb-2 mb-sm-0">
                                <i class="fas fa-list me-2"></i>Danh sách đơn hàng
                            </h5>
                            <div class="d-flex gap-2">
                                <input type="text" id="searchOrder" class="form-control form-control-sm"
                                    placeholder="🔍 Tìm kiếm đơn hàng..." style="width: 220px;">
                                <select id="sortOrder" class="form-select form-select-sm" style="width: 150px;">
                                    <option value="newest">Ngày mới nhất</option>
                                    <option value="highest">Giá trị cao nhất</option>
                                </select>
                            </div>
                        </div>
                        <div id="ordersTableContainer">Đang tải...</div>
                        <div id="paginationContainer" class="mt-3 d-flex justify-content-end"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let revenueChart, orderStatusChart;
        let currentPage = 1;

        function fetchAllData() {
            let from = $('#from_date').val();
            let to = $('#to_date').val();
            let search = $('#searchOrder').val();
            let sort = $('#sortOrder').val();

            console.log('Fetching data from:', from, 'to:', to);

            $.ajax({
                url: 'ajax_filter.php',
                type: 'POST',
                data: {
                    from_date: from,
                    to_date: to,
                    page: currentPage,
                    search: search,
                    sort: sort
                },
                dataType: 'json',
                success: function(res) {
                    console.log('Response:', res);
                    if (res.kpi) renderKPI(res.kpi);
                    if (res.comparison) $('#comparisonContent').html(res.comparison);
                    if (res.topProducts) renderTopProducts(res.topProducts);
                    if (res.lowStock) renderLowStock(res.lowStock);
                    if (res.topCustomers) renderTopCustomers(res.topCustomers);
                    if (res.categoryRevenue) renderCategoryRevenue(res.categoryRevenue);
                    if (res.orders) $('#ordersTableContainer').html(res.orders);
                    if (res.pagination) $('#paginationContainer').html(res.pagination);
                    if (res.chartRevenue && res.orderStatus) {
                        updateCharts(res.chartRevenue, res.orderStatus);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Response text:', xhr.responseText);
                    $('#kpiContainer').html('<div class="col-12 text-center py-5 text-danger">Lỗi tải dữ liệu: ' + error + '</div>');
                }
            });
        }

        function renderKPI(kpi) {
            let comparisonClass = kpi.sosanh_class || 'comparison-up';
            let comparisonIcon = comparisonClass === 'comparison-up' ? '↑' : '↓';

            $('#kpiContainer').html(`
        <div class="col-md-3">
            <div class="card-stats">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small text-uppercase">Doanh thu</span>
                        <h2 class="fw-bold mt-2 mb-1">${kpi.doanhthu}</h2>
                        <span class="${comparisonClass} small">
                            ${comparisonIcon} ${Math.abs(kpi.sosanh_percent)}% so với tháng trước
                        </span>
                    </div>
                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-stats">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small text-uppercase">Tổng đơn hàng</span>
                        <h2 class="fw-bold mt-2 mb-1">${kpi.tongdon}</h2>
                        <small class="text-muted">${kpi.don_chua_xuly} đơn chờ xử lý</small>
                    </div>
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-truck"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-stats">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small text-uppercase">Khách hàng</span>
                        <h2 class="fw-bold mt-2 mb-1">${kpi.khachhang}</h2>
                        <small class="text-success">+${kpi.khach_moi} khách mới</small>
                    </div>
                    <div class="stats-icon bg-purple">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-stats">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small text-uppercase">Tỷ lệ hủy</span>
                        <h2 class="fw-bold mt-2 mb-1">${kpi.tylehuy}%</h2>
                        <small>${kpi.don_huy} đơn đã hủy</small>
                    </div>
                    <div class="stats-icon bg-danger bg-opacity-10 text-danger">
                        <i class="fas fa-ban"></i>
                    </div>
                </div>
            </div>
        </div>
    `);
        }

        function renderTopProducts(products) {
            if (!products || products.length === 0) {
                $('#topProductsList').html('<p class="text-muted text-center">Chưa có dữ liệu</p>');
                return;
            }

            let html = '<div class="list-group list-group-flush">';
            products.forEach(p => {
                let productImage = p.hinh_anh && p.hinh_anh !== '' ? p.hinh_anh : 'https://placehold.co/48x48?text=No+Img';
                html += `
            <div class="list-group-item d-flex align-items-center gap-3 border-0 px-0 py-2">
                <img src="${productImage}" class="top-product-img" onerror="this.src='https://placehold.co/48x48?text=Image'">
                <div class="flex-grow-1">
                    <strong>${escapeHtml(p.ten_san_pham)}</strong>
                    <div class="small text-muted">Đã bán: ${p.da_ban} | ${p.doanh_thu_formatted}</div>
                </div>
            </div>
        `;
            });
            html += '</div>';
            $('#topProductsList').html(html);
        }

        function renderLowStock(products) {
            if (!products || products.length === 0) {
                $('#lowStockList').html('<p class="text-muted text-center">Không có sản phẩm sắp hết</p>');
                return;
            }

            let html = '<ul class="list-unstyled mb-0">';
            products.forEach(p => {
                let badge = p.so_luong <= 3 ?
                    '<span class="badge bg-danger ms-2">rất thấp</span>' :
                    '<span class="badge bg-warning text-dark ms-2">thấp</span>';
                html += `
            <li class="mb-3 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-box me-2 text-warning"></i> ${escapeHtml(p.ten_san_pham)}</span>
                <div>còn ${p.so_luong} ${p.don_vi_tinh || ''} ${badge}</div>
            </li>
        `;
            });
            html += '</ul>';
            $('#lowStockList').html(html);
        }

        function renderTopCustomers(customers) {
            if (!customers || customers.length === 0) {
                $('#topCustomersList').html('<p class="text-muted text-center">Chưa có dữ liệu khách hàng</p>');
                return;
            }

            let html = '<div class="list-group">';
            customers.forEach(c => {
                let vipBadge = c.vip_badge ? '<span class="badge bg-warning ms-2">VIP</span>' : '';
                html += `
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-user-circle me-2 fs-5 text-secondary"></i>
                    <strong>${escapeHtml(c.ho_ten)}</strong>
                    <br><small class="text-muted">${c.so_don} đơn</small>
                </div>
                <div class="fw-bold text-end">
                    ${c.tong_chi_tieu_formatted} ${vipBadge}
                </div>
            </div>
        `;
            });
            html += '</div>';
            $('#topCustomersList').html(html);
        }

        function renderCategoryRevenue(cats) {
            if (!cats || cats.length === 0) {
                $('#categoryRevenueList').html('<p class="text-muted text-center">Chưa có dữ liệu danh mục</p>');
                return;
            }

            let html = '';
            cats.forEach(c => {
                html += `
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span><i class="fas fa-tag me-1 text-success"></i> ${escapeHtml(c.ten_danh_muc)}</span>
                    <span class="fw-bold">${c.phan_tram}%</span>
                </div>
                <div class="progress">
                    <div class="progress-bar bg-success" style="width: ${c.phan_tram}%"></div>
                </div>
            </div>
        `;
            });
            $('#categoryRevenueList').html(html);
        }

        function updateCharts(revenueData, orderStatus) {
            console.log('Updating charts:', revenueData, orderStatus);

            // Destroy existing charts
            if (revenueChart) revenueChart.destroy();
            if (orderStatusChart) orderStatusChart.destroy();

            // Revenue Chart (Line)
            let ctx1 = document.getElementById('revenueChart').getContext('2d');
            revenueChart = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: revenueData.labels || [],
                    datasets: [{
                        label: 'Doanh thu (VNĐ)',
                        data: revenueData.values || [],
                        borderColor: '#2e7d32',
                        backgroundColor: 'rgba(46, 125, 50, 0.05)',
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: '#2e7d32',
                        pointBorderColor: '#fff',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.raw.toLocaleString() + 'đ';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString() + 'đ';
                                }
                            }
                        }
                    }
                }
            });

            // Order Status Chart (Doughnut) - SỬA: dùng màu từ server
            let ctx2 = document.getElementById('orderStatusChart').getContext('2d');
            let backgroundColors = orderStatus.colors || [
                '#f59e0b', '#3b82f6', '#06b6d4', '#10b981', '#ef4444'
            ];

            orderStatusChart = new Chart(ctx2, {

                type: 'doughnut',

                data: {
                    labels: orderStatus.labels || [],

                    datasets: [{
                        data: orderStatus.values || [],

                        backgroundColor: backgroundColors,

                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },

                plugins: [ChartDataLabels],

                options: {

                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '60%',

                    plugins: {

                        legend: {
                            position: 'bottom',

                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 15
                            }
                        },

                        // Hover tooltip giữ nguyên
                        tooltip: {
                            callbacks: {
                                label: function(context) {

                                    let label = context.label || '';

                                    let value = context.parsed || 0;

                                    let total = context.dataset.data.reduce(
                                        (a, b) => a + b,
                                        0
                                    );

                                    let percentage = total > 0 ?
                                        ((value / total) * 100).toFixed(1) :
                                        0;

                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        },

                        // Hiện % trực tiếp trên chart
                        datalabels: {

                            color: '#fff',

                            font: {
                                weight: '700',
                                size: 13
                            },

                            formatter: (value, context) => {

                                const data =
                                    context.chart.data.datasets[0].data;

                                const total = data.reduce(
                                    (a, b) => a + b,
                                    0
                                );

                                if (total === 0) {
                                    return '';
                                }

                                const percentage =
                                    ((value / total) * 100).toFixed(1);

                                // Ẩn nếu quá nhỏ
                                if (percentage < 5) {
                                    return '';
                                }

                                return percentage + '%';
                            }
                        }
                    }
                }
            });
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }

        $(document).ready(function() {
            console.log('Document ready, loading data...');
            fetchAllData();

            $('#applyFilterBtn').click(function() {
                currentPage = 1;
                fetchAllData();
            });

            $('#searchOrder').on('keyup', function() {
                currentPage = 1;
                fetchAllData();
            });

            $('#sortOrder').change(function() {
                currentPage = 1;
                fetchAllData();
            });

            $(document).on('click', '.page-link', function(e) {
                e.preventDefault();
                let page = $(this).data('page');
                if (page) {
                    currentPage = page;
                    fetchAllData();
                }
            });

            $('.quick-filter').click(function() {

                // XÓA ACTIVE CŨ
                $('.quick-filter')
                    .removeClass('active-filter');

                // ACTIVE NÚT ĐANG CHỌN
                $(this)
                    .addClass('active-filter');

                let type = $(this).data('type');

                let today = new Date();

                let from, to;

                if (type === 'today') {

                    from = to =
                        today.toISOString().slice(0, 10);

                } else if (type === '7days') {

                    to = today.toISOString().slice(0, 10);

                    let sevenDaysAgo =
                        new Date(today);

                    sevenDaysAgo.setDate(
                        today.getDate() - 6
                    );

                    from =
                        sevenDaysAgo.toISOString().slice(0, 10);

                } else if (type === 'month') {

                    from =
                        new Date(
                            today.getFullYear(),
                            today.getMonth(),
                            1
                        )
                        .toISOString()
                        .slice(0, 10);

                    to =
                        new Date(
                            today.getFullYear(),
                            today.getMonth() + 1,
                            0
                        )
                        .toISOString()
                        .slice(0, 10);

                } else if (type === 'year') {

                    from =
                        new Date(
                            today.getFullYear(),
                            0,
                            1
                        )
                        .toISOString()
                        .slice(0, 10);

                    to =
                        new Date(
                            today.getFullYear(),
                            11,
                            31
                        )
                        .toISOString()
                        .slice(0, 10);
                }

                $('#from_date').val(from);

                $('#to_date').val(to);

                $('#applyFilterBtn').click();
            });

            $('#exportExcelBtn').click(function() {
                let from = $('#from_date').val();
                let to = $('#to_date').val();
                window.location.href = `export_excel.php?from=${from}&to=${to}`;
            });

            $('#printReportBtn').click(function() {
                let from = $('#from_date').val();
                let to = $('#to_date').val();
                window.open(`print.php?from=${from}&to=${to}`, '_blank');
            });
        });
    </script>
</body>

</html>