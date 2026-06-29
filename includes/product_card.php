<?php
/**
 * includes/product_card.php
 * ใช้ในลูป foreach โดยตัวแปร $p ต้องมี field จากตาราง products
 * เรียกใช้แบบ: foreach ($products as $p) { include 'product_card.php'; }
 */
$typeLabels = [
    'blind_single' => 'สุ่มเดี่ยว',
    'full_set'     => 'ยกกล่อง',
    'check_card'   => 'เช็กการ์ด',
    'figure'       => 'ของเล่น/ตุ๊กตา',
];
$hasDiscount = !empty($p['sale_price']) && $p['sale_price'] < $p['price'];
$displayPrice = $hasDiscount ? $p['sale_price'] : $p['price'];
?>
<div class="product-card">
    <div class="product-thumb">
        <?php if (!empty($p['cover_image'])): ?>
            <img src="<?= sanitize($p['cover_image']) ?>" alt="<?= sanitize($p['name']) ?>" loading="lazy">
        <?php else: ?>
            🎲
        <?php endif; ?>

        <?php if (!empty($p['badge_label'])): ?>
            <?php
                $badgeClass = 'badge-new';
                if (stripos($p['badge_label'], 'rare') !== false || mb_strpos($p['badge_label'], 'หา') !== false) $badgeClass = 'badge-rare';
                elseif (stripos($p['badge_label'], 'pre') !== false) $badgeClass = 'badge-preorder';
                elseif (mb_strpos($p['badge_label'], 'เหลือ') !== false) $badgeClass = 'badge-lowstock';
            ?>
            <span class="badge <?= $badgeClass ?>"><?= sanitize($p['badge_label']) ?></span>
        <?php elseif ($p['stock_qty'] > 0 && $p['stock_qty'] <= 3): ?>
            <span class="badge badge-lowstock">เหลือ <?= (int)$p['stock_qty'] ?> ชิ้น!</span>
        <?php endif; ?>
    </div>
    <div class="product-info">
        <span class="product-type-tag"><?= $typeLabels[$p['product_type']] ?? '' ?></span>
        <h3 class="product-name"><a href="/product.php?slug=<?= urlencode($p['slug']) ?>"><?= sanitize($p['name']) ?></a></h3>
        <div class="product-price-row">
            <span class="price-now">฿<?= formatPrice($displayPrice) ?></span>
            <?php if ($hasDiscount): ?>
                <span class="price-old">฿<?= formatPrice($p['price']) ?></span>
            <?php endif; ?>
        </div>
        <?php if ($p['stock_qty'] <= 0 && !$p['is_preorder']): ?>
            <button class="btn btn-outline btn-block" disabled>สินค้าหมด</button>
        <?php else: ?>
            <a href="/product.php?slug=<?= urlencode($p['slug']) ?>" class="btn btn-primary btn-block">ดูรายละเอียด</a>
        <?php endif; ?>
    </div>
</div>
