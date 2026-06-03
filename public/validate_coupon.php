<?php
/**
 * KABUTO ESPORTS — Coupon Validation API
 * POST /validate_coupon.php
 */
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/includes/functions.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['valid' => false, 'message' => 'Invalid request method.']);
        exit;
    }

    $code     = strtoupper(trim(Security::clean($_POST['code'] ?? '')));
    $entryFee = max(0, (float)($_POST['entry_fee'] ?? 0));

    if (!$code) {
        echo json_encode(['valid' => false, 'message' => 'Please enter a coupon code.']);
        exit;
    }

    $coupon = Database::fetchOne(
        "SELECT * FROM coupons WHERE code = ? AND is_active = 1",
        [$code]
    );

    if (!$coupon) {
        echo json_encode(['valid' => false, 'message' => 'Invalid coupon code. Please check and try again.']);
        exit;
    }

    if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
        echo json_encode(['valid' => false, 'message' => 'This coupon has expired.']);
        exit;
    }

    if ($coupon['max_uses'] !== null && (int)$coupon['used_count'] >= (int)$coupon['max_uses']) {
        echo json_encode(['valid' => false, 'message' => 'This coupon has reached its usage limit.']);
        exit;
    }

    if ($entryFee < (float)$coupon['min_fee']) {
        echo json_encode(['valid' => false, 'message' => 'This coupon requires a minimum entry fee of ₹' . number_format((float)$coupon['min_fee']) . '.']);
        exit;
    }

    if ($coupon['discount_type'] === 'percent') {
        $discount = round($entryFee * ((float)$coupon['discount_value'] / 100), 2);
    } else {
        $discount = min((float)$coupon['discount_value'], $entryFee);
    }

    $finalAmount = max(0, $entryFee - $discount);

    echo json_encode([
        'valid'          => true,
        'message'        => 'Coupon applied! You save ₹' . number_format($discount, 2),
        'discount_type'  => $coupon['discount_type'],
        'discount_value' => (float)$coupon['discount_value'],
        'discount_amount'=> $discount,
        'final_amount'   => $finalAmount,
        'description'    => $coupon['description'] ?? '',
        'code'           => $coupon['code'],
    ]);

} catch (Throwable $e) {
    // Never return 500 — always return JSON
    echo json_encode([
        'valid'   => false,
        'message' => 'Coupon system error. Please proceed without coupon.',
        'debug'   => (defined('APP_DEBUG') && APP_DEBUG) ? $e->getMessage() : null,
    ]);
}
