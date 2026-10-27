<?php
require_once __DIR__ . '/classes.php';

require_login('login.php');

$orderId = (int) ($_GET['order'] ?? 0);
$user = current_user();

$pdo = get_db();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id AND user_id = :user_id');
$stmt->execute([':id' => $orderId, ':user_id' => $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    flash_set('error', 'That order could not be found.');
    header('Location: myaccount.php');
    exit;
}

$itemsStmt = $pdo->prepare('SELECT * FROM bookings WHERE order_id = :order_id');
$itemsStmt->execute([':order_id' => $orderId]);
$items = $itemsStmt->fetchAll();

$pageTitle = 'Order Confirmed — Tickets to Thrill';
$activePage = '';
require __DIR__ . '/header.php';
?>

<section>
  <div class="container" style="max-width:640px;">
    <?php if ($order['status'] === 'paid'): ?>
      <div class="empty-state">
        <span class="emoji">🎉</span>
        <h2>Payment successful!</h2>
        <p style="color:var(--ink-soft);">Order #<?= (int) $order['id'] ?> is confirmed. Your e-ticket has been emailed to <?= h($user['email']) ?>.</p>
        <a href="ticket_pdf.php?order=<?= (int) $order['id'] ?>" class="btn btn-primary" target="_blank" rel="noopener">Download PDF Ticket</a>
        <a href="myaccount.php" class="btn btn-ghost" style="margin-top:10px;">Go to My Account</a>
      </div>

      <div class="summary-card" style="margin-top:24px;">
        <h3>Order Summary</h3>
        <div class="summary-row"><span>Visit Date</span><span><?= $order['visit_date'] ? h(date('d M Y', strtotime($order['visit_date']))) : 'Not specified' ?></span></div>
        <?php foreach ($items as $item): ?>
          <div class="summary-row">
            <span><?= h($item['ticket_name']) ?> × <?= (int) $item['qty'] ?></span>
            <span><?= money((float) $item['price'] * (int) $item['qty']) ?></span>
          </div>
        <?php endforeach; ?>
        <div class="summary-row total"><span>Total Paid</span><span><?= money((float) $order['total']) ?></span></div>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <span class="emoji">⏳</span>
        <h2>This order isn't paid yet</h2>
        <p style="color:var(--ink-soft);">Order #<?= (int) $order['id'] ?> is still <?= h($order['status']) ?>.</p>
        <?php if ($order['status'] === 'pending'): ?>
          <a href="payment.php?order=<?= (int) $order['id'] ?>" class="btn btn-primary">Complete Payment</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/footer-simple.php'; ?>
