<?php
// Cấu hình website
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Đường dẫn cơ sở
define('BASE_URL', 'http://localhost:8000');
define('BASE_PATH', dirname(__DIR__, 2));

// Cấu hình Database SQLite
class Database
{
    private static $instance = null;
    private $db;

    private function __construct()
    {
        try {
            $db_path = BASE_PATH . '/database/database.db';
            $this->db = new PDO("sqlite:" . $db_path);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Kiểm tra và tạo bảng nếu chưa có
            $this->initializeDatabase();
        } catch (PDOException $e) {
            die("Kết nối database thất bại: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->db;
    }

    private function initializeDatabase()
    {
        $schema_file = BASE_PATH . '/database/schema.sql';
        if (file_exists($schema_file)) {
            $sql = file_get_contents($schema_file);
            try {
                $this->db->exec($sql);
            } catch (PDOException $e) {
                // Bảng đã tồn tại
            }
        }
    }
}

// Hàm lấy kết nối database
function getDB()
{
    return Database::getInstance()->getConnection();
}

// Hàm helper
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isAdmin()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1;
}

function redirect($url)
{
    header("Location: " . BASE_URL . $url);
    exit();
}

function formatPrice($price)
{
    return number_format($price, 0, ',', '.') . 'đ';
}

function safeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Lấy cấu hình website
function getConfig($key)
{
    $db = getDB();
    $stmt = $db->prepare("SELECT gia_tri FROM cauhinh WHERE ten_cau_hinh = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['gia_tri'] : '';
}

// =============================================
// HÀM GỬI THÔNG BÁO
// =============================================

/**
 * Gửi thông báo cho một người dùng
 * 
 * @param PDO $db Kết nối database
 * @param int $user_id ID người nhận
 * @param string $loai Loại thông báo (don_hang, san_pham, tin_tuc, lien_he, danh_muc)
 * @param string $tieu_de Tiêu đề thông báo
 * @param string $noi_dung Nội dung thông báo
 * @param string|null $link Đường dẫn xem chi tiết (tùy chọn)
 * @return bool Thành công hay không
 */
function sendNotification($db, $user_id, $loai, $tieu_de, $noi_dung, $link = null)
{
    try {
        $sql = "INSERT INTO thongbao (user_id, loai, tieu_de, noi_dung, link, ngay_tao, da_xem) 
                VALUES (:user_id, :loai, :tieu_de, :noi_dung, :link, datetime('now', '+7 hours'), 0)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'user_id' => $user_id,
            'loai' => $loai,
            'tieu_de' => $tieu_de,
            'noi_dung' => $noi_dung,
            'link' => $link
        ]);
    } catch (PDOException $e) {
        error_log("Send notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Gửi thông báo cho nhiều người dùng
 * 
 * @param PDO $db Kết nối database
 * @param array $user_ids Mảng các user_id
 * @param string $loai Loại thông báo
 * @param string $tieu_de Tiêu đề thông báo
 * @param string $noi_dung Nội dung thông báo
 * @param string|null $link Đường dẫn xem chi tiết
 * @return int Số lượng thông báo đã gửi thành công
 */
function sendNotificationToUsers($db, $user_ids, $loai, $tieu_de, $noi_dung, $link = null)
{
    if (empty($user_ids)) return 0;

    $success = 0;
    $sql = "INSERT INTO thongbao (user_id, loai, tieu_de, noi_dung, link, ngay_tao, da_xem) 
            VALUES (?, ?, ?, ?, ?, datetime('now', '+7 hours'), 0)";
    $stmt = $db->prepare($sql);

    foreach ($user_ids as $user_id) {
        try {
            if ($stmt->execute([$user_id, $loai, $tieu_de, $noi_dung, $link])) {
                $success++;
            }
        } catch (PDOException $e) {
            error_log("Send notification error for user $user_id: " . $e->getMessage());
        }
    }
    return $success;
}

/**
 * Gửi thông báo cho tất cả người dùng (khách hàng)
 * 
 * @param PDO $db Kết nối database
 * @param string $loai Loại thông báo
 * @param string $tieu_de Tiêu đề thông báo
 * @param string $noi_dung Nội dung thông báo
 * @param string|null $link Đường dẫn xem chi tiết
 * @return int Số lượng thông báo đã gửi thành công
 */
function sendNotificationToAllUsers($db, $loai, $tieu_de, $noi_dung, $link = null)
{
    // Lấy tất cả user có trang_thai = 1 (đang hoạt động)
    $stmt = $db->prepare("SELECT id FROM users WHERE trang_thai = 1");
    $stmt->execute();
    $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return sendNotificationToUsers($db, $user_ids, $loai, $tieu_de, $noi_dung, $link);
}

// =============================================
// CẤU HÌNH OTP
// =============================================
define('OTP_LENGTH', 6);
define('OTP_EXPIRY_MINUTES', 5);   // OTP hết hạn sau 5 phút
define('OTP_MAX_ATTEMPTS', 5);      // Số lần nhập sai tối đa
define('OTP_REGISTER', 'register');
define('OTP_RESET_PASSWORD', 'reset_password');
