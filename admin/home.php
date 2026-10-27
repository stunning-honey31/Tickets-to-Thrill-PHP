<?php
if (!defined('ADMIN_ROUTER')) {
    header('Location: ../login.php');
    exit;
}

$pdo = get_db();

$userCount = (int) $pdo->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
$bookingCount = (int) $pdo->query('SELECT COUNT(*) AS c FROM bookings WHERE status != "pending_payment"')->fetch()['c'];
$revenue = (float) $pdo->query('SELECT COALESCE(SUM(total), 0) AS total FROM orders WHERE status = "paid"')->fetch()['total'];
$unreadMessages = (int) $pdo->query('SELECT COUNT(*) AS c FROM messages WHERE is_read = 0')->fetch()['c'];

$recentBookings = $pdo->query('
    SELECT b.*, u.name AS user_name, u.email AS user_email, o.visit_date AS visit_date
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    LEFT JOIN orders o ON o.id = b.order_id
    ORDER BY b.created_at DESC LIMIT 8
')->fetchAll();

$recentMessages = $pdo->query('
    SELECT * FROM messages ORDER BY created_at DESC LIMIT 5
')->fetchAll();

$statusLabels = booking_status_labels();
?>

<div class="admin-stat-grid">
  <div class="admin-stat-card"><span class="label">Total Users</span><strong><?= $userCount ?></strong></div>
  <div class="admin-stat-card"><span class="label">Total Tickets Booked</span><strong><?= $bookingCount ?></strong></div>
  <div class="admin-stat-card"><span class="label">Revenue (booked)</span><strong><?= money($revenue) ?></strong></div>
  <div class="admin-stat-card"><span class="label">Unread Messages</span><strong><?= $unreadMessages ?></strong></div>
</div>

<div class="admin-panel">
  <div class="admin-panel-head">
    <h2>Recent Tickets</h2>
    <a href="index.php?page=ticket_list">View all →</a>
  </div>
  <table class="admin-table">
    <thead><tr><th>Customer</th><th>Ticket</th><th>Qty</th><th>Amount</th><th>Status</th><th>Visit Date</th><th>Booked</th></tr></thead>
    <tbody>
      <?php if (empty($recentBookings)): ?>
        <tr><td colspan="7" class="admin-empty">No tickets booked yet.</td></tr>
      <?php else: foreach ($recentBookings as $b): ?>
        <tr>
          <td><?= h($b['user_name']) ?><br><span class="admin-sub"><?= h($b['user_email']) ?></span></td>
          <td><?= h($b['ticket_name']) ?></td>
          <td><?= (int) $b['qty'] ?></td>
          <td><?= money($b['price'] * $b['qty']) ?></td>
          <td><span class="status-pill <?= h($b['status']) ?>"><?= h($statusLabels[$b['status']] ?? ucfirst($b['status'])) ?></span></td>
          <td><?= !empty($b['visit_date']) ? h(date('M d, Y', strtotime($b['visit_date']))) : '—' ?></td>
          <td><?= h(date('M d, Y', strtotime($b['created_at']))) ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<div class="admin-panel">
  <div class="admin-panel-head">
    <h2>Recent Messages</h2>
    <a href="index.php?page=messages">View all →</a>
  </div>
  <table class="admin-table">
    <thead><tr><th>From</th><th>Subject</th><th>Received</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($recentMessages)): ?>
        <tr><td colspan="4" class="admin-empty">No messages yet.</td></tr>
      <?php else: foreach ($recentMessages as $m): ?>
        <tr>
          <td><?= h($m['name']) ?><br><span class="admin-sub"><?= h($m['email']) ?></span></td>
          <td><?= h($m['subject']) ?></td>
          <td><?= h(date('M d, Y', strtotime($m['created_at']))) ?></td>
          <td><?php if (!$m['is_read']): ?><span class="status-pill upcoming">New</span><?php endif; ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
