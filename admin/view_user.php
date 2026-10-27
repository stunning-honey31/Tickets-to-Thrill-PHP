<?php
if (!defined('ADMIN_ROUTER')) {
    header('Location: ../login.php');
    exit;
}

$pdo = get_db();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute([':id' => $id]);
$targetUser = $stmt->fetch();

if (!$targetUser) {
    echo '<div class="admin-panel"><p class="admin-empty">User not found.</p></div>';
    return;
}

$bookingsStmt = $pdo->prepare('
    SELECT b.*, o.visit_date AS visit_date
    FROM bookings b
    LEFT JOIN orders o ON o.id = b.order_id
    WHERE b.user_id = :id ORDER BY b.created_at DESC
');
$bookingsStmt->execute([':id' => $id]);
$bookings = $bookingsStmt->fetchAll();

$statusLabels = booking_status_labels();
?>

<div class="admin-panel">
  <div class="admin-panel-head">
    <h2><?= h($targetUser['name']) ?></h2>
    <a href="index.php?page=edit_user&id=<?= (int) $targetUser['id'] ?>" class="btn btn-sm btn-ghost">Edit</a>
  </div>
  <div class="row-2">
    <div class="field"><label>Email</label><input type="text" value="<?= h($targetUser['email']) ?>" readonly></div>
    <div class="field"><label>Phone</label><input type="text" value="<?= h($targetUser['phone'] ?: '—') ?>" readonly></div>
  </div>
  <div class="row-2">
    <div class="field"><label>Role</label><input type="text" value="<?= h(ucfirst($targetUser['role'])) ?>" readonly></div>
    <div class="field"><label>Joined</label><input type="text" value="<?= h(date('F j, Y', strtotime($targetUser['created_at']))) ?>" readonly></div>
  </div>
</div>

<div class="admin-panel">
  <div class="admin-panel-head"><h2>Tickets booked (<?= count($bookings) ?>)</h2></div>
  <table class="admin-table">
    <thead><tr><th>Ticket</th><th>Qty</th><th>Amount</th><th>Status</th><th>Booked</th><th>Visit Date</th><th></th></tr></thead>
    <tbody>
      <?php if (empty($bookings)): ?>
        <tr><td colspan="7" class="admin-empty">No bookings yet.</td></tr>
      <?php else: foreach ($bookings as $b): ?>
        <tr>
          <td><?= h($b['ticket_name']) ?></td>
          <td><?= (int) $b['qty'] ?></td>
          <td><?= money($b['price'] * $b['qty']) ?></td>
          <td><span class="status-pill <?= h($b['status']) ?>"><?= h($statusLabels[$b['status']] ?? ucfirst($b['status'])) ?></span></td>
          <td><?= h(date('M d, Y', strtotime($b['created_at']))) ?></td>
          <td><?= !empty($b['visit_date']) ? h(date('M d, Y', strtotime($b['visit_date']))) : '—' ?></td>
          <td>
            <?php if ($b['order_id'] !== null && in_array($b['status'], ['upcoming', 'completed'], true)): ?>
              <a href="../ticket_pdf.php?order=<?= (int) $b['order_id'] ?>" target="_blank" rel="noopener" class="btn btn-sm btn-ghost">📄 PDF</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
