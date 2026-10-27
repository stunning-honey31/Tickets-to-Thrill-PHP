<?php
/**
 * admin_class.php
 * Admin-only helpers. Kept separate from classes.php so it's obvious
 * at a glance which functions are safe to use on public pages and
 * which are only meaningful inside the admin panel.
 */

require_once __DIR__ . '/classes.php';

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && ($user['role'] ?? 'user') === 'admin';
}

/**
 * Guards an admin page. $prefix is how many directories "up" to go to
 * reach the site root from wherever this is called — admin/index.php
 * (the router) calls this once per request on behalf of every admin
 * page, so individual admin/*.php pages don't each need their own copy.
 */
function require_admin(string $prefix = '../'): void
{
    if (!current_user()) {
        flash_set('error', 'Please log in to continue.');
        header('Location: ' . $prefix . 'login.php');
        exit;
    }
    if (!is_admin()) {
        flash_set('error', 'You do not have access to that page.');
        header('Location: ' . $prefix . 'myaccount.php');
        exit;
    }
}
