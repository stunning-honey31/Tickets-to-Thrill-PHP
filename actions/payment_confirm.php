<?php
require_once __DIR__ . '/../classes.php';
require_once __DIR__ . '/../mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    header('Location: ../cart.php');
    exit;
}

$user = current_user();
if (!$user) {
    flash_set('error', 'Please log in to continue.');
    header('Location: ../login.php');
    exit;
}

$orderId = (int) ($_POST['order_id'] ?? 0);
$pdo = get_db();

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id AND user_id = :user_id');
$stmt->execute([':id' => $orderId, ':user_id' => $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    flash_set('error', 'That order could not be found.');
    header('Location: ../cart.php');
    exit;
}

if ($order['status'] !== 'pending') {
    // Already paid or cancelled — nothing to do, just send them onward.
    header('Location: ../order_confirmation.php?order=' . $orderId);
    exit;
}

// This is a simulated payment gateway (no real card processing, no
// network calls to a real bank/PSP) — but the "card" fields are still
// validated with basic shape checks, the same way a real checkout
// form would reject obviously-invalid input before ever contacting a
// payment provider.
$cardName = trim((string) ($_POST['card_name'] ?? ''));
$cardNumber = preg_replace('/\s+/', '', (string) ($_POST['card_number'] ?? ''));
$expiry = trim((string) ($_POST['expiry'] ?? ''));
$cvv = trim((string) ($_POST['cvv'] ?? ''));

$errors = v_collect([
    'card_name' => fn() => v_name($cardName),
    'card_number' => fn() => (preg_match('/^\d{13,19}$/', $cardNumber) ? null : 'Enter a valid card number.'),
    'expiry' => fn() => (preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry) ? null : 'Use MM/YY format.'),
    'cvv' => fn() => (preg_match('/^\d{3,4}$/', $cvv) ? null : 'Enter a valid CVV.'),
]);

if (!empty($errors)) {
    flash_set('error', 'Please check your card details and try again.');
    header('Location: ../payment.php?order=' . $orderId);
    exit;
}

// "Charge" succeeded (simulated) — mark the order paid and promote
// its booking rows from pending_payment to upcoming (real tickets now).
$paymentRef = 'PAY-' . strtoupper(bin2hex(random_bytes(6)));

$pdo->prepare('UPDATE orders SET status = "paid", payment_ref = :ref WHERE id = :id')
    ->execute([':ref' => $paymentRef, ':id' => $orderId]);

$pdo->prepare('UPDATE bookings SET status = "upcoming" WHERE order_id = :order_id')
    ->execute([':order_id' => $orderId]);

// Re-fetch the now-updated order + its items to build the PDF/email
// from the final, authoritative state rather than pre-update values.
$order = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
$order->execute([':id' => $orderId]);
$order = $order->fetch();

$itemsStmt = $pdo->prepare('SELECT * FROM bookings WHERE order_id = :order_id');
$itemsStmt->execute([':order_id' => $orderId]);
$items = $itemsStmt->fetchAll();

$emailed = send_ticket_email($order, $items, $user);

if ($emailed) {
    flash_set('success', '🎉 Payment successful! Your e-ticket has been emailed to ' . $user['email'] . '.');
} else {
    flash_set('success', '🎉 Payment successful! (We could not confirm the email was sent — you can still download your PDF ticket below.)');
}

header('Location: ../order_confirmation.php?order=' . $orderId);
exit;
