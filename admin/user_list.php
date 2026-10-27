<?php
if (!defined('ADMIN_ROUTER')) {
    header('Location: ../login.php');
    exit;
}

$pdo = get_db();

// This page handles its own mutations (no separate action files) —
// same pattern as new_user.php / edit_user.php. Runs while output is
// still buffered by index.php, so header()+exit here is safe.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $targetId = (int) ($_POST['user_id'] ?? 0);
    $doing = (string) ($_POST['do'] ?? '');

    if ($targetId === (int) current_user()['id']) {
        flash_set('error', 'You cannot change your own account from here.');
    } elseif ($doing === 'toggle_role') {
        $role = (string) ($_POST['role'] ?? 'user');
        $role = in_array($role, ['user', 'admin'], true) ? $role : 'user';
        $stmt = $pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
        $stmt->execute([':role' => $role, ':id' => $targetId]);
        flash_set('success', 'User role updated.');
    } elseif ($doing === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $targetId]);
        flash_set('success', 'User deleted.');
    }

    header('Location: index.php?page=user_list');
    exit;
}

$users = $pdo->query('
    SELECT u.*, (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) AS booking_count
    FROM users u ORDER BY u.created_at DESC
')->fetchAll();
?>

<div class="admin-panel">
  <div class="admin-panel-head">
    <h2>All Users (<?= count($users) ?>)</h2>
    <a href="index.php?page=new_user" class="btn btn-sm btn-primary">+ New User</a>
  </div>
  <table class="admin-table">
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Bookings</th><th>Joined</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><a href="index.php?page=view_user&id=<?= (int) $u['id'] ?>"><?= h($u['name']) ?></a></td>
          <td><?= h($u['email']) ?></td>
          <td><?= h($u['phone'] ?: '—') ?></td>
          <td><span class="status-pill <?= $u['role'] === 'admin' ? 'upcoming' : 'completed' ?>"><?= h(ucfirst($u['role'])) ?></span></td>
          <td><?= (int) $u['booking_count'] ?></td>
          <td><?= h(date('M d, Y', strtotime($u['created_at']))) ?></td>
          <td class="admin-row-actions">
            <?php if ((int) $u['id'] !== (int) current_user()['id']): ?>
              <a href="index.php?page=edit_user&id=<?= (int) $u['id'] ?>" class="btn btn-sm btn-ghost">Edit</a>
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                <input type="hidden" name="do" value="toggle_role">
                <input type="hidden" name="role" value="<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>">
                <button type="submit" class="btn btn-sm btn-ghost"><?= $u['role'] === 'admin' ? 'Demote' : 'Make Admin' ?></button>
              </form>
              <form method="post" onsubmit="return confirm('Delete this user and all their bookings? This cannot be undone.');">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                <input type="hidden" name="do" value="delete">
                <button type="submit" class="btn btn-sm btn-ghost" style="color:var(--coral-dark);">Delete</button>
              </form>
            <?php else: ?>
              <span class="admin-sub">(you)</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
