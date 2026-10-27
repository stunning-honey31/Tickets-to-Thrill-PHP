<?php
/**
 * mailer.php
 *
 * Sends the "your tickets are confirmed" email with the PDF ticket
 * attached, using PHP's built-in mail() function — no PHPMailer/
 * Composer package, since there's no network access here to install
 * one. mail() hands the message to whatever MTA (sendmail/postfix)
 * is configured on the server; on a lot of hosts that's enough, but
 * for reliable delivery (and to avoid landing in spam) a real SMTP
 * library is worth switching to later. See README.md.
 */

require_once __DIR__ . '/pdf_generator.php';

/**
 * Sends the ticket confirmation email. Returns true if mail() accepted
 * the message for delivery — that does NOT guarantee it was actually
 * delivered, only that it was handed off successfully.
 */
function send_ticket_email(array $order, array $items, array $user): bool
{
    $config = require __DIR__ . '/config.php';
    $fromEmail = $config['mail_from_email'] ?? 'no-reply@ticketstothrill.example';
    $fromName = $config['mail_from_name'] ?? 'Tickets to Thrill';

    $pdfBytes = build_ticket_pdf($order, $items, $user);
    $pdfFilename = 'ticket-order-' . $order['id'] . '.pdf';

    $subject = 'Your Tickets to Thrill order #' . $order['id'] . ' is confirmed';

    $itemLines = '';
    foreach ($items as $item) {
        $itemLines .= sprintf(
            "<tr><td style=\"padding:6px 10px;border-bottom:1px solid #eee;\">%s</td>" .
            "<td style=\"padding:6px 10px;border-bottom:1px solid #eee;text-align:center;\">%d</td>" .
            "<td style=\"padding:6px 10px;border-bottom:1px solid #eee;text-align:right;\">%s</td></tr>",
            htmlspecialchars($item['ticket_name'], ENT_QUOTES, 'UTF-8'),
            (int) $item['qty'],
            money((float) $item['price'] * (int) $item['qty'])
        );
    }

    $htmlBody = "
    <div style=\"font-family:Arial,sans-serif;color:#1F1435;max-width:560px;margin:0 auto;\">
      <h2 style=\"color:#5E2CC0;\">Your tickets are confirmed! 🎢</h2>
      <p>Hi " . htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . ",</p>
      <p>Thanks for your order — your e-ticket is attached as a PDF. Order reference: <strong>#{$order['id']}</strong>.</p>
      " . (!empty($order['visit_date'])
        ? "<p style=\"background:#F5F1FB;border-radius:8px;padding:10px 14px;display:inline-block;\">🗓 <strong>Visit date:</strong> " . htmlspecialchars(date('l, d M Y', strtotime($order['visit_date'])), ENT_QUOTES, 'UTF-8') . "</p>"
        : "") . "
      <table style=\"width:100%;border-collapse:collapse;margin:16px 0;\">
        <thead>
          <tr style=\"background:#F5F1FB;\">
            <th style=\"padding:8px 10px;text-align:left;\">Ticket</th>
            <th style=\"padding:8px 10px;text-align:center;\">Qty</th>
            <th style=\"padding:8px 10px;text-align:right;\">Amount</th>
          </tr>
        </thead>
        <tbody>{$itemLines}</tbody>
      </table>
      <p style=\"font-size:1.1rem;\"><strong>Total paid: " . money((float) $order['total']) . "</strong></p>
      <p>Present the attached PDF (printed or on your phone) at the gate. See you at the park!</p>
      <p style=\"color:#888;font-size:.8rem;\">This is a demo store — no real payment was processed.</p>
    </div>";

    $boundary = 'ttt-' . bin2hex(random_bytes(12));

    $headers = [];
    $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
    $headers[] = 'Reply-To: ' . $fromEmail;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $htmlBody . "\r\n\r\n";

    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: application/pdf; name=\"{$pdfFilename}\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"{$pdfFilename}\"\r\n\r\n";
    $body .= chunk_split(base64_encode($pdfBytes)) . "\r\n";
    $body .= "--{$boundary}--";

    return @mail($user['email'], $subject, $body, implode("\r\n", $headers));
}
