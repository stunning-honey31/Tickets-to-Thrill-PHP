<?php
require_once __DIR__ . '/../classes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $id = (string) ($_POST['ticket_id'] ?? '');
    cart_remove($id);
}

header('Location: ../cart.php');
exit;
