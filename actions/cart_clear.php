<?php
require_once __DIR__ . '/../classes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    cart_clear();
}

header('Location: ../cart.php');
exit;
