<?php
if (!defined('ADMIN_ROUTER')) {
    header('Location: ../login.php');
    exit;
}
?>
<div class="admin-main">
  <header class="admin-topbar">
    <span class="admin-who">Signed in as <strong><?= h($admin['name'] ?? '') ?></strong></span>
  </header>
