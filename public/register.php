<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/RegistrationHelper.php';
require_once __DIR__ . '/includes/PayUGateway.php';
require_once __DIR__ . '/includes/EmailService.php';

// ── REQUIRE LOGIN ────────────────────────────────────────────
if (!UserAuth::check()) {
    $currentUrl = '/register/' . (int)($_GET['id'] ?? 0);
    redirect('/login?next=' . urlencode($currentUrl));
}
$currentUser = UserAuth::current();

$id = Security::sanitizeInt($_GET['id'] ?? 0);
if (!$id) redirect('/tournaments');

$tournament = getTournamentById($id);
if (!$tournament || $tournament['status'] === 'cancelled') redirect('/tournaments');

$avail  = RegistrationHelper::getAvailableSlots($id);
$isFree = (float)$tournament['entry_fee'] == 0;
$isOpen = $avail > 0 && strtotime($tournament['registration_deadline']) > time() && $tournament['registration_open'];

if (!$isOpen) {
    redirect('/tournament/' . $tournament['slug']);
}

$errors  = [];
$success = false;
$regId   = null;

// ── POST: Process Registration ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Rate limiting
    if (!Security::rateLimit('register_' . $id, 3, 300)) {
        $errors[] = 'Too many registration attempts. Please wait 5 minutes.';
    }

    // CSRF
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    }

    if (empty($errors)) {
        // Collect & sanitize inputs
        $teamName   = Security::clean($_POST['team_name']   ?? '');
        $leaderName = Security::clean($_POST['leader_name'] ?? '');
        $leaderUid  = Security::clean($_POST['leader_uid']  ?? '');
        $leaderIgn  = Security::clean($_POST['leader_ign']  ?? '');
        $mobile     = preg_replace('/\D/', '', Security::clean($_POST['mobile'] ?? ''));
        $whatsapp   = preg_replace('/\D/', '', Security::clean($_POST['whatsapp'] ?? ''));
        $email      = Security::sanitizeEmail($_POST['email'] ?? '');

        $p2Name     = Security::clean($_POST['player2_name']     ?? '');
        $p2Uid      = Security::clean($_POST['player2_uid']      ?? '');
        $p2Ign      = Security::clean($_POST['player2_ign']      ?? '');
        $p2Whatsapp = preg_replace('/\D/', '', Security::clean($_POST['player2_whatsapp'] ?? ''));

        $p3Name     = Security::clean($_POST['player3_name']     ?? '');
        $p3Uid      = Security::clean($_POST['player3_uid']      ?? '');
        $p3Ign      = Security::clean($_POST['player3_ign']      ?? '');
        $p3Whatsapp = preg_replace('/\D/', '', Security::clean($_POST['player3_whatsapp'] ?? ''));

        $p4Name     = Security::clean($_POST['player4_name']     ?? '');
        $p4Uid      = Security::clean($_POST['player4_uid']      ?? '');
        $p4Ign      = Security::clean($_POST['player4_ign']      ?? '');
        $p4Whatsapp = preg_replace('/\D/', '', Security::clean($_POST['player4_whatsapp'] ?? ''));

        $subName     = Security::clean($_POST['sub_name']     ?? '');
        $subUid      = Security::clean($_POST['sub_uid']      ?? '');
        $subIgn      = Security::clean($_POST['sub_ign']      ?? '');
        $subWhatsapp = preg_replace('/\D/', '', Security::clean($_POST['sub_whatsapp'] ?? ''));

        $referralName = Security::clean($_POST['referral_name'] ?? '');
        $couponCode   = strtoupper(trim(Security::clean($_POST['coupon_code'] ?? '')));
        $discountAmt  = 0;
        $finalAmount  = (float)$tournament['entry_fee'];

        // Validate coupon server-side
        if ($couponCode && !$isFree) {
            $coupon = Database::fetchOne(
                "SELECT * FROM coupons WHERE code = ? AND is_active = 1",
                [$couponCode]
            );
            if ($coupon
                && (!$coupon['expires_at'] || strtotime($coupon['expires_at']) > time())
                && ($coupon['max_uses'] === null || $coupon['used_count'] < $coupon['max_uses'])
                && $finalAmount >= $coupon['min_fee']
            ) {
                if ($coupon['discount_type'] === 'percent') {
                    $discountAmt = round($finalAmount * ($coupon['discount_value'] / 100), 2);
                } else {
                    $discountAmt = min((float)$coupon['discount_value'], $finalAmount);
                }
                $finalAmount = max(0, $finalAmount - $discountAmt);
            } else {
                $couponCode = ''; // invalid, clear it
            }
        }

        // Required validation
        if (strlen($teamName) < 2)   $errors['team_name']   = 'Team name is required (min 2 chars).';
        if (strlen($leaderName) < 2) $errors['leader_name'] = 'Leader name is required.';
        if (strlen($leaderUid) < 4)  $errors['leader_uid']  = 'Valid BGMI UID is required.';
        if (strlen($leaderIgn) < 2)  $errors['leader_ign']  = 'In-game name is required.';
        if (!Security::validateMobile($mobile))   $errors['mobile'] = 'Enter a valid 10-digit Indian mobile number.';
        if (!Security::validateMobile($whatsapp)) $errors['whatsapp'] = 'Enter a valid 10-digit WhatsApp number.';
        if (!$email) $errors['email'] = 'Enter a valid email address.';

        // Squad specific validation
        if ($tournament['mode'] === 'squad') {
            if (!$p2Name || !$p2Uid || !$p2Ign) $errors['player2'] = 'Player 2 name, IGN and UID are required for squad.';
            if (!$p3Name || !$p3Uid || !$p3Ign) $errors['player3'] = 'Player 3 name, IGN and UID are required for squad.';
            if (!$p4Name || !$p4Uid || !$p4Ign) $errors['player4'] = 'Player 4 name, IGN and UID are required for squad.';
        } elseif ($tournament['mode'] === 'duo') {
            if (!$p2Name || !$p2Uid || !$p2Ign) $errors['player2'] = 'Player 2 name, IGN and UID are required for duo.';
        }

        // Duplicate UID check
        $allUids = array_filter([$leaderUid, $p2Uid, $p3Uid, $p4Uid, $subUid]);
        if (count($allUids) !== count(array_unique($allUids))) {
            $errors['uid_duplicate'] = 'Duplicate UIDs detected. Each player must have a unique UID.';
        }

        // Check if UIDs already registered
        foreach (array_filter([$leaderUid, $p2Uid, $p3Uid, $p4Uid]) as $uid) {
            if (RegistrationHelper::isUidRegistered($uid, $id)) {
                $errors['uid_registered'] = "UID $uid is already registered for this tournament.";
                break;
            }
        }

        // Duplicate team check
        if (RegistrationHelper::isTeamRegistered($teamName, $id)) {
            $errors['team_duplicate'] = 'A team with this name is already registered for this tournament.';
        }

        // Check slots still available
        if (RegistrationHelper::getAvailableSlots($id) < 1) {
            $errors[] = 'Sorry, all slots are filled!';
        }
    }

    // ── If no errors, create registration ──
    if (empty($errors)) {
        try {
            Database::beginTransaction();

            // Clean up any stale pending registrations for this user/UID
            RegistrationHelper::cleanupPendingRegistrations($id, $userId, $leaderUid);

            $registrationId = RegistrationHelper::generateRegistrationId();
            $txnId          = 'KAB' . time() . rand(100, 999);
            $isFreeAfterDiscount = $isFree || $finalAmount == 0;
            $payStatus      = $isFreeAfterDiscount ? 'free' : 'pending';
            $ip             = getClientIp();
            $userId         = $currentUser['id'] ?? null;

            Database::query(
                "INSERT INTO registrations 
                (registration_id,tournament_id,user_id,team_name,leader_name,leader_uid,leader_ign,
                 mobile,whatsapp,email,
                 player2_name,player2_uid,player2_ign,player2_whatsapp,
                 player3_name,player3_uid,player3_ign,player3_whatsapp,
                 player4_name,player4_uid,player4_ign,player4_whatsapp,
                 sub_name,sub_uid,sub_ign,sub_whatsapp,
                 referral_name,coupon_code,discount_amount,final_amount,
                 payment_status,transaction_id,ip_address)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $registrationId, $id, $userId, $teamName, $leaderName, $leaderUid, $leaderIgn,
                    $mobile, $whatsapp, $email,
                    $p2Name, $p2Uid, $p2Ign, $p2Whatsapp,
                    $p3Name, $p3Uid, $p3Ign, $p3Whatsapp,
                    $p4Name, $p4Uid, $p4Ign, $p4Whatsapp,
                    $subName, $subUid, $subIgn, $subWhatsapp,
                    $referralName, $couponCode ?: null, $discountAmt, $finalAmount,
                    $payStatus, $txnId, $ip
                ]
            );

            // ⚡ Capture DB ID immediately after INSERT — before any other query resets it
            $dbRegId = (int)Database::lastInsertId();

            // Increment coupon usage
            if ($couponCode) {
                Database::query("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?", [$couponCode]);
            }

            RegistrationHelper::incrementSlot($id);
            Database::commit();

            if ($isFreeAfterDiscount) {
                EmailService::sendRegistrationConfirmation(
                    ['registration_id'=>$registrationId,'team_name'=>$teamName,'leader_name'=>$leaderName,'email'=>$email,'payment_status'=>$payStatus],
                    $tournament
                );
                EmailService::sendAdminNotification(
                    ['registration_id'=>$registrationId,'team_name'=>$teamName,'leader_name'=>$leaderName,'email'=>$email,'mobile'=>$mobile,'payment_status'=>$payStatus],
                    $tournament
                );
                redirect('/payment/success?reg=' . urlencode($registrationId) . '&free=1');
            } else {
                // Paid: record payment and redirect to PayU with final amount
                PayUGateway::recordPayment($dbRegId, $txnId, $finalAmount, 'initiated');
                $payuHtml = PayUGateway::buildPaymentForm(
                    $txnId,
                    $finalAmount,
                    'Kabuto Esports - ' . $tournament['name'],
                    $leaderName,
                    $email,
                    $mobile,
                    $registrationId,
                    (string)$id
                );
                echo $payuHtml;
                exit;
            }
        } catch (Exception $e) {
            Database::rollback();
            error_log('Registration error: ' . $e->getMessage());
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register – <?= Security::sanitize($tournament['name']) ?> | Kabuto Esports</title>
<meta name="description" content="Register your team for <?= Security::sanitize($tournament['name']) ?> on Kabuto Esports.">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.reg-layout{display:grid;grid-template-columns:1fr 320px;gap:32px;align-items:start}
.tournament-info-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;position:sticky;top:88px}
.reg-form-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:32px}
.fee-box{background:linear-gradient(135deg,rgba(245,166,35,.1),rgba(245,166,35,.03));border:1px solid var(--border-bright);border-radius:var(--radius-md);padding:20px;text-align:center;margin-bottom:20px}
.fee-amount{font-family:'Orbitron',sans-serif;font-size:32px;font-weight:700;color:var(--primary)}
@media(max-width:900px){.reg-layout{grid-template-columns:1fr}.tournament-info-card{position:static}}
</style>
</head>
<body>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div style="padding-top:68px"></div>

<section class="section-sm">
  <div class="container">
    <div class="breadcrumb" style="margin-bottom:24px">
      <a href="/">Home</a> <i class="fas fa-chevron-right" style="font-size:10px"></i>
      <a href="/tournaments">Tournaments</a> <i class="fas fa-chevron-right" style="font-size:10px"></i>
      <a href="/tournament/<?= Security::sanitize($tournament['slug']) ?>"><?= Security::sanitize($tournament['name']) ?></a> <i class="fas fa-chevron-right" style="font-size:10px"></i>
      <span>Register</span>
    </div>

    <!-- Stepper -->
    <div class="stepper" style="max-width:500px;margin:0 auto 36px">
      <div class="step active"><div class="step-circle">1</div><div class="step-label">Details</div></div>
      <div class="step-line"></div>
      <div class="step"><div class="step-circle">2</div><div class="step-label">Payment</div></div>
      <div class="step-line"></div>
      <div class="step"><div class="step-circle">3</div><div class="step-label">Confirm</div></div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger" style="max-width:800px;margin:0 auto 24px">
      <i class="fas fa-exclamation-circle"></i>
      <div>
        <strong>Please fix the following errors:</strong>
        <ul style="margin:8px 0 0;padding-left:20px">
          <?php foreach ($errors as $err): ?><li><?= Security::sanitize((string)$err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?php endif; ?>

    <div class="reg-layout">
      <!-- Registration Form -->
      <div class="reg-form-card">
        <h2 style="margin-bottom:4px">Team Registration</h2>
        <p style="color:var(--text-muted);font-size:14px;margin-bottom:28px">Fill all required fields carefully. Your BGMI UID must be correct.</p>

        <form method="POST" id="regForm" novalidate>
          <?= Security::csrfField() ?>
          <input type="hidden" name="tournament_id" value="<?= $id ?>">

          <!-- TEAM INFORMATION -->
          <div class="form-section-title"><i class="fas fa-shield-alt"></i> Team Information</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="team_name">Team Name *</label>
              <input type="text" class="form-control <?= isset($errors['team_name'])?'is-invalid':'' ?>" id="team_name" name="team_name" value="<?= Security::sanitize($_POST['team_name'] ?? '') ?>" placeholder="e.g. Dragon Warriors" required>
              <?php if (isset($errors['team_name'])): ?><div class="form-error show"><?= Security::sanitize($errors['team_name']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
              <label class="form-label" for="leader_name">Team Leader Name *</label>
              <input type="text" class="form-control <?= isset($errors['leader_name'])?'is-invalid':'' ?>" id="leader_name" name="leader_name" value="<?= Security::sanitize($_POST['leader_name'] ?? '') ?>" placeholder="Real name" required>
              <?php if (isset($errors['leader_name'])): ?><div class="form-error show"><?= Security::sanitize($errors['leader_name']) ?></div><?php endif; ?>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="leader_uid">Leader BGMI UID *</label>
              <input type="text" class="form-control <?= isset($errors['leader_uid'])?'is-invalid':'' ?>" id="leader_uid" name="leader_uid" value="<?= Security::sanitize($_POST['leader_uid'] ?? '') ?>" placeholder="e.g. 512345678" required>
              <?php if (isset($errors['leader_uid'])): ?><div class="form-error show"><?= Security::sanitize($errors['leader_uid']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
              <label class="form-label" for="leader_ign">Leader In-Game Name *</label>
              <input type="text" class="form-control <?= isset($errors['leader_ign'])?'is-invalid':'' ?>" id="leader_ign" name="leader_ign" value="<?= Security::sanitize($_POST['leader_ign'] ?? '') ?>" placeholder="IGN in BGMI" required>
              <?php if (isset($errors['leader_ign'])): ?><div class="form-error show"><?= Security::sanitize($errors['leader_ign']) ?></div><?php endif; ?>
            </div>
          </div>

          <!-- CONTACT INFORMATION -->
          <div class="form-section-title"><i class="fas fa-address-card"></i> Contact Information</div>
          <div class="form-group">
            <label class="form-label" for="email">Email Address *</label>
            <input type="email" class="form-control <?= isset($errors['email'])?'is-invalid':'' ?>" id="email" name="email" value="<?= Security::sanitize($_POST['email'] ?? '') ?>" placeholder="you@example.com" required>
            <?php if (isset($errors['email'])): ?><div class="form-error show"><?= Security::sanitize($errors['email']) ?></div><?php endif; ?>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="mobile">Mobile Number *</label>
              <input type="tel" class="form-control <?= isset($errors['mobile'])?'is-invalid':'' ?>" id="mobile" name="mobile" value="<?= Security::sanitize($_POST['mobile'] ?? '') ?>" placeholder="10-digit number" required maxlength="10">
              <?php if (isset($errors['mobile'])): ?><div class="form-error show"><?= Security::sanitize($errors['mobile']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
              <label class="form-label" for="whatsapp">WhatsApp Number *</label>
              <input type="tel" class="form-control <?= isset($errors['whatsapp'])?'is-invalid':'' ?>" id="whatsapp" name="whatsapp" value="<?= Security::sanitize($_POST['whatsapp'] ?? '') ?>" placeholder="10-digit number" required maxlength="10">
              <?php if (isset($errors['whatsapp'])): ?><div class="form-error show"><?= Security::sanitize($errors['whatsapp']) ?></div><?php endif; ?>
            </div>
          </div>

          <?php if ($tournament['mode'] !== 'solo'): ?>
          <!-- PLAYER 2 -->
          <div class="form-section-title"><i class="fas fa-user"></i> Player 2 <?= $tournament['mode'] === 'squad' ? '*' : '' ?></div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Player 2 Real Name <?= $tournament['mode'] === 'squad' ? '*' : '' ?></label>
              <input type="text" class="form-control <?= isset($errors['player2'])?'is-invalid':'' ?>" name="player2_name" value="<?= Security::sanitize($_POST['player2_name'] ?? '') ?>" placeholder="Real name" <?= $tournament['mode']==='squad'?'required':'' ?>>
            </div>
            <div class="form-group">
              <label class="form-label">Player 2 In-Game Name (IGN) <?= $tournament['mode'] === 'squad' ? '*' : '' ?></label>
              <input type="text" class="form-control" name="player2_ign" value="<?= Security::sanitize($_POST['player2_ign'] ?? '') ?>" placeholder="IGN in BGMI" <?= $tournament['mode']==='squad'?'required':'' ?>>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Player 2 BGMI UID <?= $tournament['mode'] === 'squad' ? '*' : '' ?></label>
              <input type="text" class="form-control" name="player2_uid" value="<?= Security::sanitize($_POST['player2_uid'] ?? '') ?>" placeholder="BGMI UID" <?= $tournament['mode']==='squad'?'required':'' ?>>
            </div>
            <div class="form-group">
              <label class="form-label">Player 2 WhatsApp Number</label>
              <input type="tel" class="form-control" name="player2_whatsapp" value="<?= Security::sanitize($_POST['player2_whatsapp'] ?? '') ?>" placeholder="10-digit number" maxlength="10">
            </div>
          </div>
          <?php if (isset($errors['player2'])): ?><div class="alert alert-danger" style="margin-top:-10px"><?= Security::sanitize($errors['player2']) ?></div><?php endif; ?>
          <?php endif; ?>

          <?php if ($tournament['mode'] === 'squad'): ?>
          <!-- PLAYER 3 -->
          <div class="form-section-title"><i class="fas fa-user"></i> Player 3 *</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Player 3 Real Name *</label>
              <input type="text" class="form-control <?= isset($errors['player3'])?'is-invalid':'' ?>" name="player3_name" value="<?= Security::sanitize($_POST['player3_name'] ?? '') ?>" placeholder="Real name" required>
            </div>
            <div class="form-group">
              <label class="form-label">Player 3 In-Game Name (IGN) *</label>
              <input type="text" class="form-control" name="player3_ign" value="<?= Security::sanitize($_POST['player3_ign'] ?? '') ?>" placeholder="IGN in BGMI" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Player 3 BGMI UID *</label>
              <input type="text" class="form-control" name="player3_uid" value="<?= Security::sanitize($_POST['player3_uid'] ?? '') ?>" placeholder="BGMI UID" required>
            </div>
            <div class="form-group">
              <label class="form-label">Player 3 WhatsApp Number</label>
              <input type="tel" class="form-control" name="player3_whatsapp" value="<?= Security::sanitize($_POST['player3_whatsapp'] ?? '') ?>" placeholder="10-digit number" maxlength="10">
            </div>
          </div>
          <?php if (isset($errors['player3'])): ?><div class="alert alert-danger" style="margin-top:-10px"><?= Security::sanitize($errors['player3']) ?></div><?php endif; ?>

          <!-- PLAYER 4 -->
          <div class="form-section-title"><i class="fas fa-user"></i> Player 4 *</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Player 4 Real Name *</label>
              <input type="text" class="form-control <?= isset($errors['player4'])?'is-invalid':'' ?>" name="player4_name" value="<?= Security::sanitize($_POST['player4_name'] ?? '') ?>" placeholder="Real name" required>
            </div>
            <div class="form-group">
              <label class="form-label">Player 4 In-Game Name (IGN) *</label>
              <input type="text" class="form-control" name="player4_ign" value="<?= Security::sanitize($_POST['player4_ign'] ?? '') ?>" placeholder="IGN in BGMI" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Player 4 BGMI UID *</label>
              <input type="text" class="form-control" name="player4_uid" value="<?= Security::sanitize($_POST['player4_uid'] ?? '') ?>" placeholder="BGMI UID" required>
            </div>
            <div class="form-group">
              <label class="form-label">Player 4 WhatsApp Number</label>
              <input type="tel" class="form-control" name="player4_whatsapp" value="<?= Security::sanitize($_POST['player4_whatsapp'] ?? '') ?>" placeholder="10-digit number" maxlength="10">
            </div>
          </div>
          <?php if (isset($errors['player4'])): ?><div class="alert alert-danger" style="margin-top:-10px"><?= Security::sanitize($errors['player4']) ?></div><?php endif; ?>

          <!-- SUBSTITUTE -->
          <div class="form-section-title"><i class="fas fa-user-clock"></i> Substitute Player (Optional)</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Sub Player Real Name</label>
              <input type="text" class="form-control" name="sub_name" value="<?= Security::sanitize($_POST['sub_name'] ?? '') ?>" placeholder="Real name">
            </div>
            <div class="form-group">
              <label class="form-label">Sub Player IGN</label>
              <input type="text" class="form-control" name="sub_ign" value="<?= Security::sanitize($_POST['sub_ign'] ?? '') ?>" placeholder="IGN in BGMI">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Sub Player BGMI UID</label>
              <input type="text" class="form-control" name="sub_uid" value="<?= Security::sanitize($_POST['sub_uid'] ?? '') ?>" placeholder="BGMI UID">
            </div>
            <div class="form-group">
              <label class="form-label">Sub Player WhatsApp Number</label>
              <input type="tel" class="form-control" name="sub_whatsapp" value="<?= Security::sanitize($_POST['sub_whatsapp'] ?? '') ?>" placeholder="10-digit number" maxlength="10">
            </div>
          </div>
          <?php endif; ?>

          <!-- REFERRAL & COUPON -->
          <div class="form-section-title"><i class="fas fa-tag"></i> Referral & Coupon Code</div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Referral Username <span style="color:var(--text-dim);font-weight:400">(optional)</span></label>
              <input type="text" class="form-control" name="referral_name"
                     value="<?= Security::sanitize($_POST['referral_name'] ?? '') ?>"
                     placeholder="Referral username">
            </div>
            <?php if (!$isFree): ?>
            <div class="form-group">
              <label class="form-label">Coupon Code <span style="color:var(--text-dim);font-weight:400">(optional)</span></label>
              <div style="display:flex;gap:8px">
                <input type="text" class="form-control" id="couponInput" name="coupon_code"
                       value="<?= Security::sanitize($_POST['coupon_code'] ?? '') ?>"
                       placeholder="Enter coupon code" style="text-transform:uppercase;flex:1">
                <button type="button" id="applyCouponBtn" class="btn btn-outline" style="white-space:nowrap;padding:0 16px">
                  <i class="fas fa-check"></i> Apply
                </button>
              </div>
              <div id="couponMsg" style="margin-top:8px;font-size:13px;display:none"></div>
            </div>
            <?php endif; ?>
          </div>

          <?php if (!$isFree): ?>
          <!-- Pricing Summary -->
          <div id="pricingSummary" style="background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:16px;margin-bottom:16px">
            <div style="display:flex;justify-content:space-between;font-size:14px;color:var(--text-muted);margin-bottom:6px">
              <span>Entry Fee</span><span id="origFee">₹<?= number_format((float)$tournament['entry_fee'], 2) ?></span>
            </div>
            <div id="discountRow" style="display:none;justify-content:space-between;font-size:14px;color:#34d399;margin-bottom:6px">
              <span>Discount</span><span id="discountAmt">–₹0</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:700;border-top:1px solid var(--border);padding-top:10px;margin-top:4px">
              <span>Total Payable</span>
              <span id="finalFee" style="color:var(--primary);font-family:'Rajdhani',sans-serif;font-size:20px">
                ₹<?= number_format((float)$tournament['entry_fee'], 2) ?>
              </span>
            </div>
          </div>
          <input type="hidden" name="applied_coupon_final" id="appliedCouponFinal" value="">
          <?php endif; ?>

          <!-- Error alerts -->
          <?php foreach (['uid_duplicate','uid_registered','team_duplicate'] as $ek): ?>
          <?php if (isset($errors[$ek])): ?>
          <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= Security::sanitize($errors[$ek]) ?></div>
          <?php endif; ?>
          <?php endforeach; ?>

          <!-- Terms -->
          <div class="form-group" style="margin-top:12px">
            <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;color:var(--text-muted);font-size:14px">
              <input type="checkbox" id="terms" required style="margin-top:3px;accent-color:var(--primary)">
              I agree to the tournament rules and <a href="#" style="color:var(--primary)">terms of service</a>. I confirm all player UIDs are correct.
            </label>
          </div>

          <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitBtn">
            <i class="fas fa-<?= $isFree ? 'check' : 'credit-card' ?>"></i>
            <?= $isFree ? 'Complete Registration' : 'Proceed to Payment' ?>
            <?php if (!$isFree): ?><span id="submitAmt"> – <?= formatCurrency((float)$tournament['entry_fee']) ?></span><?php endif; ?>
          </button>
        </form>
      </div>

      <!-- Tournament Info Sidebar -->
      <div class="tournament-info-card">
        <div style="font-size:13px;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:1px;margin-bottom:16px">You're Registering For</div>
        <h3 style="margin-bottom:16px;font-size:18px"><?= Security::sanitize($tournament['name']) ?></h3>

        <?php if (!$isFree): ?>
        <div class="fee-box">
          <div style="font-size:12px;color:var(--text-muted);margin-bottom:4px">Entry Fee</div>
          <div class="fee-amount"><?= formatCurrency((float)$tournament['entry_fee']) ?></div>
          <div style="font-size:12px;color:var(--text-muted);margin-top:4px">Secure payment via PayU</div>
        </div>
        <?php else: ?>
        <div class="fee-box">
          <div class="fee-amount" style="font-size:24px;color:var(--success)">FREE ENTRY</div>
          <div style="font-size:12px;color:var(--text-muted);margin-top:4px">No payment required</div>
        </div>
        <?php endif; ?>

        <div style="font-size:13px;color:var(--text-muted)">
          <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)"><span>Prize Pool</span><strong style="color:var(--primary)"><?= formatCurrency((float)$tournament['prize_pool']) ?></strong></div>
          <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)"><span>Mode</span><strong style="color:var(--text-primary)"><?= strtoupper($tournament['mode']) ?></strong></div>
          <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)"><span>Slots Left</span><strong style="color:<?= $avail <= 10 ? 'var(--danger)' : 'var(--success)' ?>"><?= $avail ?></strong></div>
          <div style="display:flex;justify-content:space-between;padding:8px 0"><span>Deadline</span><strong style="color:var(--text-primary);font-size:12px"><?= date('d M Y', strtotime($tournament['registration_deadline'])) ?></strong></div>
        </div>

        <div style="margin-top:16px;padding:12px;background:var(--bg-surface);border-radius:var(--radius-sm);font-size:12px;color:var(--text-dim)">
          <i class="fas fa-lock" style="color:var(--primary);margin-right:6px"></i>
          Your information is encrypted and secure.
        </div>
        <div style="margin-top:12px;padding:12px;background:var(--bg-surface);border-radius:var(--radius-sm);font-size:12px;color:var(--text-dim)">
          <i class="fas fa-envelope" style="color:var(--primary);margin-right:6px"></i>
          Confirmation will be sent to your email.
        </div>
      </div>
    </div>
  </div>
</section>

<script>
document.getElementById('regForm').addEventListener('submit', function(e) {
  const terms = document.getElementById('terms');
  if (!terms.checked) { e.preventDefault(); alert('Please accept the terms and conditions.'); return; }
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Processing...';
});
// Copy mobile to whatsapp
document.getElementById('mobile').addEventListener('input', function(){
  const wa = document.getElementById('whatsapp');
  if (!wa.value) wa.value = this.value;
});

// ── Coupon AJAX ──────────────────────────────────────────────
const couponBtn  = document.getElementById('applyCouponBtn');
const couponMsg  = document.getElementById('couponMsg');
const couponInput = document.getElementById('couponInput');
const origFee    = <?= (float)$tournament['entry_fee'] ?>;

if (couponBtn) {
  couponBtn.addEventListener('click', function() {
    const code = couponInput.value.trim().toUpperCase();
    if (!code) { showCouponMsg('Enter a coupon code first.', false); return; }

    couponBtn.disabled = true;
    couponBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    const fd = new FormData();
    fd.append('code', code);
    fd.append('entry_fee', origFee);
    fd.append('tournament_id', <?= $id ?>);

    fetch('/validate_coupon.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        couponBtn.disabled = false;
        couponBtn.innerHTML = '<i class="fas fa-check"></i> Apply';

        showCouponMsg(data.message, data.valid);

        if (data.valid) {
          // Update pricing UI
          document.getElementById('discountAmt').textContent = '–₹' + data.discount_amount.toFixed(2);
          document.getElementById('discountRow').style.display = 'flex';
          document.getElementById('finalFee').textContent = '₹' + data.final_amount.toFixed(2);
          const submitAmt = document.getElementById('submitAmt');
          if (submitAmt) submitAmt.textContent = ' – ₹' + data.final_amount.toFixed(2);
          couponInput.readOnly = true;
          couponBtn.innerHTML = '<i class="fas fa-times"></i> Remove';
          couponBtn.onclick = removeCoupon;
        }
      })
      .catch(() => {
        couponBtn.disabled = false;
        couponBtn.innerHTML = '<i class="fas fa-check"></i> Apply';
        showCouponMsg('Network error. Please try again.', false);
      });
  });
}

function removeCoupon() {
  couponInput.value = '';
  couponInput.readOnly = false;
  document.getElementById('discountRow').style.display = 'none';
  document.getElementById('finalFee').textContent = '₹' + origFee.toFixed(2);
  const submitAmt = document.getElementById('submitAmt');
  if (submitAmt) submitAmt.textContent = ' – ₹' + origFee.toFixed(2);
  couponMsg.style.display = 'none';
  couponBtn.innerHTML = '<i class="fas fa-check"></i> Apply';
  couponBtn.onclick = null;
  couponBtn.addEventListener('click', arguments.callee);
}

function showCouponMsg(msg, success) {
  couponMsg.style.display = 'block';
  couponMsg.style.color   = success ? '#34d399' : '#f87171';
  couponMsg.innerHTML = (success ? '✅ ' : '❌ ') + msg;
}
</script>
</body>
</html>
