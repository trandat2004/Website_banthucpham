<?php
    require_once __DIR__ . '/../includes/check_auth.php';

    $page_title = 'Quản lý liên hệ';

    require_once '../includes/config.php';

    include '../includes/header.php';
    include '../includes/sidebar.php';

    $db = getDB();

    /*
|--------------------------------------------------------------------------
| XỬ LÝ PHẢN HỒI
|--------------------------------------------------------------------------
*/
    if (isset($_POST['reply_contact'])) {

    $contact_id = intval($_POST['contact_id']);
    $phan_hoi   = safeInput($_POST['phan_hoi']);
    $admin_name = $_SESSION['admin_name'] ?? 'admin';

    $stmt = $db->prepare("SELECT user_id, tieu_de FROM lienhe WHERE id = ?");
    $stmt->execute([$contact_id]);
    $contact_info = $stmt->fetch();

    $stmt = $db->prepare("
        UPDATE lienhe
        SET
            phan_hoi = ?,
            admin_phan_hoi = ?,
            ngay_phan_hoi = datetime('now', '+7 hours'),
            trang_thai = 1
        WHERE id = ?
    ");
    $stmt->execute([$phan_hoi, $admin_name, $contact_id]);

    if ($contact_info && ! empty($contact_info['user_id'])) {
        $contact_detail_link = BASE_URL . '/public/pages/contact-detail.php?id=' . $contact_id;
        sendNotification(
            $db,
            $contact_info['user_id'],
            'lien_he',
            'phan_hoi',
            $contact_id,
            '💬 Phản hồi liên hệ của bạn',
            'Admin đã phản hồi liên hệ "' . htmlspecialchars($contact_info['tieu_de']) . '". Vui lòng xem chi tiết.',
            $contact_detail_link,
            $hinh_anh
        );
    }

    redirect('/admin/contact/list.php');
    }

    /*
|--------------------------------------------------------------------------
| LẤY TỔNG SỐ BẢN GHI CHO PHÂN TRANG
|--------------------------------------------------------------------------
*/
    $stmt          = $db->query("SELECT COUNT(*) FROM lienhe");
    $total_records = $stmt->fetchColumn();

    $records_per_page = 10;
    $total_pages      = ceil($total_records / $records_per_page);
    $current_page     = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset           = ($current_page - 1) * $records_per_page;

    /*
|--------------------------------------------------------------------------
| LẤY DANH SÁCH LIÊN HỆ CÓ PHÂN TRANG
|--------------------------------------------------------------------------
*/
    $stmt = $db->prepare("
    SELECT *
    FROM lienhe
    ORDER BY ngay_gui DESC
    LIMIT ? OFFSET ?
");
    $stmt->execute([$records_per_page, $offset]);
    $contacts = $stmt->fetchAll();

    $stmt            = $db->query("SELECT COUNT(*) FROM lienhe WHERE trang_thai = 0");
    $unreplied_count = $stmt->fetchColumn();

    $total_count = $total_records;

?>

<!-- Font Awesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
    /* ========== LAYOUT CỐ ĐỊNH HEADER ========== */
    .contact-modern-wrapper {
        background: #f3f4f6;
        height: calc(100vh - 70px);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        padding: 1rem 1.5rem 0 1.5rem;
        border-radius: 28px 0 0 0;
    }

    /* Phần header cố định */
    .contact-header-fixed {
        flex-shrink: 0;
        background: #f3f4f6;
        padding-bottom: 0.5rem;
        z-index: 10;
    }

    /* Phần danh sách có thể cuộn */
    .contact-scrollable {
        flex: 1;
        overflow-y: auto;
        padding-right: 6px;
        margin-top: 1rem;
    }

    .contact-scrollable::-webkit-scrollbar {
        width: 6px;
    }

    .contact-scrollable::-webkit-scrollbar-track {
        background: #e5e7eb;
        border-radius: 10px;
    }

    .contact-scrollable::-webkit-scrollbar-thumb {
        background: #9ca3af;
        border-radius: 10px;
    }

    /* Tiêu đề trang */
    .page-header-area {
        margin-bottom: 1.2rem;
    }
    .page-header-area h2 {
        margin-left: 15px;
        margin-top: 20px;
        font-size: 1.8rem;
        font-weight: 800;
        color: #111827;
        margin-bottom: 0.3rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .page-header-area p {
        color: #6b7280;
        margin: 0;
        margin-left: 15px;
        font-size: 0.9rem;
    }

    /* Thống kê với màu sắc riêng */
    .stat-cards {
        display: flex;
        gap: 1.2rem;
        flex-wrap: wrap;
        margin-bottom: 1.2rem;
    }
    .stat-item {
        background: white;
        border-radius: 24px;
        padding: 0.8rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border: 1px solid #e5e7eb;
        flex: 1;
        min-width: 160px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .stat-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.1);
    }

    /* Màu sắc riêng cho từng thống kê */
    .stat-item.total {
        background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);
        border-left: 4px solid #3b82f6;
    }
    .stat-item.total .stat-icon {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        box-shadow: 0 4px 12px rgba(59,130,246,0.3);
    }

    .stat-item.pending {
        background: linear-gradient(135deg, #ffffff 0%, #fff7ed 100%);
        border-left: 4px solid #f59e0b;
    }
    .stat-item.pending .stat-icon {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        box-shadow: 0 4px 12px rgba(245,158,11,0.3);
    }

    .stat-item.replied {
        background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
        border-left: 4px solid #10b981;
    }
    .stat-item.replied .stat-icon {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        box-shadow: 0 4px 12px rgba(16,185,129,0.3);
    }

    .stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        transition: all 0.3s;
    }
    .stat-item:hover .stat-icon {
        transform: scale(1.05);
    }

    .stat-info h4 {
        font-size: 1.8rem;
        font-weight: 800;
        margin: 0;
        line-height: 1.2;
    }
    .stat-item.total .stat-info h4 {
        color: #1e40af;
    }
    .stat-item.pending .stat-info h4 {
        color: #b45309;
    }
    .stat-item.replied .stat-info h4 {
        color: #065f46;
    }
    .stat-info p {
        margin: 0;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .stat-item.total .stat-info p {
        color: #3b82f6;
    }
    .stat-item.pending .stat-info p {
        color: #f59e0b;
    }
    .stat-item.replied .stat-info p {
        color: #10b981;
    }

    /* Tìm kiếm + lọc */
    .search-filter-bar {
        background: white;
        border-radius: 60px;
        padding: 0.3rem 0.3rem 0.3rem 1.5rem;
        display: flex;
        gap: 0.5rem;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        margin-bottom: 0.5rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    .search-input-group {
        flex: 2;
        min-width: 220px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .search-input-group i {
        color: #9ca3af;
        font-size: 1rem;
    }
    .search-input-group input {
        border: none;
        padding: 0.8rem 0;
        width: 100%;
        outline: none;
        font-size: 0.9rem;
        background: transparent;
    }
    .filter-buttons {
        display: flex;
        gap: 0.5rem;
    }
    .filter-btn {
        background: transparent;
        border: none;
        padding: 0.5rem 1.2rem;
        border-radius: 40px;
        font-weight: 600;
        font-size: 0.8rem;
        transition: 0.2s;
        color: #4b5563;
        cursor: pointer;
    }
    .filter-btn.active {
        background: #3b82f6;
        color: white;
    }
    .filter-btn:hover:not(.active) {
        background: #f3f4f6;
    }

    /* Card liên hệ */
    .contact-timeline {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .contact-card-modern {
        background: white;
        border-radius: 28px;
        border: 1px solid #eef2f6;
        transition: all 0.25s ease;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    }
    .contact-card-modern:hover {
        box-shadow: 0 20px 25px -12px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }

    /* Card header */
    .card-header-flex {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 1rem;
        padding: 1.5rem 1.8rem 0.8rem 1.8rem;
        border-bottom: 1px solid #f3f4f6;
    }
    .user-info h3 {
        font-size: 1.2rem;
        font-weight: 700;
        margin: 0;
        color: #111827;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .user-contact-details {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-top: 0.3rem;
        font-size: 0.8rem;
        color: #6b7280;
    }
    .user-contact-details span i {
        width: 18px;
        margin-right: 4px;
        color: #9ca3af;
    }
    .status-badge {
        padding: 0.3rem 1rem;
        border-radius: 100px;
        font-size: 0.7rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .status-badge.replied {
        background: #dcfce7;
        color: #15803d;
    }
    .status-badge.pending {
        background: #ffedd5;
        color: #c2410c;
    }

    /* Nội dung */
    .card-body-modern {
        padding: 1.2rem 1.8rem;
    }
    .contact-subject {
        font-weight: 800;
        font-size: 1rem;
        margin-bottom: 0.8rem;
        color: #1f2937;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .contact-message-modern {
        background: #f9fafb;
        padding: 1.2rem;
        border-radius: 20px;
        color: #374151;
        line-height: 1.55;
        font-size: 0.9rem;
        border-left: 4px solid #e5e7eb;
    }
    .meta-time {
        font-size: 0.7rem;
        color: #9ca3af;
        margin-top: 0.8rem;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 8px;
    }

    /* Phản hồi */
    .reply-section {
        background: #fef9f1;
        margin: 0 1.8rem 1.2rem 1.8rem;
        border-radius: 20px;
        padding: 1.2rem;
        border: 1px solid #fde68a;
    }
    .reply-header {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        color: #b45309;
        margin-bottom: 10px;
    }
    .reply-content {
        background: white;
        border-radius: 16px;
        padding: 1rem;
        color: #374151;
        font-size: 0.85rem;
        border-left: 3px solid #f59e0b;
    }
    .form-reply textarea {
        border-radius: 18px;
        border: 1px solid #e5e7eb;
        padding: 12px 16px;
        font-size: 0.85rem;
        transition: 0.2s;
    }
    .form-reply textarea:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
    }
    .btn-send-reply {
        background: #3b82f6;
        border: none;
        border-radius: 40px;
        padding: 0.5rem 1.4rem;
        font-weight: 600;
        font-size: 0.8rem;
    }
    .btn-send-reply:hover {
        background: #2563eb;
    }

    /* Phân trang */
    .pagination-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .pagination {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        background: white;
        padding: 0.5rem 1rem;
        border-radius: 60px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .pagination a, .pagination span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 12px;
        border-radius: 40px;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.85rem;
        transition: 0.2s;
        color: #4b5563;
    }
    .pagination a:hover {
        background: #f3f4f6;
        color: #3b82f6;
    }
    .pagination .active-page {
        background: #3b82f6;
        color: white;
    }
    .pagination .disabled {
        color: #d1d5db;
        pointer-events: none;
    }

    /* Empty state */
    .empty-state {
        background: white;
        border-radius: 2rem;
        text-align: center;
        padding: 3rem;
    }

    @media (max-width: 768px) {
        .card-header-flex {
            flex-direction: column;
        }
        .search-filter-bar {
            border-radius: 28px;
            flex-direction: column;
            align-items: stretch;
            padding: 1rem;
        }
        .filter-buttons {
            justify-content: center;
        }
        .stat-item {
            min-width: 100%;
        }
        .pagination a, .pagination span {
            min-width: 32px;
            padding: 0 8px;
        }
    }
</style>

<div class="col-md-10 main-content p-0 contact-modern-wrapper">
    <!-- Phần HEADER cố định (không cuộn) -->
    <div class="contact-header-fixed">
        <div class="page-header-area">
            <h2>
                <i class="fas fa-headset" style="color: #3b82f6;"></i> Quản lý liên hệ
            </h2>
            <p>Theo dõi và phản hồi khách hàng một cách nhanh chóng</p>
        </div>

        <!-- Thống kê với màu sắc riêng biệt -->
        <div class="stat-cards">
            <div class="stat-item total">
                <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                <div class="stat-info">
                    <h4><?php echo number_format($total_count); ?></h4>
                    <p><i class="fas fa-chart-line"></i> TỔNG LIÊN HỆ</p>
                </div>
            </div>
            <div class="stat-item pending">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h4><?php echo number_format($unreplied_count); ?></h4>
                    <p><i class="fas fa-hourglass-half"></i> CHƯA PHẢN HỒI</p>
                </div>
            </div>
            <div class="stat-item replied">
                <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                <div class="stat-info">
                    <h4><?php echo number_format($total_count - $unreplied_count); ?></h4>
                    <p><i class="fas fa-check-circle"></i> ĐÃ TRẢ LỜI</p>
                </div>
            </div>
        </div>

        <!-- Tìm kiếm + lọc -->
        <div class="search-filter-bar">
            <div class="search-input-group">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Tìm theo tên, email, số điện thoại hoặc tiêu đề...">
            </div>
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">Tất cả</button>
                <button class="filter-btn" data-filter="pending">Chưa phản hồi</button>
                <button class="filter-btn" data-filter="replied">Đã phản hồi</button>
            </div>
        </div>
    </div>

    <!-- Phần DANH SÁCH có thể cuộn -->
    <div class="contact-scrollable">
        <div class="contact-timeline" id="contactsContainer">
            <?php if ($total_count > 0): ?>
                <?php foreach ($contacts as $contact): ?>
                    <div class="contact-card-modern"
                         data-status="<?php echo $contact['trang_thai'] == 1 ? 'replied' : 'pending'; ?>"
                         data-name="<?php echo htmlspecialchars(strtolower($contact['ho_ten'])); ?>"
                         data-email="<?php echo htmlspecialchars(strtolower($contact['email'])); ?>"
                         data-phone="<?php echo htmlspecialchars(strtolower($contact['dien_thoai'] ?? '')); ?>"
                         data-title="<?php echo htmlspecialchars(strtolower($contact['tieu_de'])); ?>">

                        <div class="card-header-flex">
                            <div class="user-info">
                                <h3>
                                    <i class="fas fa-user-circle" style="color:#3b82f6;"></i>
                                    <?php echo htmlspecialchars($contact['ho_ten']); ?>
                                </h3>
                                <div class="user-contact-details">
                                    <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contact['email']); ?></span>
                                    <?php if (! empty($contact['dien_thoai'])): ?>
                                        <span><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($contact['dien_thoai']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <?php if ($contact['trang_thai'] == 1): ?>
                                    <span class="status-badge replied"><i class="fas fa-check-circle"></i> Đã phản hồi</span>
                                <?php else: ?>
                                    <span class="status-badge pending"><i class="fas fa-hourglass-half"></i> Chưa phản hồi</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-body-modern">
                            <div class="contact-subject">
                                <i class="fas fa-tag" style="color:#6b7280; font-size: 12px;"></i>
                                <?php echo htmlspecialchars($contact['tieu_de']); ?>
                            </div>
                            <div class="contact-message-modern">
                                <?php echo nl2br(htmlspecialchars($contact['noi_dung'])); ?>
                            </div>
                            <div class="meta-time">
                                <i class="far fa-calendar-alt"></i> Gửi lúc: <?php echo date('d/m/Y H:i', strtotime($contact['ngay_gui'])); ?>
                            </div>
                        </div>

                        <?php if (! empty($contact['phan_hoi'])): ?>
                            <div class="reply-section">
                                <div class="reply-header">
                                    <i class="fas fa-reply-all"></i> Phản hồi từ Admin
                                    <span style="font-size: 12px; font-weight: normal;">(<?php echo htmlspecialchars($contact['admin_phan_hoi']); ?>)</span>
                                </div>
                                <div class="reply-content">
                                    <?php echo nl2br(htmlspecialchars($contact['phan_hoi'])); ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="reply-section" style="background: #f8fafc; border-color: #e2e8f0;">
                                <form method="POST" class="form-reply">
                                    <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                    <textarea name="phan_hoi" rows="3" class="form-control" placeholder="Viết phản hồi cho khách hàng..."></textarea>
                                    <div class="mt-3 text-end">
                                        <button type="submit" name="reply_contact" class="btn btn-send-reply">
                                            <i class="fas fa-paper-plane me-1"></i> Gửi phản hồi
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <h5 class="fw-bold">Chưa có liên hệ nào</h5>
                    <p class="text-muted">Khách hàng gửi liên hệ sẽ hiển thị tại đây</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- PHÂN TRANG -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination-wrapper">
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?php echo $current_page - 1; ?>"><i class="fas fa-chevron-left"></i></a>
                <?php else: ?>
                    <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                <?php endif; ?>

                <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page   = min($total_pages, $current_page + 2);

                if ($start_page > 1): ?>
                    <a href="?page=1">1</a>
                    <?php if ($start_page > 2): ?>
                        <span>...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $current_page): ?>
                        <span class="active-page"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span>...</span>
                    <?php endif; ?>
                    <a href="?page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
                <?php endif; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?>"><i class="fas fa-chevron-right"></i></a>
                <?php else: ?>
                    <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    (function() {
        const searchInput = document.getElementById('searchInput');
        const filterBtns = document.querySelectorAll('.filter-btn');
        const contactCards = document.querySelectorAll('.contact-card-modern');

        let currentFilter = 'all';
        let currentSearch = '';

        function filterContacts() {
            contactCards.forEach(card => {
                const status = card.getAttribute('data-status');
                const name = card.getAttribute('data-name') || '';
                const email = card.getAttribute('data-email') || '';
                const phone = card.getAttribute('data-phone') || '';
                const title = card.getAttribute('data-title') || '';

                let matchesFilter = true;
                if (currentFilter === 'pending') {
                    matchesFilter = (status === 'pending');
                } else if (currentFilter === 'replied') {
                    matchesFilter = (status === 'replied');
                }

                let matchesSearch = true;
                if (currentSearch.trim() !== '') {
                    const term = currentSearch.toLowerCase();
                    matchesSearch = name.includes(term) || email.includes(term) || phone.includes(term) || title.includes(term);
                }

                if (matchesFilter && matchesSearch) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentFilter = this.getAttribute('data-filter');
                filterContacts();
            });
        });

        searchInput.addEventListener('input', function(e) {
            currentSearch = e.target.value;
            filterContacts();
        });
    })();
</script>

<?php include '../includes/footer.php'; ?>