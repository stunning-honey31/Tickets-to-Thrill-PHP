<?php
/**
 * admin/header.php — <head> + opening page wrapper for every admin
 * screen. Included only by admin/index.php.
 */
if (!defined('ADMIN_ROUTER')) {
    header('Location: ../login.php');
    exit;
}

$admin = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> | Tickets to Thrill Admin</title>
<link rel="icon" type="image/x-icon" href="../favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicon-32x32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../assets/img/apple-touch-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Rubik:wght@400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-shell">
