<?php
require_once __DIR__ . '/../classes.php';
require_once __DIR__ . '/../data/tickets.php';

$redirect = $_POST['redirect'] ?? '../tickets.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash_set('error', 'Something went wrong. Please try again.');
    header('Location: ' . $redirect);
    exit;
}

$ticketId = (string) ($_POST['ticket_id'] ?? '');
$qty = max(1, (int) ($_POST['qty'] ?? 1));
$ticket = ticket_find($ticketId);

if (!$ticket) {
    flash_set('error', 'That ticket could not be found.');
    header('Location: ' . $redirect);
    exit;
}

cart_add($ticket['id'], $ticket['name'], (float) $ticket['price'], $ticket['category'], $qty);

flash_set('success', $ticket['name'] . ' added to your cart.');
header('Location: ' . $redirect);
exit;
