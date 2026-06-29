<?php
require_once __DIR__ . '/../includes/init.php';
$pageTitle = 'บัญชีของฉัน';

if (!isLoggedIn()) redirect('/login.php');

$stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
$stmt->execute([currentMemberId()]);
$member = $stmt->fetch();

$ordersStmt = $pdo->prepare("SELECT * FROM orders WHERE member_id = ? ORDER BY created_at DESC LIMIT 10");
$ordersStmt->execute([currentMemberId()]);
$orders = $ordersStmt->fetchAll();

$addrStmt = $pdo->prepare("SELECT * FROM member_addresses WHERE member_id = ?");
$addrStmt->execute([currentMemberId()]);
$addresses = $addrStmt->fetchAll();

$statusLabels = [
    'pending' => 'รอชำระเงิน', 'paid' => 'ชำระเงินแล้ว', 'processing' => 'กำลังจัดเตรียม',
    'shipped' => 'จัดส่งแล้ว', 'completed' => 'สำเร็จ', 'cancelled' => 'ยกเลิก',
];
$statusColors = [
    'pending' => 'var(--color-danger)', 'paid' => 'var(--color-mint)', 'processing' => 'var(--color-honey-dark)',
    'shipped' => 'var(--color-honey-dark)', 'completed' => 'var(--color-mint)', 'cancelled' => 'var(--color-ink-soft)',
];

require __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:10px;">
            <div>
                <h2>สวัสดี, <?= sanitize($member['full_name']) ?> 👋</h2>
                <p style="color:var(--color-ink-soft);">✨ แต้มสะสมของคุณ: <strong style="color:var(--color-honey-dark);"><?= (int)$member['points'] ?> แต้ม</strong></p>
            </div>
        </div>

        <h3 style="font-size:17px;">📦 ประวัติคำสั่งซื้อ</h3>
        <?php if (empty($orders)): ?>
            <p style="color:var(--color-ink-soft);">คุณยังไม่มีคำสั่งซื้อ</p>
        <?php else: ?>
        <div style="display:grid; gap:12px; margin-bottom:30px;">
            <?php foreach ($orders as $o): ?>
            <div style="background:#fff; border:2px solid var(--color-border); border-radius:14px; padding:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <div>
                    <p style="font-weight:700; margin:0;"><?= sanitize($o['order_code']) ?></p>
                    <p style="font-size:13px; color:var(--color-ink-soft); margin:2px 0 0;"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></p>
                </div>
                <span style="font-weight:700; color:<?= $statusColors[$o['order_status']] ?>;"><?= $statusLabels[$o['order_status']] ?></span>
                <span style="font-weight:700;">฿<?= formatPrice($o['total_amount']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <h3 style="font-size:17px;">📍 ที่อยู่จัดส่งที่บันทึกไว้</h3>
        <?php if (empty($addresses)): ?>
            <p style="color:var(--color-ink-soft);">ยังไม่มีที่อยู่ที่บันทึกไว้ (ระบบจะบันทึกอัตโนมัติจากการสั่งซื้อครั้งถัดไป)</p>
        <?php else: ?>
        <div style="display:grid; gap:10px;">
            <?php foreach ($addresses as $a): ?>
            <div style="background:#fff; border:2px solid var(--color-border); border-radius:14px; padding:14px; font-size:14px;">
                <strong><?= sanitize($a['recipient_name']) ?></strong> · <?= sanitize($a['phone']) ?><br>
                <?= sanitize($a['address_line']) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
