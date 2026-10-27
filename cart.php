<?php
$pageTitle = 'My Cart — Tickets to Thrill';
$activePage = 'cart.php';
require __DIR__ . '/header.php';

$items = cart_items();
$totals = cart_totals();
$swatch = [
    'individual' => ['bg' => 'var(--teal)', 'icon' => '🎫'],
    'combo'      => ['bg' => 'var(--purple)', 'icon' => '🎁'],
    'offer'      => ['bg' => 'var(--coral)', 'icon' => '🔥'],
];
?>

<div class="page-hero">
  <div class="container">
    <div class="breadcrumb">HOME / MY CART</div>
    <h1>My Cart</h1>
    <p>Review your tickets before checkout.</p>
  </div>
</div>

<section>
  <div class="container">
    <?php if (empty($items)): ?>
      <div class="cart-layout">
        <div class="empty-state" style="grid-column:1/-1;">
          <span class="emoji">🎟️</span>
          <h2>Your cart is empty</h2>
          <p style="color:var(--ink-soft);">Looks like you haven't picked a ticket yet.</p>
          <a href="tickets.php" class="btn btn-primary">Browse Tickets</a>
        </div>
      </div>
    <?php else: ?>
      <div class="cart-layout">
        <div>
          <?php foreach ($items as $item): $sw = $swatch[$item['category']] ?? $swatch['individual']; ?>
          <div class="cart-item">
            <div class="swatch" style="background:<?= h($sw['bg']) ?>;"><?= $sw['icon'] ?></div>
            <div>
              <h4><?= h($item['name']) ?></h4>
              <div class="meta"><?= money($item['price']) ?> each</div>
              <form action="actions/cart_update.php" method="post" class="qty-stepper" style="margin-top:8px;">
                <?= csrf_field() ?>
                <input type="hidden" name="ticket_id" value="<?= h($item['id']) ?>">
                <button type="submit" name="step" value="-1">−</button>
                <input type="text" value="<?= (int) $item['qty'] ?>" readonly>
                <button type="submit" name="step" value="1">+</button>
              </form>
            </div>
            <div class="line-price"><?= money($item['price'] * $item['qty']) ?></div>
            <form action="actions/cart_remove.php" method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="ticket_id" value="<?= h($item['id']) ?>">
              <button type="submit" class="remove" aria-label="Remove item">✕</button>
            </form>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="summary-card">
          <h3>Order Summary</h3>
          <form action="actions/cart_promo.php" method="post" class="promo-row">
            <?= csrf_field() ?>
            <input type="text" name="promo_code" placeholder="Promo code (try THRILL10)" value="<?= h($_SESSION['promo_code'] ?? '') ?>">
            <button type="submit" class="btn btn-sm" style="background:var(--yellow); color:var(--indigo);">Apply</button>
          </form>
          <?php if (!empty($_SESSION['promo_message'])): ?>
            <div style="font-size:.78rem; color:<?= $_SESSION['promo_ok'] ? 'var(--yellow)' : '#FFB4BC' ?>; margin-bottom:4px;"><?= h($_SESSION['promo_message']) ?></div>
          <?php endif; ?>
          <div class="summary-row"><span>Subtotal</span><span><?= money($totals['subtotal']) ?></span></div>
          <div class="summary-row"><span>Discount</span><span>-<?= money($totals['discount']) ?></span></div>
          <div class="summary-row"><span>Tax (5%)</span><span><?= money($totals['tax']) ?></span></div>
          <div class="summary-row total"><span>Total</span><span><?= money($totals['total']) ?></span></div>
          <?php if (current_user()): ?>
            <form action="actions/checkout.php" method="post">
              <?= csrf_field() ?>
              <div class="field" style="margin-top:16px;">
                <label for="visit_date">When are you visiting?</label>
                <input type="date" id="visit_date" name="visit_date" required min="<?= h(date('Y-m-d')) ?>" max="<?= h(date('Y-m-d', strtotime('+1 year'))) ?>">
              </div>
              <button type="submit" class="btn btn-primary btn-block">Checkout</button>
            </form>
          <?php else: ?>
            <a href="login.php?next=cart.php" class="btn btn-primary btn-block" style="margin-top:16px;">Log in to Checkout</a>
            <p style="font-size:.78rem; color:var(--ink-soft); margin-top:8px;">You need an account to check out — it only takes a minute.</p>
          <?php endif; ?>
          <form action="actions/cart_clear.php" method="post">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-ghost btn-block" style="color:#D9D2F0; margin-top:6px;">Clear Cart</button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php
$footerLinkHref = 'tickets.php';
$footerLinkText = 'Continue browsing tickets →';
require __DIR__ . '/footer-simple.php';
?>
