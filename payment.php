<?php
require_once __DIR__ . '/classes.php';

require_login('login.php?next=' . urlencode('payment.php?order=' . (int) ($_GET['order'] ?? 0)));

$orderId = (int) ($_GET['order'] ?? 0);
$user = current_user();

$pdo = get_db();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id AND user_id = :user_id');
$stmt->execute([':id' => $orderId, ':user_id' => $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    flash_set('error', 'That order could not be found.');
    header('Location: cart.php');
    exit;
}

if ($order['status'] === 'paid') {
    header('Location: order_confirmation.php?order=' . $orderId);
    exit;
}
if ($order['status'] === 'cancelled') {
    flash_set('error', 'This order was cancelled.');
    header('Location: cart.php');
    exit;
}

$itemsStmt = $pdo->prepare('SELECT * FROM bookings WHERE order_id = :order_id');
$itemsStmt->execute([':order_id' => $orderId]);
$items = $itemsStmt->fetchAll();

$errors = [];
$old = ['card_name' => $user['name'], 'card_number' => '', 'expiry' => '', 'cvv' => ''];

$pageTitle = 'Payment — Tickets to Thrill';
$activePage = '';
require __DIR__ . '/header.php';
?>

<div class="page-hero">
  <div class="container">
    <div class="breadcrumb">HOME / PAYMENT</div>
    <h1>Complete your payment</h1>
    <p>Order #<?= (int) $order['id'] ?> · This is a demo payment page — no real card is charged.</p>
  </div>
</div>

<section>
  <div class="container cart-layout">
    <div>
      <div style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius); padding:26px;">
        <h3>Pay with card (demo)</h3>
        <p style="font-size:.82rem; color:var(--ink-soft); margin-top:-8px;">
          Enter any values — this simulated gateway never contacts a real bank.
        </p>
        <form action="actions/payment_confirm.php" method="post" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
          <div class="field<?= isset($errors['card_name']) ? ' error' : '' ?>">
            <label>Name on card</label>
            <input type="text" name="card_name" value="<?= h($old['card_name']) ?>">
            <div class="err-msg"><?= h($errors['card_name'] ?? '') ?></div>
          </div>
          <div class="field<?= isset($errors['card_number']) ? ' error' : '' ?>">
            <label>Card number</label>
            <input type="text" name="card_number" placeholder="4242 4242 4242 4242" maxlength="19">
            <div class="err-msg"><?= h($errors['card_number'] ?? '') ?></div>
          </div>
          <div class="row-2">
            <div class="field<?= isset($errors['expiry']) ? ' error' : '' ?>">
              <label>Expiry (MM/YY)</label>
              <input type="text" name="expiry" placeholder="12/29" maxlength="5">
              <div class="err-msg"><?= h($errors['expiry'] ?? '') ?></div>
            </div>
            <div class="field<?= isset($errors['cvv']) ? ' error' : '' ?>">
              <label>CVV</label>
              <input type="text" name="cvv" placeholder="123" maxlength="4">
              <div class="err-msg"><?= h($errors['cvv'] ?? '') ?></div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Pay <?= money((float) $order['total']) ?></button>
        </form>
        <form action="actions/order_cancel.php" method="post" style="margin-top:8px;">
          <?= csrf_field() ?>
          <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
          <button type="submit" class="btn btn-ghost btn-block">Cancel order</button>
        </form>
      </div>
    </div>

    <div class="summary-card">
      <h3>Order Summary</h3>
      <div class="summary-row"><span>Visit Date</span><span><?= $order['visit_date'] ? h(date('d M Y', strtotime($order['visit_date']))) : 'Not specified' ?></span></div>
      <?php foreach ($items as $item): ?>
        <div class="summary-row">
          <span><?= h($item['ticket_name']) ?> × <?= (int) $item['qty'] ?></span>
          <span><?= money((float) $item['price'] * (int) $item['qty']) ?></span>
        </div>
      <?php endforeach; ?>
      <div class="summary-row total"><span>Total</span><span><?= money((float) $order['total']) ?></span></div>
    </div>
  </div>
</section>

<?php
$footerNoMargin = false;
require __DIR__ . '/footer-simple.php';
?>
