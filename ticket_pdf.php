<?php
require_once __DIR__ . '/admin_class.php';
require_once __DIR__ . '/pdf_generator.php';

require_login('login.php');

$user = current_user();
$orderId = (int) ($_GET['order'] ?? 0);

$pdo = get_db();

// Admins may download any order's ticket (e.g. to resend/troubleshoot);
// everyone else may only download their own.
if (is_admin()) {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
    $stmt->execute([':id' => $orderId]);
} else {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id AND user_id = :user_id');
    $stmt->execute([':id' => $orderId, ':user_id' => $user['id']]);
}
$order = $stmt->fetch();

if (!$order || $order['status'] !== 'paid') {
    http_response_code(404);
    echo 'Ticket not found.';
    exit;
}

$ownerStmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
$ownerStmt->execute([':id' => $order['user_id']]);
$owner = $ownerStmt->fetch();

$itemsStmt = $pdo->prepare('SELECT * FROM bookings WHERE order_id = :order_id');
$itemsStmt->execute([':order_id' => $orderId]);
$items = $itemsStmt->fetchAll();

$pdfBytes = build_ticket_pdf($order, $items, $owner);
$filename = 'ticket-order-' . $order['id'] . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdfBytes));
echo $pdfBytes;
exit;
