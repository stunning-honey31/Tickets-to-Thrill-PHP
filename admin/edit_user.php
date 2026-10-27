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

$errors = [];
$old = ['name' => $targetUser['name'], 'phone' => $targetUser['phone'], 'role' => $targetUser['role']];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $old['name'] = trim((string) ($_POST['name'] ?? ''));
    $old['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $old['role'] = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';

    if ((int) $targetUser['id'] === (int) current_user()['id'] && $old['role'] !== 'admin') {
        $errors['role'] = 'You cannot remove your own admin access.';
    }

    $errors = array_merge($errors, v_collect([
        'name' => fn() => v_name($old['name']),
        'phone' => fn() => v_phone($old['phone']),
    ]));

    if (empty($errors)) {
        $update = $pdo->prepare('UPDATE users SET name = :name, phone = :phone, role = :role WHERE id = :id');
        $update->execute([
            ':name' => $old['name'],
            ':phone' => $old['phone'],
            ':role' => $old['role'],
            ':id' => $targetUser['id'],
        ]);
        flash_set('success', 'User updated.');
        header('Location: index.php?page=user_list');
        exit;
    }
}
?>

<div class="admin-panel">
  <div class="admin-panel-head"><h2>Edit User — <?= h($targetUser['email']) ?></h2></div>
  <form method="post" novalidate style="max-width:520px;">
    <?= csrf_field() ?>
    <div class="row-2">
      <div class="field<?= isset($errors['name']) ? ' error' : '' ?>">
        <label>Full name</label>
        <input type="text" name="name" value="<?= h($old['name']) ?>">
        <div class="err-msg"><?= h($errors['name'] ?? '') ?></div>
      </div>
      <div class="field<?= isset($errors['phone']) ? ' error' : '' ?>">
        <label>Phone</label>
        <input type="tel" name="phone" value="<?= h($old['phone']) ?>">
        <div class="err-msg"><?= h($errors['phone'] ?? '') ?></div>
      </div>
    </div>
    <div class="field">
      <label>Email</label>
      <input type="text" value="<?= h($targetUser['email']) ?>" readonly>
    </div>
    <div class="field<?= isset($errors['role']) ? ' error' : '' ?>">
      <label>Role</label>
      <select name="role">
        <option value="user"<?= $old['role'] === 'user' ? ' selected' : '' ?>>User</option>
        <option value="admin"<?= $old['role'] === 'admin' ? ' selected' : '' ?>>Admin</option>
      </select>
      <div class="err-msg"><?= h($errors['role'] ?? '') ?></div>
    </div>
    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="index.php?page=user_list" class="btn btn-ghost">Cancel</a>
  </form>
</div>
