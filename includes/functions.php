<?php
/**
 * includes/functions.php
 * ฟังก์ชันช่วยเหลือที่ใช้ร่วมกันทั่วทั้งเว็บไซต์
 */

function formatPrice($price) {
    return number_format((float)$price, 2);
}

function generateOrderCode() {
    return 'ORD' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

function getCartToken() {
    if (!isset($_COOKIE['cart_token'])) {
        $token = bin2hex(random_bytes(16));
        setcookie('cart_token', $token, time() + (86400 * 30), '/');
        $_COOKIE['cart_token'] = $token;
        return $token;
    }
    return $_COOKIE['cart_token'];
}

function isLoggedIn() {
    return isset($_SESSION['member_id']);
}

function currentMemberId() {
    return $_SESSION['member_id'] ?? null;
}

function redirect($path) {
    header('Location: ' . $path);
    exit;
}

function sanitize($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * คำนวณยอดรวมตะกร้าสินค้า พร้อมส่วนลดและค่าจัดส่ง
 */
function calculateCartTotals($items, $couponDiscount = 0) {
    $subtotal = 0;
    foreach ($items as $item) {
        $price = $item['sale_price'] ?? $item['price'];
        $subtotal += $price * $item['quantity'];
    }
    $shippingFee = ($subtotal >= FREE_SHIPPING_THRESHOLD || $subtotal == 0) ? 0 : SHIPPING_FEE_FLAT;
    $total = max(0, $subtotal - $couponDiscount) + $shippingFee;
    return [
        'subtotal' => $subtotal,
        'discount' => $couponDiscount,
        'shipping_fee' => $shippingFee,
        'total' => $total,
    ];
}

/**
 * สุ่มผลตัวละครจากสต็อกจริงของ product_variants
 * ใช้ตอนคำสั่งซื้อสำเร็จเท่านั้น (ลูกค้าซื้อกล่องไปแล้วแน่นอน
 * ระบบเพียงเปิดเผยว่ากล่องที่ซื้อไปคือตัวไหน โดยตัดจากสต็อกจริง)
 */
function revealBlindBoxVariant(PDO $pdo, $productId) {
    $stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? AND stock_qty > 0");
    $stmt->execute([$productId]);
    $variants = $stmt->fetchAll();

    if (empty($variants)) return null;

    // สุ่มถ่วงน้ำหนักตามสต็อกที่เหลือจริง (ไม่ใช่ตาม drop_rate ที่แสดงผล
    // เพื่อให้สอดคล้องกับสต็อกจริงในคลังเสมอ)
    $totalStock = array_sum(array_column($variants, 'stock_qty'));
    $rand = mt_rand(1, $totalStock);
    $cumulative = 0;
    foreach ($variants as $v) {
        $cumulative += $v['stock_qty'];
        if ($rand <= $cumulative) {
            // ตัดสต็อกตัวที่สุ่มได้
            $upd = $pdo->prepare("UPDATE product_variants SET stock_qty = stock_qty - 1 WHERE variant_id = ?");
            $upd->execute([$v['variant_id']]);
            return $v;
        }
    }
    return null;
}

function uploadProductImage($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return null;

    if ($file['size'] > 5 * 1024 * 1024) return null; // จำกัด 5MB

    $filename = uniqid('prod_', true) . '.' . $ext;
    $destination = UPLOAD_DIR . $filename;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return UPLOAD_URL . $filename;
    }
    return null;
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return;
    }
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}
