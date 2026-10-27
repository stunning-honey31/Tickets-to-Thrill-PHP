<?php
if (!defined('ADMIN_ROUTER')) {
    header('Location: ../login.php');
    exit;
}

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? '');
    $allowed = ['pending_payment', 'upcoming', 'completed', 'cancelled'];

    if (in_array($status, $allowed, true)) {
        $current = $pdo->prepare('SELECT * FROM bookings WHERE id = :id');
        $current->execute([':id' => $bookingId]);
        $booking = $current->fetch();

        $pdo->prepare('UPDATE bookings SET status = :status WHERE id = :id')
            ->execute([':status' => $status, ':id' => $bookingId]);

        if ($booking && $booking['status'] === 'upcoming' && $status === 'cancelled') {
            $refundAmount = (float) $booking['price'] * (int) $booking['qty'];
            flash_set(
                'success',
                'Ticket cancelled. Remember to process a refund of ' . money($refundAmount) .
                ' to the customer (this is a simulated gateway, so there\'s no real charge to reverse).'
            );
        } else {
            flash_set('success', 'Ticket status updated.');
        }
    }

    $redirectStatus = (string) ($_POST['redirect_status'] ?? 'all');
    header('Location: index.php?page=ticket_list&status=' . urlencode($redirectStatus));
    exit;
}

$filter = (string) ($_GET['status'] ?? 'all');
$allowedFilters = ['all', 'pending_payment', 'upcoming', 'completed', 'cancelled'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}

$sql = '
    SELECT b.*, u.name AS user_name, u.email AS user_email, o.visit_date AS visit_date
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    LEFT JOIN orders o ON o.id = b.order_id
';
$params = [];
if ($filter !== 'all') {
    $sql .= ' WHERE b.status = :status';
    $params[':status'] = $filter;
}
$sql .= ' ORDER BY b.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$statusLabels = booking_status_labels();
?>

<div class="admin-panel">
  <div class="admin-panel-head">
    <h2>All Tickets (<?= count($bookings) ?>)</h2>
    <div class="admin-filter-tabs">
      <?php foreach ($allowedFilters as $opt): ?>
        <a href="index.php?page=ticket_list&status=<?= h($opt) ?>" class="<?= $filter === $opt ? 'active' : '' ?>"><?= h($opt === 'all' ? 'All' : booking_status_label($opt)) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <table class="admin-table">
    <thead><tr><th>Customer</th><th>Ticket</th><th>Category</th><th>Qty</th><th>Amount</th><th>Status</th><th>Booked</th><th>Visit Date</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($bookings)): ?>
        <tr><td colspan="9" class="admin-empty">No tickets match this filter.</td></tr>
      <?php else: foreach ($bookings as $b): ?>
        <tr>
          <td><a href="index.php?page=view_user&id=<?= (int) $b['user_id'] ?>"><?= h($b['user_name']) ?></a><br><span class="admin-sub"><?= h($b['user_email']) ?></span></td>
          <td><?= h($b['ticket_name']) ?></td>
          <td><?= h(ucfirst($b['category'])) ?></td>
          <td><?= (int) $b['qty'] ?></td>
          <td><?= money($b['price'] * $b['qty']) ?></td>
          <td><span class="status-pill <?= h($b['status']) ?>"><?= h($statusLabels[$b['status']] ?? ucfirst($b['status'])) ?></span></td>
          <td><?= h(date('M d, Y', strtotime($b['created_at']))) ?></td>
          <td><?= !empty($b['visit_date']) ? h(date('M d, Y', strtotime($b['visit_date']))) : '—' ?></td>
          <td class="admin-row-actions">
            <?php if ($b['order_id'] !== null && in_array($b['status'], ['upcoming', 'completed'], true)): ?>
              <a href="../ticket_pdf.php?order=<?= (int) $b['order_id'] ?>" target="_blank" rel="noopener" class="btn btn-sm btn-ghost">📄 PDF</a>
            <?php endif; ?>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="booking_id" value="<?= (int) $b['id'] ?>">
              <input type="hidden" name="redirect_status" value="<?= h($filter) ?>">
              <select name="status" onchange="this.form.submit()">
                <?php foreach ($statusLabels as $key => $label): ?>
                  <option value="<?= h($key) ?>"<?= $b['status'] === $key ? ' selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
