<?php
/**
 * KABUTO ESPORTS — Coupon Validation API
 * POST /api/validate_coupon.php
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['valid' => false, 'message' => 'Method not allowed']);
    exit;
}

$code       = strtoupper(trim(Security::clean($_POST['code'] ?? '')));
$entryFee   = max(0, (float)($_POST['entry_fee'] ?? 0));
$tournamentId = Security::sanitizeInt($_POST['tournament_id'] ?? 0);

if (!$code) {
    echo json_encode(['valid' => false, 'message' => 'Please enter a coupon code.']);
    exit;
}

// Fetch coupon
$coupon = Database::fetchOne(
    "SELECT * FROM coupons WHERE code = ? AND is_active = 1",
    [$code]
);

if (!$coupon) {
    echo json_encode(['valid' => false, 'message' => 'Invalid coupon code. Please check and try again.']);
    exit;
}

// Check expiry
if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
    echo json_encode(['valid' => false, 'message' => 'This coupon has expired.']);
    exit;
}

// Check max uses
if ($coupon['max_uses'] !== null && $coupon['used_count'] >= $coupon['max_uses']) {
    echo json_encode(['valid' => false, 'message' => 'This coupon has reached its usage limit.']);
    exit;
}

// Check min fee
if ($entryFee < $coupon['min_fee']) {
    echo json_encode([
        'valid'   => false,
        'message' => 'This coupon requires a minimum entry fee of ₹' . number_format($coupon['min_fee']) . '.'
    ]);
    exit;
}

// Calculate discount
if ($coupon['discount_type'] === 'percent') {
    $discount = round($entryFee * ($coupon['discount_value'] / 100), 2);
} else {
    $discount = min($coupon['discount_value'], $entryFee); // fixed, can't exceed fee
}

$finalAmount = max(0, $entryFee - $discount);

echo json_encode([
    'valid'          => true,
    'message'        => '🎉 Coupon applied! You save ₹' . number_format($discount, 2),
    'discount_type'  => $coupon['discount_type'],
    'discount_value' => $coupon['discount_value'],
    'discount_amount'=> $discount,
    'final_amount'   => $finalAmount,
    'description'    => $coupon['description'] ?? '',
    'code'           => $coupon['code'],
]);
