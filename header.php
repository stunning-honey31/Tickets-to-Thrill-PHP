<?php
/**
 * Shared page header.
 * Expects $pageTitle (string) and optionally $activePage (one of
 * index.php, tickets.php, about.php, contact.php) to be set by the
 * including page before this file is required.
 */

require_once __DIR__ . '/admin_class.php';

$activePage = $activePage ?? '';
$pageTitle = $pageTitle ?? 'Tickets to Thrill';
$user = current_user();
$flash = flash_get();

function nav_active(string $file, string $activePage): string
{
    return $file === $activePage ? ' class="active"' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?></title>
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="assets/img/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="192x192" href="assets/img/android-chrome-192x192.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Rubik:wght@400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
  <nav class="navbar container">
    <a href="index.php" class="logo" aria-label="Tickets to Thrill — home">
      <svg viewBox="0 0 300 64" width="150" height="32" role="img" aria-hidden="true">
        <defs>
          <linearGradient id="navLogoGrad" x1="10%" y1="0%" x2="90%" y2="100%">
            <stop offset="0%" stop-color="#33206E"/>
            <stop offset="55%" stop-color="#241454"/>
            <stop offset="100%" stop-color="#5E2CC0"/>
          </linearGradient>
          <clipPath id="navLogoClip">
            <rect x="1" y="1" width="62" height="62" rx="17"/>
          </clipPath>
        </defs>
        <g clip-path="url(#navLogoClip)">
          <rect x="1" y="1" width="62" height="62" fill="url(#navLogoGrad)"/>
          <path d="M8 38 C 13 21, 20 21, 24 32 C 27 40, 30 40, 33 32 C 37 21, 44 21, 49 30 C 52 35, 54 37, 57 38"
                fill="none" stroke="#FFFFFF" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="24.5" cy="31.5" r="6.4" fill="none" stroke="#FFFFFF" stroke-width="2.6"/>
          <g transform="translate(38,23) rotate(-24)">
            <rect x="-5" y="-3.6" width="10" height="7.2" rx="1.8" fill="#FF4D5E"/>
            <circle cx="-2.8" cy="4" r="1.5" fill="#1F1435"/>
            <circle cx="2.8" cy="4" r="1.5" fill="#1F1435"/>
          </g>
          <g transform="translate(50,14)">
            <path d="M0 -6.5 L1.7 -1.7 L6.5 0 L1.7 1.7 L0 6.5 L-1.7 1.7 L-6.5 0 L-1.7 -1.7 Z" fill="#FFC93C"/>
          </g>
          <rect x="1" y="55" width="62" height="8" fill="#FFC93C"/>
        </g>
        <text x="76" y="40" font-family="Fredoka, Poppins, 'Segoe UI', sans-serif" font-weight="700" font-size="27" fill="#FFFFFF">Tickets to Thr<tspan fill="#FFC93C">i</tspan>ll</text>
      </svg>
    </a>
    <ul class="nav-links">
      <li><a href="index.php"<?= nav_active('index.php', $activePage) ?>>Home</a></li>
      <li><a href="tickets.php"<?= nav_active('tickets.php', $activePage) ?>>Tickets</a></li>
      <li><a href="about.php"<?= nav_active('about.php', $activePage) ?>>About</a></li>
      <li><a href="contact.php"<?= nav_active('contact.php', $activePage) ?>>Contact</a></li>
    </ul>
    <div class="nav-actions">
      <a href="cart.php" class="icon-btn" aria-label="Cart">🛒<span class="cart-count" data-cart-count><?= (int) cart_count() ?></span></a>
      <span data-nav-auth>
        <?php if ($user): ?>
          <a href="myaccount.php" class="nav-user">Hi, <?= h(explode(' ', $user['name'])[0]) ?> 👋</a>
          <?php if (is_admin()): ?><a href="admin/index.php" class="icon-btn" title="Admin Panel" aria-label="Admin Panel">🛠️</a><?php endif; ?>
          <a href="logout.php" class="icon-btn" title="Log out" aria-label="Log out">⎋</a>
        <?php else: ?>
          <a href="login.php" class="btn btn-primary btn-sm">Login</a>
        <?php endif; ?>
      </span>
      <button class="hamburger" aria-label="Menu">☰</button>
    </div>
  </nav>
</header>

<?php if ($flash): ?>
  <div class="container" style="padding-top:18px;">
    <div class="form-msg show <?= h($flash['type'] === 'success' ? 'success' : 'error') ?>"><?= h($flash['message']) ?></div>
  </div>
<?php endif; ?>
