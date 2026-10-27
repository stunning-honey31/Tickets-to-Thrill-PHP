<?php
require_once __DIR__ . '/../classes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    header('Location: ../cart.php');
    exit;
}

// Validation: checkout is not allowed without being logged in. This is
// enforced here (server-side, cannot be bypassed) in addition to
// cart.php only showing a "Checkout" button to logged-in users.
$user = current_user();
if (!$user) {
    flash_set('error', 'Please log in to checkout.');
    header('Location: ../login.php?next=cart.php');
    exit;
}

$items = cart_items();
if (empty($items)) {
    flash_set('error', 'Your cart is empty.');
    header('Location: ../cart.php');
    exit;
}

// Validation: a visit date is required for every checkout — this is
// when the customer plans to come to the park, printed on the ticket
// and shown throughout their account.
$visitDate = trim((string) ($_POST['visit_date'] ?? ''));
$visitDateError = v_visit_date($visitDate);
if ($visitDateError !== null) {
    flash_set('error', $visitDateError);
    header('Location: ../cart.php');
    exit;
}

$totals = cart_totals();
$pdo = get_db();
$now = date('Y-m-d H:i:s');

// Create the order first (status "pending" until payment is confirmed),
// then one booking row per cart line, linked to that order and marked
// "pending_payment" — they only become real confirmed tickets once
// actions/payment_confirm.php marks the order paid.
$orderStmt = $pdo->prepare('
    INSERT INTO orders (user_id, total, status, visit_date, created_at)
    VALUES (:user_id, :total, "pending", :visit_date, :created_at)
');
$orderStmt->execute([
    ':user_id' => $user['id'],
    ':total' => $totals['total'],
    ':visit_date' => $visitDate,
    ':created_at' => $now,
]);
$orderId = (int) $pdo->lastInsertId();

$bookingStmt = $pdo->prepare('
    INSERT INTO bookings (user_id, order_id, ticket_id, ticket_name, category, qty, price, status, created_at)
    VALUES (:user_id, :order_id, :ticket_id, :ticket_name, :category, :qty, :price, "pending_payment", :created_at)
');
foreach ($items as $item) {
    $bookingStmt->execute([
        ':user_id' => $user['id'],
        ':order_id' => $orderId,
        ':ticket_id' => $item['id'],
        ':ticket_name' => $item['name'],
        ':category' => $item['category'],
        ':qty' => $item['qty'],
        ':price' => $item['price'],
        ':created_at' => $now,
    ]);
}

cart_clear();
header('Location: ../payment.php?order=' . $orderId);
exit;
