<?php
/**
 * api/cart_update.php
 * action = update | remove
 */
require_once __DIR__ . '/../includes/init.php';
header('Content-Type: application/json; charset=utf-8');

$cartItemId = (int)($_POST['cart_item_id'] ?? 0);
$action = $_POST['action'] ?? 'update';
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

// ตรวจสอบสิทธิ์ความเป็นเจ้าของตะกร้า (ผูกกับ cart_token เท่านั้น)
$stmt = $pdo->prepare("SELECT ci.*, p.stock_qty, p.is_preorder FROM cart_items ci
    JOIN products p ON p.product_id = ci.product_id
    WHERE ci.cart_item_id = ? AND ci.cart_token = ?");
$stmt->execute([$cartItemId, $cartToken]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบรายการในตะกร้า']);
    exit;
}

if ($action === 'remove') {
    $pdo->prepare("DELETE FROM cart_items WHERE cart_item_id = ?")->execute([$cartItemId]);
} else {
    if (!$item['is_preorder'] && $quantity > $item['stock_qty']) {
        $quantity = max(1, (int)$item['stock_qty']);
    }
    $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?")->execute([$quantity, $cartItemId]);
}

// คำนวณยอดใหม่ทั้งหมด
$itemsStmt = $pdo->prepare("SELECT ci.quantity, p.price, p.sale_price FROM cart_items ci
    JOIN products p ON p.product_id = ci.product_id WHERE ci.cart_token = ?");
$itemsStmt->execute([$cartToken]);
$items = $itemsStmt->fetchAll();

$totals = calculateCartTotals($items);
$cartCount = array_sum(array_column($items, 'quantity'));

echo json_encode([
    'success' => true,
    'cart_count' => $cartCount,
    'totals' => $totals,
]);
