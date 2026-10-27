<?php
/**
 * Database connection settings.
 *
 * Defaults match a typical local MySQL install. Override any of these
 * with environment variables (e.g. in your web server config, a .env
 * loader, or `export DB_PASS=...` before starting `php -S`) instead of
 * editing this file directly, especially in production.
 */

return [
    'host'    => getenv('DB_HOST') ?: '127.0.0.1',
    'port'    => getenv('DB_PORT') ?: '3306',
    'name'    => getenv('DB_NAME') ?: 'tickets_to_thrill',
    'user'    => getenv('DB_USER') ?: 'root',
    'pass'    => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',

    // Used by mailer.php when sending the ticket-confirmation email.
    'mail_from_email' => getenv('MAIL_FROM_EMAIL') ?: 'no-reply@ticketstothrill.example',
    'mail_from_name'  => getenv('MAIL_FROM_NAME') ?: 'Tickets to Thrill',
];
