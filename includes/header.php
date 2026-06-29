<?php
/**
 * includes/header.php
 * Header & Navigation - ใช้ร่วมกันทุกหน้าฝั่งร้านค้า
 */
$cartCountStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) AS cnt FROM cart_items WHERE cart_token = ?");
$cartCountStmt->execute([$cartToken]);
$cartCount = (int)$cartCountStmt->fetch()['cnt'];

$navCategories = $pdo->query("SELECT name, slug FROM categories ORDER BY sort_order ASC")->fetchAll();
$currentSlug = $_GET['category'] ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? sanitize($pageTitle) . ' - ' . SITE_NAME : SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a href="/index.php" class="logo">🧸 ArtToy<span class="dot">.</span></a>

        <form class="search-bar" action="/search.php" method="get">
            <input type="text" name="q" placeholder="ค้นหา Art Toy, Blind Box, หมีพูห์..." value="<?= sanitize($_GET['q'] ?? '') ?>">
            <button type="submit" aria-label="ค้นหา">🔍</button>
        </form>

        <div class="header-actions">
            <a href="/cart.php" class="icon-btn" aria-label="ตะกร้าสินค้า">
                🛒 ตะกร้า
                <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
            <?php if (isLoggedIn()): ?>
                <a href="/member/dashboard.php" class="icon-btn">👤 สมาชิก</a>
                <a href="/logout.php" class="icon-btn">ออกจากระบบ</a>
            <?php else: ?>
                <a href="/login.php" class="icon-btn">👤 เข้าสู่ระบบ</a>
            <?php endif; ?>
        </div>
    </div>

    <nav class="main-nav">
        <div class="container">
            <a href="/index.php" class="<?= $currentSlug === '' && basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">หน้าแรก</a>
            <?php foreach ($navCategories as $cat): ?>
                <a href="/category.php?slug=<?= urlencode($cat['slug']) ?>" class="<?= $currentSlug === $cat['slug'] ? 'active' : '' ?>"><?= sanitize($cat['name']) ?></a>
            <?php endforeach; ?>
            <a href="/reviews.php">รีวิวลูกค้า</a>
        </div>
    </nav>
</header>
