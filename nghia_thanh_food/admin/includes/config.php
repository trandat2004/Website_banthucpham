<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('BASE_URL', 'http://localhost:8000');
define('BASE_PATH', dirname(__DIR__, 2));

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/
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
        } catch (PDOException $e) {

            die("Connection failed: " . $e->getMessage());
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
}

/*
|--------------------------------------------------------------------------
| DATABASE HELPER
|--------------------------------------------------------------------------
*/
function getDB()
{

    return Database::getInstance()->getConnection();
}

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
function isAdminLoggedIn()
{

    return isset($_SESSION['admin_id'])
    && isset($_SESSION['admin_role'])
        && $_SESSION['admin_role'] == 1;
}

/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/
function redirect($url)
{

    header('Location: ' . BASE_URL . $url);
    exit();
}

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/
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

/*
|--------------------------------------------------------------------------
| ADMIN LOGIN CHECK
|--------------------------------------------------------------------------
*/
$current_file = basename($_SERVER['PHP_SELF']);

if ($current_file != 'login.php' && ! isAdminLoggedIn()) {

    redirect('/admin/auth/login.php');
}

/*
|--------------------------------------------------------------------------
| HÀM GỬI THÔNG BÁO
|--------------------------------------------------------------------------
*/

if (! function_exists('sendNotification')) {
    /**
     * Gửi thông báo cho một người dùng
     */
    function sendNotification($db, $user_id, $loai, $hanh_dong, $tham_chieu_id, $tieu_de, $noi_dung, $link = null, $hinh_anh = null)
    {
        try {
            $sql = "INSERT INTO thongbao (
                        user_id, loai, hanh_dong, tham_chieu_id, tieu_de, noi_dung, link, hinh_anh, da_xem, ngay_tao
                    ) VALUES (
                        :user_id, :loai, :hanh_dong, :tham_chieu_id, :tieu_de, :noi_dung, :link, :hinh_anh, 0, datetime('now', '+7 hours')
                    )";
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'user_id'       => $user_id,
                'loai'          => $loai,
                'hanh_dong'     => $hanh_dong,
                'tham_chieu_id' => $tham_chieu_id,
                'tieu_de'       => $tieu_de,
                'noi_dung'      => $noi_dung,
                'link'          => $link,
                'hinh_anh'      => $hinh_anh,
            ]);
        } catch (PDOException $e) {
            error_log("Send notification error: " . $e->getMessage());
            return false;
        }
    }
}

if (! function_exists('sendNotificationToUsers')) {
    /**
     * Gửi thông báo cho nhiều người dùng
     */
    function sendNotificationToUsers($db, $user_ids, $loai, $hanh_dong, $tham_chieu_id, $tieu_de, $noi_dung, $link = null, $hinh_anh = null)
    {
        if (empty($user_ids)) {
            return 0;
        }

        $success = 0;
        $sql     = "INSERT INTO thongbao (
                    user_id, loai, hanh_dong, tham_chieu_id, tieu_de, noi_dung, link, hinh_anh, da_xem, ngay_tao
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, 0, datetime('now', '+7 hours')
                )";
        $stmt = $db->prepare($sql);

        foreach ($user_ids as $user_id) {
            try {
                if ($stmt->execute([$user_id, $loai, $hanh_dong, $tham_chieu_id, $tieu_de, $noi_dung, $link, $hinh_anh])) {
                    $success++;
                }
            } catch (PDOException $e) {
                error_log("Send notification error for user $user_id: " . $e->getMessage());
            }
        }
        return $success;
    }
}

if (! function_exists('sendNotificationToAllUsers')) {
    /**
     * Gửi thông báo cho tất cả người dùng (khách hàng)
     */
    function sendNotificationToAllUsers($db, $loai, $hanh_dong, $tham_chieu_id, $tieu_de, $noi_dung, $link = null, $hinh_anh = null)
    {
        // Lấy tất cả user có trang_thai = 1 (đang hoạt động) và không phải admin
        $stmt = $db->prepare("SELECT id FROM users WHERE trang_thai = 1 AND vai_tro = 0");
        $stmt->execute();
        $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return sendNotificationToUsers($db, $user_ids, $loai, $hanh_dong, $tham_chieu_id, $tieu_de, $noi_dung, $link, $hinh_anh);
    }
}
