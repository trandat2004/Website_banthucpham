-- Bảng danh mục sản phẩm
CREATE TABLE IF NOT EXISTS danhmuc (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ten_danh_muc TEXT NOT NULL,
    mo_ta TEXT,
    trang_thai INTEGER DEFAULT 1,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Bảng sản phẩm
CREATE TABLE IF NOT EXISTS sanpham (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ma_san_pham TEXT UNIQUE,
    ten_san_pham TEXT NOT NULL,
    danh_muc_id INTEGER,
    gia_ban DECIMAL(10,2) NOT NULL,
    gia_khuyen_mai DECIMAL(10,2),
    so_luong INTEGER DEFAULT 0,
    don_vi_tinh TEXT DEFAULT 'kg',
    hinh_anh TEXT,
    mo_ta_ngan TEXT,
    mo_ta_chi_tiet TEXT,
    trang_thai INTEGER DEFAULT 1,
    san_pham_noi_bat INTEGER DEFAULT 0,
    san_pham_moi INTEGER DEFAULT 1,
    luot_xem INTEGER DEFAULT 0,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (danh_muc_id) REFERENCES danhmuc(id) ON DELETE SET NULL
);

-- Bảng người dùng (khách hàng)
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ho_ten TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    mat_khau TEXT NOT NULL,
    dien_thoai TEXT,
    dia_chi TEXT,
    thanh_pho TEXT,
    vai_tro INTEGER DEFAULT 0, -- 0: khách hàng, 1: admin
    trang_thai INTEGER DEFAULT 1,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP,
    email_verified INTEGER DEFAULT 0, -- 0: chưa xác thực, 1: đã xác thực
    avatar TEXT
);

-- Bảng quản trị viên (kế thừa từ users)
-- Tài khoản admin sẽ được lưu trong users với vai_tro = 1

-- Bảng đơn hàng
CREATE TABLE IF NOT EXISTS donhang (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ma_don_hang TEXT UNIQUE NOT NULL,
    user_id INTEGER,
    ho_ten_nguoi_nhan TEXT NOT NULL,
    email_nguoi_nhan TEXT NOT NULL,
    dien_thoai_nguoi_nhan TEXT NOT NULL,
    dia_chi_giao_hang TEXT NOT NULL,
    thanh_pho TEXT,
    ghi_chu TEXT,
    tong_tien DECIMAL(10,2) NOT NULL,
    phi_ship DECIMAL(10,2) DEFAULT 0,
    tong_thanh_toan DECIMAL(10,2) NOT NULL,
    trang_thai TEXT DEFAULT 'cho_xac_nhan',
    phuong_thuc_thanh_toan TEXT DEFAULT 'cod',
    ngay_dat DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Bảng chi tiết đơn hàng
CREATE TABLE IF NOT EXISTS chitietdonhang (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    don_hang_id INTEGER NOT NULL,
    san_pham_id INTEGER NOT NULL,
    ten_san_pham TEXT NOT NULL,
    gia DECIMAL(10,2) NOT NULL,
    so_luong INTEGER NOT NULL,
    thanh_tien DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (don_hang_id) REFERENCES donhang(id) ON DELETE CASCADE,
    FOREIGN KEY (san_pham_id) REFERENCES sanpham(id) ON DELETE CASCADE
);

-- Bảng tin tức
CREATE TABLE IF NOT EXISTS tintuc (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tieu_de TEXT NOT NULL,
    tieu_de_khong_dau TEXT,
    noi_dung TEXT,
    hinh_anh TEXT,
    luot_xem INTEGER DEFAULT 0,
    trang_thai INTEGER DEFAULT 1,
    ngay_dang DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Bảng liên hệ
CREATE TABLE IF NOT EXISTS lienhe (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ho_ten TEXT NOT NULL,
    email TEXT NOT NULL,
    dien_thoai TEXT,
    tieu_de TEXT,
    noi_dung TEXT NOT NULL,
    trang_thai INTEGER DEFAULT 0,
    ngay_gui DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Bảng cấu hình website
CREATE TABLE IF NOT EXISTS cauhinh (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ten_cau_hinh TEXT UNIQUE NOT NULL,
    gia_tri TEXT,
    mo_ta TEXT
);

-- Tạo index cho các bảng
CREATE INDEX idx_sanpham_danhmuc ON sanpham(danhmuc_id);
CREATE INDEX idx_sanpham_trangthai ON sanpham(trang_thai);
CREATE INDEX idx_donhang_user ON donhang(user_id);
CREATE INDEX idx_donhang_trangthai ON donhang(trang_thai);
CREATE INDEX idx_chitiet_donhang ON chitietdonhang(don_hang_id);
CREATE INDEX idx_tintuc_ngaydang ON tintuc(ngay_dang);

ALTER TABLE lienhe 
ADD COLUMN phan_hoi TEXT;

CREATE TABLE IF NOT EXISTS maotp (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL,
    ma_otp TEXT NOT NULL,
    muc_dich TEXT NOT NULL,
    thoi_gian_het_han DATETIME NOT NULL,
    da_su_dung INTEGER DEFAULT 0,
    so_lan_nhap_sai INTEGER DEFAULT 0,
    ngay_tao DATETIME
);

CREATE TABLE thongbao (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,          -- Người nhận thông báo
    loai VARCHAR(50) NOT NULL,         -- don_hang, danh_muc, san_pham, tin_tuc, lien_he
    hanh_dong VARCHAR(50) NOT NULL,    -- tao_moi, cap_nhat_trang_thai, phan_hoi, ...
    tham_chieu_id INTEGER NOT NULL,    -- ID của bảng liên quan (don_hang_id, san_pham_id...)
    tieu_de VARCHAR(255) NOT NULL,     -- Tiêu đề thông báo
    noi_dung TEXT,                      -- Nội dung chi tiết
    link VARCHAR(500),                  -- Link để xem chi tiết (VD: /pages/order-detail.php?id=xxx)
    da_xem INTEGER DEFAULT 0,           -- 0: chưa xem, 1: đã xem
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_thongbao_user ON thongbao(user_id, da_xem);
CREATE INDEX idx_thongbao_ngay ON thongbao(ngay_tao DESC);