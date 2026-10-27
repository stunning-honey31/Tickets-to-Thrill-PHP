<?php
require_once __DIR__ . '/classes.php';

$next = safe_next_url($_GET['next'] ?? ($_POST['next'] ?? null), '');

if (current_user()) {
    header('Location: ' . ($next !== '' ? $next : 'myaccount.php'));
    exit;
}

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];
$formMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $formMessage = ['type' => 'error', 'text' => 'Your session expired. Please try again.'];
    } else {
        $old['name'] = trim((string) ($_POST['name'] ?? ''));
        $old['email'] = trim((string) ($_POST['email'] ?? ''));
        $old['phone'] = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');
        $terms = isset($_POST['terms']);

        $errors = v_collect([
            'name' => fn() => v_name($old['name']),
            'email' => fn() => v_email($old['email']),
            'phone' => fn() => v_phone($old['phone']),
            'password' => fn() => v_password($password),
            'confirm' => fn() => v_matches($confirm, $password, 'Passwords'),
        ]);
        if (!$terms) {
            $formMessage = ['type' => 'error', 'text' => 'Please agree to the Terms & Privacy Policy to continue.'];
        }

        if (empty($errors) && $terms) {
            $pdo = get_db();
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $stmt->execute([':email' => $old['email']]);

            if ($stmt->fetch()) {
                $formMessage = ['type' => 'error', 'text' => 'An account with that email already exists.'];
            } else {
                $now = date('Y-m-d H:i:s');
                $insert = $pdo->prepare('
                    INSERT INTO users (name, email, phone, password_hash, role, created_at)
                    VALUES (:name, :email, :phone, :password_hash, "user", :created_at)
                ');
                $insert->execute([
                    ':name' => $old['name'],
                    ':email' => $old['email'],
                    ':phone' => $old['phone'],
                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    ':created_at' => $now,
                ]);

                $userId = (int) $pdo->lastInsertId();
                log_in_user([
                    'id' => $userId,
                    'name' => $old['name'],
                    'email' => $old['email'],
                    'phone' => $old['phone'],
                    'role' => 'user',
                    'created_at' => $now,
                ]);

                header('Location: ' . ($next !== '' ? $next : 'myaccount.php'));
                exit;
            }
        }
    }
}

$pageTitle = 'Register — Tickets to Thrill';
require __DIR__ . '/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card" style="max-width:520px;">
    <h1>Create your account 🎡</h1>
    <p class="sub">Book faster and keep every ticket in one place.</p>
    <?php if ($formMessage): ?>
      <div class="form-msg show <?= h($formMessage['type']) ?>"><?= h($formMessage['text']) ?></div>
    <?php endif; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= h($next) ?>">
      <div class="field<?= isset($errors['name']) ? ' error' : '' ?>">
        <label for="name">Full name</label>
        <input type="text" id="name" name="name" placeholder="Jordan Rivera" value="<?= h($old['name']) ?>">
        <div class="err-msg"><?= h($errors['name'] ?? 'Please enter your full name.') ?></div>
      </div>
      <div class="row-2">
        <div class="field<?= isset($errors['email']) ? ' error' : '' ?>">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" placeholder="you@email.com" value="<?= h($old['email']) ?>">
          <div class="err-msg"><?= h($errors['email'] ?? 'Please enter a valid email address.') ?></div>
        </div>
        <div class="field<?= isset($errors['phone']) ? ' error' : '' ?>">
          <label for="phone">Phone</label>
          <input type="tel" id="phone" name="phone" placeholder="98765 43210" value="<?= h($old['phone']) ?>">
          <div class="err-msg"><?= h($errors['phone'] ?? 'Please enter a valid phone number.') ?></div>
        </div>
      </div>
      <div class="row-2">
        <div class="field<?= isset($errors['password']) ? ' error' : '' ?>">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="8+ chars, 1 letter & 1 number">
          <div class="err-msg"><?= h($errors['password'] ?? 'Password must be at least 8 characters.') ?></div>
        </div>
        <div class="field<?= isset($errors['confirm']) ? ' error' : '' ?>">
          <label for="confirm">Confirm password</label>
          <input type="password" id="confirm" name="confirm" placeholder="Repeat password">
          <div class="err-msg"><?= h($errors['confirm'] ?? 'Passwords do not match.') ?></div>
        </div>
      </div>
      <div class="checkbox-row">
        <label><input type="checkbox" name="terms"> I agree to the Terms & Privacy Policy</label>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Create Account</button>
    </form>
    <p class="auth-foot">Already have an account? <a href="login.php<?= $next !== '' ? '?next=' . urlencode($next) : '' ?>">Log in</a></p>
  </div>
</div>

<?php
$footerNoMargin = true;
require __DIR__ . '/footer-simple.php';
?>
