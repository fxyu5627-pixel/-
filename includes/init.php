<?php
/**
 * includes/init.php
 * ไฟล์เริ่มต้นที่ทุกหน้าต้อง require ก่อนใช้งานอย่างอื่น
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$pdo = getDB();
$cartToken = getCartToken();
