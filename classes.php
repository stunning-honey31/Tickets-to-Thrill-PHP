<?php
/**
 * classes.php
 * Shared helpers used across every page: session bootstrap, auth,
 * cart (kept in the session), flash messages, CSRF, formatting and
 * validation. Admin-only helpers live in admin_class.php instead.
 */

require_once __DIR__ . '/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = []; // ticket_id => ['id','name','price','category','qty']
}
if (!isset($_SESSION['promo_code'])) {
    $_SESSION['promo_code'] = null;
}
if (!isset($_SESSION['promo_rate'])) {
    $_SESSION['promo_rate'] = 0.0;
}
if (!isset($_SESSION['promo_message'])) {
    $_SESSION['promo_message'] = null;
}
if (!isset($_SESSION['promo_ok'])) {
    $_SESSION['promo_ok'] = false;
}

/* ---------------------------------------------------------------------
   Small utilities
--------------------------------------------------------------------- */

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function money(float $amount): string
{
    return format_inr($amount, true);
}

function money0(float $amount): string
{
    return format_inr($amount, false);
}

/**
 * Formats a number as Indian Rupees using Indian digit grouping
 * (e.g. ₹1,49,900.00 rather than ₹149,900.00).
 */
function format_inr(float $amount, bool $withDecimals = true): string
{
    $negative = $amount < 0;
    $amount = abs(round($amount, 2));

    $parts = explode('.', number_format($amount, 2, '.', ''));
    $integerPart = $parts[0];
    $decimalPart = $parts[1];

    if (strlen($integerPart) > 3) {
        $lastThree = substr($integerPart, -3);
        $remaining = substr($integerPart, 0, -3);
        $remaining = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $remaining);
        $grouped = $remaining . ',' . $lastThree;
    } else {
        $grouped = $integerPart;
    }

    $result = '₹' . $grouped . ($withDecimals ? '.' . $decimalPart : '');
    return ($negative ? '-' : '') . $result;
}

function is_valid_email(string $value): bool
{
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

function current_path(): string
{
    return basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
}

/**
 * Validates a "next" redirect target so login/register can send people
 * back where they came from without opening up an open-redirect hole.
 * Only accepts a same-app relative path like "cart.php" or
 * "payment.php?order=5" — anything else (an absolute URL, a path
 * starting with "//", "..", etc.) is rejected in favour of $fallback.
 */
function safe_next_url(?string $next, string $fallback): string
{
    if ($next === null || $next === '') {
        return $fallback;
    }
    if (preg_match('#^[a-zA-Z0-9_\-]+\.php(\?[a-zA-Z0-9=&_\-\.%]*)?$#', $next)) {
        return $next;
    }
    return $fallback;
}

/* ---------------------------------------------------------------------
   CSRF protection
--------------------------------------------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function csrf_check(): bool
{
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent) && hash_equals($_SESSION['csrf_token'] ?? '', $sent);
}

/* ---------------------------------------------------------------------
   Flash messages (one-shot, survive a redirect)
--------------------------------------------------------------------- */

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/* ---------------------------------------------------------------------
   Auth (general — admin-only checks live in admin_class.php)
--------------------------------------------------------------------- */

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(string $redirectTo = 'login.php'): void
{
    if (!current_user()) {
        flash_set('error', 'Please log in to continue.');
        header('Location: ' . $redirectTo);
        exit;
    }
}

function log_in_user(array $userRow): void
{
    $_SESSION['user'] = [
        'id'    => (int) $userRow['id'],
        'name'  => $userRow['name'],
        'email' => $userRow['email'],
        'phone' => $userRow['phone'],
        'role'  => $userRow['role'] ?? 'user',
        'created_at' => $userRow['created_at'],
    ];
}

function log_out_user(): void
{
    unset($_SESSION['user']);
}

/* ---------------------------------------------------------------------
   Cart (server-side, stored in the session)
--------------------------------------------------------------------- */

function cart_items(): array
{
    return $_SESSION['cart'];
}

function cart_count(): int
{
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += (int) $item['qty'];
    }
    return $count;
}

function cart_subtotal(): float
{
    $subtotal = 0.0;
    foreach ($_SESSION['cart'] as $item) {
        $subtotal += $item['price'] * $item['qty'];
    }
    return $subtotal;
}

function cart_add(string $id, string $name, float $price, string $category, int $qty): void
{
    $qty = max(1, $qty);
    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$id] = [
            'id' => $id,
            'name' => $name,
            'price' => $price,
            'category' => $category,
            'qty' => $qty,
        ];
    }
}

function cart_update_qty(string $id, int $qty): void
{
    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['qty'] = max(1, $qty);
    }
}

function cart_remove(string $id): void
{
    unset($_SESSION['cart'][$id]);
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
    $_SESSION['promo_code'] = null;
    $_SESSION['promo_rate'] = 0.0;
    $_SESSION['promo_message'] = null;
    $_SESSION['promo_ok'] = false;
}

/* Promo codes: code => discount rate */
function promo_codes(): array
{
    return [
        'THRILL10' => 0.10,
        'SPLASH20' => 0.20,
    ];
}

function cart_totals(): array
{
    $subtotal = cart_subtotal();
    $rate = (float) ($_SESSION['promo_rate'] ?? 0);
    $discount = $subtotal * $rate;
    $tax = ($subtotal - $discount) * 0.05;
    $total = $subtotal - $discount + $tax;

    return [
        'subtotal' => $subtotal,
        'discount' => $discount,
        'tax' => $tax,
        'total' => $total,
    ];
}

/* ---------------------------------------------------------------------
   Booking / order status labels (shared so every page agrees)
--------------------------------------------------------------------- */

function booking_status_labels(): array
{
    return [
        'pending_payment' => 'Pending Payment',
        'upcoming' => 'Upcoming',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
}

function booking_status_label(string $status): string
{
    return booking_status_labels()[$status] ?? ucfirst($status);
}

/* =======================================================================
   VALIDATION
   Small reusable validation helpers. Each returns an error message
   string on failure, or null when the value is valid.
======================================================================= */

function v_required(string $value, string $label): ?string
{
    return trim($value) === '' ? "{$label} is required." : null;
}

function v_length(string $value, int $min, int $max, string $label): ?string
{
    $len = mb_strlen(trim($value));
    if ($len < $min) {
        return "{$label} must be at least {$min} characters.";
    }
    if ($len > $max) {
        return "{$label} must be {$max} characters or fewer.";
    }
    return null;
}

function v_email(string $value): ?string
{
    if (trim($value) === '') {
        return 'Email is required.';
    }
    if (mb_strlen($value) > 190) {
        return 'Email is too long.';
    }
    return filter_var($value, FILTER_VALIDATE_EMAIL) === false
        ? 'Please enter a valid email address.'
        : null;
}

function v_name(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return 'Name is required.';
    }
    if (mb_strlen($value) < 2 || mb_strlen($value) > 120) {
        return 'Name must be between 2 and 120 characters.';
    }
    if (!preg_match("/^[\p{L}][\p{L}\s.'-]*$/u", $value)) {
        return 'Name can only contain letters, spaces, apostrophes and hyphens.';
    }
    return null;
}

function v_phone(string $value): ?string
{
    $digits = preg_replace('/\D/', '', $value);
    if (mb_strlen($digits) < 7 || mb_strlen($digits) > 15) {
        return 'Please enter a valid phone number.';
    }
    return null;
}

function v_password(string $value): ?string
{
    if (mb_strlen($value) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if (mb_strlen($value) > 72) {
        return 'Password must be 72 characters or fewer.';
    }
    if (!preg_match('/[A-Za-z]/', $value) || !preg_match('/\d/', $value)) {
        return 'Password must include at least one letter and one number.';
    }
    return null;
}

function v_matches(string $value, string $other, string $label): ?string
{
    return $value !== $other ? "{$label} do not match." : null;
}

function v_in(string $value, array $allowed, string $label): ?string
{
    return in_array($value, $allowed, true) ? null : "Please choose a valid {$label}.";
}

function v_int_range(int $value, int $min, int $max, string $label): ?string
{
    if ($value < $min || $value > $max) {
        return "{$label} must be between {$min} and {$max}.";
    }
    return null;
}

/**
 * Validates a visit date submitted at checkout: must be a real date,
 * not in the past, and not absurdly far out (1 year — season passes
 * don't need a specific day, but this keeps the field sane for the
 * dated ticket types).
 */
function v_visit_date(string $value): ?string
{
    if (trim($value) === '') {
        return 'Please choose a visit date.';
    }
    $date = DateTime::createFromFormat('Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        return 'Please enter a valid date.';
    }
    $today = new DateTime('today');
    if ($date < $today) {
        return 'Visit date cannot be in the past.';
    }
    $maxDate = (new DateTime('today'))->modify('+1 year');
    if ($date > $maxDate) {
        return 'Please choose a date within the next year.';
    }
    return null;
}

/**
 * Runs a set of [field => callable] checks and returns an
 * ['field' => 'message', ...] array of only the ones that failed.
 */
function v_collect(array $checks): array
{
    $errors = [];
    foreach ($checks as $field => $check) {
        $result = $check();
        if ($result !== null) {
            $errors[$field] = $result;
        }
    }
    return $errors;
}
