<?php
require_once __DIR__ . '/admin_class.php';

$next = safe_next_url($_GET['next'] ?? ($_POST['next'] ?? null), '');

if (current_user()) {
    header('Location: ' . ($next !== '' ? $next : (is_admin() ? 'admin/index.php' : 'myaccount.php')));
    exit;
}

$errors = [];
$old = ['email' => ''];
$formMessage = null;

// Basic login throttling: after 5 failed attempts in this session,
// require a short pause before trying again.
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_locked_until'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $formMessage = ['type' => 'error', 'text' => 'Your session expired. Please try again.'];
    } elseif (time() < ($_SESSION['login_locked_until'] ?? 0)) {
        $wait = $_SESSION['login_locked_until'] - time();
        $formMessage = ['type' => 'error', 'text' => "Too many attempts. Please wait {$wait}s and try again."];
    } else {
        $old['email'] = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $errors = v_collect([
            'email' => fn() => v_email($old['email']),
            'password' => fn() => v_required($password, 'Password'),
        ]);

        if (empty($errors)) {
            $pdo = get_db();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
            $stmt->execute([':email' => $old['email']]);
            $userRow = $stmt->fetch();

            if (!$userRow || !password_verify($password, $userRow['password_hash'])) {
                $_SESSION['login_attempts']++;
                if ($_SESSION['login_attempts'] >= 5) {
                    $_SESSION['login_locked_until'] = time() + 30;
                    $_SESSION['login_attempts'] = 0;
                }
                $formMessage = ['type' => 'error', 'text' => 'Incorrect email or password.'];
            } else {
                $_SESSION['login_attempts'] = 0;
                log_in_user($userRow);
                if ($next !== '') {
                    header('Location: ' . $next);
                } else {
                    header('Location: ' . ($userRow['role'] === 'admin' ? 'admin/index.php' : 'myaccount.php'));
                }
                exit;
            }
        }
    }
}

$pageTitle = 'Login — Tickets to Thrill';
require __DIR__ . '/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card">
    <h1>Welcome back 🎫</h1>
    <p class="sub">Log in to view your tickets and bookings.</p>
    <?php if ($formMessage): ?>
      <div class="form-msg show <?= h($formMessage['type']) ?>"><?= h($formMessage['text']) ?></div>
    <?php endif; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= h($next) ?>">
      <div class="field<?= isset($errors['email']) ? ' error' : '' ?>">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="you@email.com" value="<?= h($old['email']) ?>">
        <div class="err-msg"><?= h($errors['email'] ?? 'Please enter a valid email address.') ?></div>
      </div>
      <div class="field<?= isset($errors['password']) ? ' error' : '' ?>">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="••••••••">
        <div class="err-msg"><?= h($errors['password'] ?? 'Password is required.') ?></div>
      </div>
      <div class="checkbox-row">
        <label><input type="checkbox" name="remember"> Remember me</label>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Log In</button>
    </form>
    <p class="auth-foot">New here? <a href="register.php<?= $next !== '' ? '?next=' . urlencode($next) : '' ?>">Create an account</a></p>
    <p class="auth-foot" style="font-size:.78rem; color:#A79CC7;">Demo tip: register first, then log in with the same details.</p>
  </div>
</div>

<?php
$footerNoMargin = true;
require __DIR__ . '/footer-simple.php';
?>
