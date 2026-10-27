<?php
require_once __DIR__ . '/../classes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $id = (string) ($_POST['ticket_id'] ?? '');
    $step = (int) ($_POST['step'] ?? 0);
    $current = $_SESSION['cart'][$id]['qty'] ?? 1;
    cart_update_qty($id, $current + $step);
}

header('Location: ../cart.php');
exit;
