-- Thêm dữ liệu danh mục
INSERT INTO danhmuc (ten_danh_muc, mo_ta, trang_thai) VALUES
('Rau củ quả', 'Các loại rau củ quả tươi sạch, an toàn', 1),
('Thịt cá', 'Thịt tươi, cá các loại đông lạnh', 1),
('Gia vị', 'Các loại gia vị đặc biệt', 1),
('Hải sản', 'Hải sản tươi sống và đông lạnh', 1),
('Sản phẩm khô', 'Nấm, mộc nhĩ, hải sản khô', 1),
('Đồ uống', 'Các loại nước giải khát', 1);

-- Thêm dữ liệu sản phẩm
INSERT INTO sanpham (ma_san_pham, ten_san_pham, danh_muc_id, gia_ban, gia_khuyen_mai, so_luong, don_vi_tinh, hinh_anh, mo_ta_ngan, mo_ta_chi_tiet, trang_thai, san_pham_noi_bat, san_pham_moi) VALUES
('SP001', 'Rau cải xanh hữu cơ', 1, 25000, 20000, 100, 'kg', 'rau-cai-xanh.jpg', 'Rau cải xanh tươi sạch, trồng theo tiêu chuẩn hữu cơ', 'Rau cải xanh được trồng tại vùng đất sạch, không sử dụng hóa chất bảo vệ thực vật, đảm bảo an toàn cho sức khỏe', 1, 1, 1),
('SP002', 'Thịt thăn bò nhập khẩu', 2, 350000, 320000, 50, 'kg', 'thit-bo.jpg', 'Thịt bò thăn mềm, nhập khẩu từ Úc', 'Thịt bò Úc, thăn ngoại mềm, thích hợp nướng, xào, nấu phở', 1, 1, 0),
('SP003', 'Cá hồi Na Uy fillet', 2, 450000, 420000, 30, 'kg', 'ca-hoi.jpg', 'Cá hồi Na Uy tươi, fillet sẵn', 'Cá hồi Na Uy giàu Omega 3, được fillet sẵn tiện lợi để chế biến', 1, 1, 1),
('SP004', 'Hạt nêm Nghĩa Thành', 3, 89000, 75000, 200, 'hộp', 'hat-nem.jpg', 'Hạt nêm từ thịt thăn, xương ống', 'Hạt nêm cao cấp được chiết xuất từ thịt thăn và xương ống, tạo vị ngọt tự nhiên cho món ăn', 1, 1, 1),
('SP005', 'Mực ống tươi', 4, 280000, 250000, 40, 'kg', 'muc-ong.jpg', 'Mực ống tươi sạch, không tẩm hóa chất', 'Mực ống tươi được đánh bắt trong ngày, sơ chế sạch, giao hàng bảo quản lạnh', 1, 0, 0),
('SP006', 'Nấm hương khô', 5, 350000, 320000, 60, 'kg', 'nam-huong.jpg', 'Nấm hương khô thơm ngon', 'Nấm hương khô tự nhiên, không tẩm hóa chất, thơm ngon đặc trưng', 1, 0, 0),
('SP007', 'Nước dừa tươi đóng lon', 6, 25000, 22000, 500, 'lon', 'nuoc-dua.jpg', 'Nước dừa tươi từ dừa non', 'Nước dừa tươi đóng lon, giữ nguyên hương vị tự nhiên, không chất bảo quản', 1, 1, 1),
('SP008', 'Tôm sú đông lạnh', 4, 380000, 350000, 35, 'kg', 'tom-su.jpg', 'Tôm sú loại 1, đông lạnh nhanh', 'Tôm sú được nuôi tại vùng nước lợ sạch, đông lạnh nhanh giữ độ tươi', 1, 0, 0),
('SP009', 'Chả giò rế đông lạnh', NULL, 120000, 110000, 80, 'gói', 'cha-gio.jpg', 'Chả giò rế nhân thịt, tôm, miến', 'Chả giò rế nhân thịt, tôm, miến, hạt sen, đông lạnh sẵn, chiên giòn ngon', 1, 1, 1),
('SP010', 'Trái cây sấy tổng hợp', 5, 95000, 85000, 45, 'hộp', 'trai-cay-say.jpg', 'Hộp trái cây sấy các loại', 'Hỗn hợp xoài sấy, dứa sấy, chuối sấy, mít sấy, bơ sấy', 1, 0, 0);

-- Thêm tài khoản admin
INSERT INTO users (ho_ten, email, mat_khau, dien_thoai, dia_chi, vai_tro, trang_thai) VALUES
('Admin', 'admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0901234567', 'Hà Nội, Việt Nam', 1, 1);

-- Thêm tài khoản khách hàng mẫu
INSERT INTO users (ho_ten, email, mat_khau, dien_thoai, dia_chi, vai_tro, trang_thai) VALUES
('Nguyễn Văn An', 'an.nguyen@email.com', '$2y$10$SampleHashForPassword123456', '0912345678', 'Số 12, Đường Láng, Đống Đa, Hà Nội', 0, 1),
('Trần Thị Bình', 'binh.tran@email.com', '$2y$10$SampleHashForPassword123456', '0987654321', 'Số 5, Đường Nguyễn Trãi, Quận 1, TP.HCM', 0, 1);

-- Thêm tin tức
INSERT INTO tintuc (tieu_de, tieu_de_khong_dau, noi_dung, hinh_anh, trang_thai) VALUES
('Chương trình khuyến mãi mừng năm mới', 'chuong-trinh-khuyen-mai-mung-nam-moi', 'Giảm giá lên đến 30% cho tất cả sản phẩm từ ngày 1-15 tháng 1', 'khuyen-mai-tet.jpg', 1),
('Bí quyết chọn thịt tươi ngon', 'bi-quyet-chon-thit-tuoi-ngon', 'Chia sẻ kinh nghiệm chọn thịt tươi, an toàn cho gia đình', 'bi-quyet-chon-thit.jpg', 1),
('Quy trình sản xuất hạt nêm của Nghĩa Thành', 'quy-trinh-san-xuat-hat-nem-cua-nghia-thanh', 'Tìm hiểu quy trình sản xuất khép kín từ khâu chọn nguyên liệu đến thành phẩm', 'quy-trinh-hat-nem.jpg', 1);

-- Thêm cấu hình website
INSERT INTO cauhinh (ten_cau_hinh, gia_tri, mo_ta) VALUES
('company_name', 'Công ty TNHH Chế biến thực phẩm xuất khẩu Nghĩa Thành', 'Tên công ty'),
('company_phone', '0228 6595 555', 'Số điện thoại hotline'),
('company_email', 'nghiathanhfood@gmail.com', 'Email liên hệ'),
('company_address', 'Số 25 Song Hào, Phường Trần Quang Khải, Thành phố Nam Định, Nam Định', 'Địa chỉ công ty'),
('facebook_url', 'https://facebook.com/nghiathanhfood', 'Link Facebook'),
('discount_code', 'WELCOME10', 'Mã giảm giá 10%');