<?php
/**
 * admin/index.php — the admin panel's single entry point.
 *
 * Every admin screen is reached as index.php?page=<name>. This file
 * checks the session once, draws the shared chrome (header/topbar/
 * sidebar/footer), and includes the requested page fragment in
 * between. Individual page files (home.php, user_list.php, ...) are
 * plain fragments — they assume this file has already checked the
 * session, which is why they refuse to run if loaded directly (see
 * the ADMIN_ROUTER guard at the top of each one).
 */

require_once __DIR__ . '/../admin_class.php';
require_admin('../');

define('ADMIN_ROUTER', true);

// Whitelist of routable pages — anything else falls through to 404.
$pages = ['home', 'user_list', 'new_user', 'edit_user', 'view_user', 'ticket_list', 'messages'];

$requestedPage = $_GET['page'] ?? 'home';
$page = in_array($requestedPage, $pages, true) ? $requestedPage : null;
$pageTitles = [
    'home' => 'Dashboard',
    'user_list' => 'Users',
    'new_user' => 'New User',
    'edit_user' => 'Edit User',
    'view_user' => 'View User',
    'ticket_list' => 'Tickets',
    'messages' => 'Messages',
];
$pageTitle = $page !== null ? ($pageTitles[$page] ?? 'Admin') : 'Not Found';
$activeAdmin = in_array($page, ['user_list', 'new_user', 'edit_user', 'view_user'], true) ? 'user_list' : $page;

// Run the page fragment first, buffered. This lets a page handle its
// own form submission and redirect (header()+exit) before any chrome
// has been sent — exactly like a normal top-of-file POST handler
// would, just deferred until we know which page was requested.
ob_start();
if ($page !== null) {
    require __DIR__ . '/' . $page . '.php';
} else {
    require __DIR__ . '/404.html';
}
$pageContent = ob_get_clean();

require __DIR__ . '/header.php';
require __DIR__ . '/sidebar.php';
require __DIR__ . '/topbar.php';
?>

<div class="content-wrapper">
  <?php $flash = flash_get(); if ($flash): ?>
      <div class="admin-flash-wrap">
        <div class="form-msg show <?= h($flash['type'] === 'success' ? 'success' : 'error') ?>"><?= h($flash['message']) ?></div>
      </div>
  <?php endif; ?>

  <div class="content-header">
    <h1><?= h($pageTitle) ?></h1>
  </div>

  <section class="content">
    <?= $pageContent ?>
  </section>
</div>

<?php require __DIR__ . '/footer.php'; ?>
