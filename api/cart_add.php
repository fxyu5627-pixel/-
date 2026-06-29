<?php
/**
 * api/cart_add.php
 * เพิ่มสินค้าลงตะกร้า (รองรับทั้ง guest ผ่าน cart_token และ member)
 */
require_once __DIR__ . '/../includes/init.php';
header('Content-Type: application/json; charset=utf-8');

$productId = (int)($_POST['product_id'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบสินค้า']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ? AND is_active = 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบสินค้า หรือสินค้าถูกปิดการขาย']);
    exit;
}

if ($product['stock_qty'] <= 0 && !$product['is_preorder']) {
    echo json_encode(['success' => false, 'message' => 'สินค้าหมดแล้ว']);
    exit;
}

// ตรวจสอบว่ามีในตะกร้าอยู่แล้วหรือยัง
$checkStmt = $pdo->prepare("SELECT * FROM cart_items WHERE cart_token = ? AND product_id = ?");
$checkStmt->execute([$cartToken, $productId]);
$existing = $checkStmt->fetch();

if ($existing) {
    $newQty = $existing['quantity'] + $quantity;
    if (!$product['is_preorder'] && $newQty > $product['stock_qty']) {
        $newQty = $product['stock_qty'];
    }
    $upd = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
    $upd->execute([$newQty, $existing['cart_item_id']]);
} else {
    if (!$product['is_preorder'] && $quantity > $product['stock_qty']) {
        $quantity = $product['stock_qty'];
    }
    $ins = $pdo->prepare("INSERT INTO cart_items (cart_token, member_id, product_id, quantity) VALUES (?, ?, ?, ?)");
    $ins->execute([$cartToken, currentMemberId(), $productId, $quantity]);
}

$cntStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) AS cnt FROM cart_items WHERE cart_token = ?");
$cntStmt->execute([$cartToken]);
$cartCount = (int)$cntStmt->fetch()['cnt'];

echo json_encode(['success' => true, 'cart_count' => $cartCount]);
