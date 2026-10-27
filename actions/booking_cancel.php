<?php
require_once __DIR__ . '/../classes.php';

$user = current_user();
if (!$user) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $pdo = get_db();

    // Fetch first — before cancelling — so we know exactly what's
    // being refunded and can confirm this booking is really the
    // user's own and still cancellable.
    $stmt = $pdo->prepare('
        SELECT * FROM bookings
        WHERE id = :id AND user_id = :user_id AND status = "upcoming"
    ');
    $stmt->execute([':id' => $bookingId, ':user_id' => $user['id']]);
    $booking = $stmt->fetch();

    if ($booking) {
        $pdo->prepare('UPDATE bookings SET status = "cancelled" WHERE id = :id')
            ->execute([':id' => $bookingId]);

        // Simulated refund — this is a demo payment gateway (see
        // actions/payment_confirm.php), so no real money moves, but we
        // still show the same message a real ticketing site would.
        $refundAmount = (float) $booking['price'] * (int) $booking['qty'];
        flash_set(
            'success',
            'Ticket cancelled — "' . $booking['ticket_name'] . '" (Qty ' . (int) $booking['qty'] . '). ' .
            'A refund of ' . money($refundAmount) . ' will be credited to your original payment method within 5–7 business days.'
        );
    } else {
        flash_set('error', 'That ticket could not be cancelled — it may already be used or cancelled.');
    }
}

header('Location: ../myaccount.php');
exit;
