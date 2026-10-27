<?php
require_once __DIR__ . '/../classes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $code = strtoupper(trim((string) ($_POST['promo_code'] ?? '')));
    $codes = promo_codes();

    if ($code !== '' && isset($codes[$code])) {
        $_SESSION['promo_code'] = $code;
        $_SESSION['promo_rate'] = $codes[$code];
        $_SESSION['promo_ok'] = true;
        $_SESSION['promo_message'] = 'Applied! ' . ($codes[$code] * 100) . '% off.';
    } else {
        $_SESSION['promo_code'] = $code;
        $_SESSION['promo_rate'] = 0.0;
        $_SESSION['promo_ok'] = false;
        $_SESSION['promo_message'] = 'Invalid promo code.';
    }
}

header('Location: ../cart.php');
exit;
