<?php
require_once __DIR__ . '/classes.php';

$user = current_user();
$profileErrors = [];
$passwordErrors = [];
$profileMessage = null;
$passwordMessage = null;

// Both forms post back to this same page; a hidden "form" field tells
// us which one was submitted.
if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $which = (string) ($_POST['form'] ?? '');

    if (!csrf_check()) {
        if ($which === 'password') {
            $passwordMessage = ['type' => 'error', 'text' => 'Your session expired. Please try again.'];
        } else {
            $profileMessage = ['type' => 'error', 'text' => 'Your session expired. Please try again.'];
        }
    } elseif ($which === 'profile') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));

        $profileErrors = v_collect([
            'name' => fn() => v_name($name),
            'phone' => fn() => v_phone($phone),
        ]);

        if (empty($profileErrors)) {
            $pdo = get_db();
            $stmt = $pdo->prepare('UPDATE users SET name = :name, phone = :phone WHERE id = :id');
            $stmt->execute([':name' => $name, ':phone' => $phone, ':id' => $user['id']]);

            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['phone'] = $phone;
            $user = current_user();

            $profileMessage = ['type' => 'success', 'text' => 'Profile updated.'];
        }
    } elseif ($which === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
        $stmt->execute([':id' => $user['id']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password_hash'])) {
            $passwordErrors['current_password'] = 'Current password is incorrect.';
        }
        $passwordErrors = array_merge($passwordErrors, v_collect([
            'new_password' => fn() => v_password($new),
            'confirm_password' => fn() => v_matches($confirm, $new, 'New passwords'),
        ]));

        if (empty($passwordErrors)) {
            $update = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
            $update->execute([':hash' => password_hash($new, PASSWORD_DEFAULT), ':id' => $user['id']]);
            $passwordMessage = ['type' => 'success', 'text' => 'Password changed.'];
        }
    }
}

$pageTitle = 'My Account — Tickets to Thrill';
$activePage = 'myaccount.php';
require __DIR__ . '/header.php';

if ($user) {
    $pdo = get_db();

    $ordersStmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC');
    $ordersStmt->execute([':user_id' => $user['id']]);
    $orders = $ordersStmt->fetchAll();

    $bookingsStmt = $pdo->prepare('SELECT * FROM bookings WHERE user_id = :user_id ORDER BY created_at DESC');
    $bookingsStmt->execute([':user_id' => $user['id']]);
    $bookings = $bookingsStmt->fetchAll();

    // Group line items by their order so each order renders as one card.
    $itemsByOrder = [];
    $legacyBookings = []; // bookings with no order_id (from before orders existed)
    foreach ($bookings as $b) {
        if ($b['order_id'] !== null) {
            $itemsByOrder[$b['order_id']][] = $b;
        } else {
            $legacyBookings[] = $b;
        }
    }

    $upcomingCount = 0;
    $pendingCount = 0;
    foreach ($bookings as $b) {
        if ($b['status'] === 'upcoming') {
            $upcomingCount++;
        } elseif ($b['status'] === 'pending_payment') {
            $pendingCount++;
        }
    }
}

$statusColors = [
    'pending_payment' => 'var(--yellow-dark)',
    'upcoming' => 'var(--teal)',
    'completed' => '#B0A6C9',
    'cancelled' => '#D98E96',
];
?>

<div class="page-hero">
  <div class="container">
    <div class="breadcrumb">HOME / MY ACCOUNT</div>
    <h1>My Account</h1>
    <p>Your profile, bookings and saved details.</p>
  </div>
</div>

<?php if (!$user): ?>
<section>
  <div class="container" style="text-align:center;">
    <h2>You need to log in first</h2>
    <p style="color:var(--ink-soft);">Please log in to view your account.</p>
    <a href="login.php" class="btn btn-primary">Go to Login</a>
  </div>
</section>
<?php else: ?>
<section id="accountContent">
  <div class="container account-layout">
    <aside class="account-side">
      <div class="avatar-circle"><?= h(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></div>
      <h3><?= h($user['name']) ?></h3>
      <div class="email"><?= h($user['email']) ?></div>
      <nav class="account-nav">
        <a href="myaccount.php" class="active">📋 Overview</a>
        <a href="tickets.php">🎫 Book More Tickets</a>
        <a href="cart.php">🛒 My Cart</a>
        <?php if (is_admin()): ?><a href="admin/index.php">🛠️ Admin Panel</a><?php endif; ?>
        <a href="logout.php">⎋ Log Out</a>
      </nav>
    </aside>

    <div>
      <div class="stat-tiles">
        <div class="stat-tile"><strong><?= (int) $upcomingCount ?></strong><span>Upcoming Visits</span></div>
        <div class="stat-tile"><strong><?= (int) $pendingCount ?></strong><span>Pending Payment</span></div>
        <div class="stat-tile"><strong><?= count($orders) ?></strong><span>Total Orders</span></div>
      </div>

      <div class="section-head" style="margin-bottom:22px;">
        <span class="eyebrow">🎟 Orders & Tickets</span>
        <h2>Your bookings</h2>
      </div>

      <div id="bookingsList">
        <?php if (empty($orders) && empty($legacyBookings)): ?>
          <div class="empty-state">
            <span class="emoji">🎫</span>
            <h2>No bookings yet</h2>
            <p style="color:var(--ink-soft);">Browse tickets and check out to see your bookings here.</p>
            <a href="tickets.php" class="btn btn-primary">Browse Tickets</a>
          </div>
        <?php else: ?>
          <?php foreach ($orders as $order): $orderItems = $itemsByOrder[$order['id']] ?? []; ?>
            <div style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius); padding:20px 22px; margin-bottom:14px;">
              <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:12px;">
                <div>
                  <strong style="font-family:var(--font-display); color:var(--indigo);">Order #<?= (int) $order['id'] ?></strong>
                  <span style="font-family:var(--font-mono); font-size:.78rem; color:var(--ink-soft); margin-left:8px;">Booked <?= h(date('M d, Y', strtotime($order['created_at']))) ?></span>
                  <?php if (!empty($order['visit_date'])): ?>
                    <span style="font-family:var(--font-mono); font-size:.78rem; color:var(--teal-dark); margin-left:8px;">🗓 Visiting <?= h(date('M d, Y', strtotime($order['visit_date']))) ?></span>
                  <?php endif; ?>
                </div>
                <span class="status-pill <?= h($order['status'] === 'paid' ? 'upcoming' : ($order['status'] === 'pending' ? 'pending_payment' : 'cancelled')) ?>">
                  <?= h($order['status'] === 'paid' ? 'Paid' : ($order['status'] === 'pending' ? 'Payment Pending' : 'Cancelled')) ?>
                </span>
              </div>

              <?php foreach ($orderItems as $b): ?>
                <div class="booking-row">
                  <div class="swatch" style="background:<?= h($statusColors[$b['status']] ?? '#B0A6C9') ?>; width:44px; height:44px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff;">🎫</div>
                  <div>
                    <h4 style="margin:0 0 2px; font-size:1rem;"><?= h($b['ticket_name']) ?></h4>
                    <div style="font-family:var(--font-mono); font-size:.78rem; color:var(--ink-soft);">Qty <?= (int) $b['qty'] ?></div>
                  </div>
                  <span class="status-pill <?= h($b['status']) ?>"><?= h(booking_status_label($b['status'])) ?></span>
                  <div style="font-family:var(--font-display); color:var(--indigo);"><?= money($b['price'] * $b['qty']) ?></div>
                  <?php if ($b['status'] === 'upcoming'): ?>
                    <form action="actions/booking_cancel.php" method="post" onsubmit="return confirm('Cancel this ticket? A refund of <?= h(money($b['price'] * $b['qty'])) ?> will be issued to your original payment method within 5–7 business days.');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="booking_id" value="<?= (int) $b['id'] ?>">
                      <button type="submit" class="icon-remove-btn" aria-label="Cancel ticket" title="Cancel ticket">✕</button>
                    </form>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>

              <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px; padding-top:12px; border-top:1px dashed var(--line); flex-wrap:wrap; gap:10px;">
                <strong>Total: <?= money((float) $order['total']) ?></strong>
                <?php if ($order['status'] === 'paid'): ?>
                  <a href="ticket_pdf.php?order=<?= (int) $order['id'] ?>" target="_blank" rel="noopener" class="btn btn-sm btn-secondary">📄 Download Ticket (PDF)</a>
                <?php elseif ($order['status'] === 'pending'): ?>
                  <a href="payment.php?order=<?= (int) $order['id'] ?>" class="btn btn-sm btn-primary">Complete Payment</a>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>

          <?php foreach ($legacyBookings as $b): ?>
            <div class="booking-row">
              <div class="swatch" style="background:<?= h($statusColors[$b['status']] ?? '#B0A6C9') ?>; width:44px; height:44px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff;">🎫</div>
              <div>
                <h4 style="margin:0 0 2px; font-size:1rem;"><?= h($b['ticket_name']) ?></h4>
                <div style="font-family:var(--font-mono); font-size:.78rem; color:var(--ink-soft);"><?= h(date('M d, Y', strtotime($b['created_at']))) ?> · Qty <?= (int) $b['qty'] ?></div>
              </div>
              <span class="status-pill <?= h($b['status']) ?>"><?= h(booking_status_label($b['status'])) ?></span>
              <div style="font-family:var(--font-display); color:var(--indigo);"><?= money($b['price'] * $b['qty']) ?></div>
              <?php if ($b['status'] === 'upcoming'): ?>
                <form action="actions/booking_cancel.php" method="post" onsubmit="return confirm('Cancel this booking? A refund of <?= h(money($b['price'] * $b['qty'])) ?> will be issued to your original payment method within 5–7 business days.');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="booking_id" value="<?= (int) $b['id'] ?>">
                  <button type="submit" class="icon-remove-btn" aria-label="Cancel booking" title="Cancel booking">✕</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius); padding:26px; margin-top:16px;">
        <h3>Edit Profile</h3>
        <?php if ($profileMessage): ?>
          <div class="form-msg show <?= h($profileMessage['type']) ?>"><?= h($profileMessage['text']) ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="form" value="profile">
          <div class="row-2">
            <div class="field<?= isset($profileErrors['name']) ? ' error' : '' ?>">
              <label for="pf_name">Full name</label>
              <input type="text" id="pf_name" name="name" value="<?= h($user['name']) ?>">
              <div class="err-msg"><?= h($profileErrors['name'] ?? '') ?></div>
            </div>
            <div class="field<?= isset($profileErrors['phone']) ? ' error' : '' ?>">
              <label for="pf_phone">Phone</label>
              <input type="tel" id="pf_phone" name="phone" value="<?= h($user['phone']) ?>">
              <div class="err-msg"><?= h($profileErrors['phone'] ?? '') ?></div>
            </div>
          </div>
          <div class="field">
            <label>Email</label>
            <input type="text" value="<?= h($user['email']) ?>" readonly>
          </div>
          <button type="submit" class="btn btn-secondary">Save Profile</button>
        </form>
      </div>

      <div style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius); padding:26px; margin-top:16px;">
        <h3>Change Password</h3>
        <?php if ($passwordMessage): ?>
          <div class="form-msg show <?= h($passwordMessage['type']) ?>"><?= h($passwordMessage['text']) ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="form" value="password">
          <div class="field<?= isset($passwordErrors['current_password']) ? ' error' : '' ?>">
            <label for="current_password">Current password</label>
            <input type="password" id="current_password" name="current_password">
            <div class="err-msg"><?= h($passwordErrors['current_password'] ?? '') ?></div>
          </div>
          <div class="row-2">
            <div class="field<?= isset($passwordErrors['new_password']) ? ' error' : '' ?>">
              <label for="new_password">New password</label>
              <input type="password" id="new_password" name="new_password" placeholder="8+ chars, 1 letter & 1 number">
              <div class="err-msg"><?= h($passwordErrors['new_password'] ?? '') ?></div>
            </div>
            <div class="field<?= isset($passwordErrors['confirm_password']) ? ' error' : '' ?>">
              <label for="confirm_password">Confirm new password</label>
              <input type="password" id="confirm_password" name="confirm_password">
              <div class="err-msg"><?= h($passwordErrors['confirm_password'] ?? '') ?></div>
            </div>
          </div>
          <button type="submit" class="btn btn-secondary">Change Password</button>
        </form>
        <p style="font-size:.8rem; color:var(--ink-soft); margin-top:14px;">Member since <?= h(date('F j, Y', strtotime($user['created_at']))) ?>.</p>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
$footerLinkHref = 'tickets.php';
$footerLinkText = 'Browse more tickets →';
require __DIR__ . '/footer-simple.php';
?>
