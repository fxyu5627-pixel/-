<?php
/**
 * config/database.php
 * ไฟล์เชื่อมต่อฐานข้อมูล MySQL ผ่าน PDO
 * แก้ค่า DB_HOST, DB_NAME, DB_USER, DB_PASS ให้ตรงกับเซิร์ฟเวอร์จริงของคุณ
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'arttoy_shop');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ค่าตั้งต้นของร้าน (ปรับได้ตามต้องการ)
define('SITE_NAME', 'ArtToy Shop');
define('SHIPPING_FEE_FLAT', 50.00);     // ค่าจัดส่งเหมาจ่าย (บาท)
define('FREE_SHIPPING_THRESHOLD', 990); // ซื้อครบเท่านี้ส่งฟรี
define('UPLOAD_DIR', __DIR__ . '/../uploads/products/');
define('UPLOAD_URL', '/uploads/products/');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . $e->getMessage());
        }
    }
    return $pdo;
}
