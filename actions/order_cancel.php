<?php
require_once __DIR__ . '/../classes.php';

$user = current_user();
if (!$user) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $pdo = get_db();

    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id AND user_id = :user_id AND status = "pending"');
    $stmt->execute([':id' => $orderId, ':user_id' => $user['id']]);
    $order = $stmt->fetch();

    if ($order) {
        $pdo->prepare('UPDATE orders SET status = "cancelled" WHERE id = :id')->execute([':id' => $orderId]);
        $pdo->prepare('UPDATE bookings SET status = "cancelled" WHERE order_id = :order_id')->execute([':order_id' => $orderId]);
        flash_set('success', 'Order cancelled — no payment had been made yet, so there\'s nothing to refund.');
    }
}

header('Location: ../cart.php');
exit;
