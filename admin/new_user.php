<?php
if (!defined('ADMIN_ROUTER')) {
    header('Location: ../login.php');
    exit;
}

$pdo = get_db();
$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => '', 'role' => 'user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $old['name'] = trim((string) ($_POST['name'] ?? ''));
    $old['email'] = trim((string) ($_POST['email'] ?? ''));
    $old['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $old['role'] = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
    $password = (string) ($_POST['password'] ?? '');

    $errors = v_collect([
        'name' => fn() => v_name($old['name']),
        'email' => fn() => v_email($old['email']),
        'phone' => fn() => v_phone($old['phone']),
        'password' => fn() => v_password($password),
    ]);

    if (empty($errors)) {
        $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $existing->execute([':email' => $old['email']]);
        if ($existing->fetch()) {
            $errors['email'] = 'A user with that email already exists.';
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO users (name, email, phone, password_hash, role, created_at)
                VALUES (:name, :email, :phone, :hash, :role, :created_at)
            ');
            $stmt->execute([
                ':name' => $old['name'],
                ':email' => $old['email'],
                ':phone' => $old['phone'],
                ':hash' => password_hash($password, PASSWORD_DEFAULT),
                ':role' => $old['role'],
                ':created_at' => date('Y-m-d H:i:s'),
            ]);
            flash_set('success', 'User created.');
            header('Location: index.php?page=user_list');
            exit;
        }
    }
}
?>

<div class="admin-panel">
  <div class="admin-panel-head"><h2>New User</h2></div>
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
    <div class="field<?= isset($errors['email']) ? ' error' : '' ?>">
      <label>Email</label>
      <input type="email" name="email" value="<?= h($old['email']) ?>">
      <div class="err-msg"><?= h($errors['email'] ?? '') ?></div>
    </div>
    <div class="field<?= isset($errors['password']) ? ' error' : '' ?>">
      <label>Password</label>
      <input type="password" name="password" placeholder="8+ chars, 1 letter & 1 number">
      <div class="err-msg"><?= h($errors['password'] ?? '') ?></div>
    </div>
    <div class="field">
      <label>Role</label>
      <select name="role">
        <option value="user"<?= $old['role'] === 'user' ? ' selected' : '' ?>>User</option>
        <option value="admin"<?= $old['role'] === 'admin' ? ' selected' : '' ?>>Admin</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Create User</button>
    <a href="index.php?page=user_list" class="btn btn-ghost">Cancel</a>
  </form>
</div>
