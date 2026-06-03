<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/PayUGateway.php';

$txnId  = Security::clean($_GET['txn'] ?? '');
$reason = Security::clean($_GET['reason'] ?? '');

// Handle POST from PayU failure callback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['txnid'])) {
    $txnId  = $_POST['txnid'];
    $status = strtolower($_POST['status'] ?? '');
    if (PayUGateway::verifyHash($_POST)) {
        Database::query("UPDATE registrations SET payment_status='failed' WHERE transaction_id=?", [$txnId]);
        PayUGateway::updatePayment($txnId, 'failure', $_POST);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Failed – Kabuto Esports</title>
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.fail-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.fail-card{background:var(--bg-card);border:1px solid rgba(239,68,68,.2);border-radius:var(--radius-xl);padding:48px;max-width:560px;width:100%;text-align:center}
.fail-icon{width:80px;height:80px;border-radius:50%;background:rgba(239,68,68,.15);border:2px solid var(--danger);display:flex;align-items:center;justify-content:center;font-size:36px;margin:0 auto 24px}
</style>
</head>
<body>
<nav class="navbar scrolled"><div class="navbar-inner"><a href="/" class="navbar-brand">⚔️ <span>KABUTO <span style="color:var(--primary)">ESPORTS</span></span></a></div></nav>
<div style="padding-top:68px" class="fail-wrap">
  <div class="fail-card">
    <div class="fail-icon">✗</div>
    <h1 style="font-size:28px;color:var(--danger);margin-bottom:8px">Payment Failed</h1>
    <p style="color:var(--text-muted);margin-bottom:24px">
      <?php if ($reason === 'hash_mismatch'): ?>
        Payment verification failed. If money was deducted, it will be refunded automatically within 5-7 business days.
      <?php else: ?>
        Your payment could not be processed. No money has been deducted. Please try again.
      <?php endif; ?>
    </p>
    <?php if ($txnId): ?>
    <div style="background:var(--bg-surface);border-radius:var(--radius-md);padding:12px;margin-bottom:24px;font-size:13px;color:var(--text-muted)">
      Transaction Reference: <strong style="color:var(--text-primary)"><?= Security::sanitize($txnId) ?></strong>
    </div>
    <?php endif; ?>
    <div style="background:var(--bg-surface);border-radius:var(--radius-md);padding:16px;margin-bottom:24px;font-size:14px;color:var(--text-muted);text-align:left">
      <strong style="color:var(--warning);display:block;margin-bottom:8px">⚠️ What to do?</strong>
      <ul style="padding-left:20px;line-height:2">
        <li>Check your bank account to confirm no deduction</li>
        <li>If deducted, payment will auto-refund in 5-7 days</li>
        <li>Try again with a different payment method</li>
        <li>Contact us on WhatsApp if issue persists</li>
      </ul>
    </div>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
      <a href="/tournaments" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Tournaments</a>
      <a href="https://wa.me/919876543210" target="_blank" class="btn btn-primary"><i class="fab fa-whatsapp"></i> WhatsApp Support</a>
    </div>
  </div>
</div>
</body>
</html>
