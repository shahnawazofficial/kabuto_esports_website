<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/PayUGateway.php';
require_once __DIR__ . '/includes/EmailService.php';

Security::setSecurityHeaders();

// PayU POSTs to this URL after successful payment
$postData = $_POST;

if (empty($postData) || empty($postData['txnid'])) {
    // May also come as GET redirect
    $regId = Security::clean($_GET['reg'] ?? '');
    $isFree = !empty($_GET['free']);

    if ($regId) {
        $reg = Database::fetchOne("SELECT r.*, t.name as tournament_name, t.slug FROM registrations r JOIN tournaments t ON r.tournament_id = t.id WHERE r.registration_id = ?", [$regId]);
        if (!$reg) redirect('/tournaments');
        renderSuccess($reg, $isFree);
        exit;
    }
    redirect('/tournaments');
}

// Verify hash
if (!PayUGateway::verifyHash($postData)) {
    error_log('PayU hash mismatch: ' . json_encode($postData));
    redirect('/payment/failure?reason=hash_mismatch');
}

$txnId  = $postData['txnid'];
$status = strtolower($postData['status'] ?? '');

// Find registration
$reg = Database::fetchOne(
    "SELECT r.*, t.name as tournament_name, t.slug FROM registrations r 
     JOIN tournaments t ON r.tournament_id = t.id 
     WHERE r.transaction_id = ?",
    [$txnId]
);

if (!$reg) redirect('/tournaments');

if ($status === 'success') {
    Database::query(
        "UPDATE registrations SET payment_status='paid', payment_reference=?, amount_paid=? WHERE transaction_id=?",
        [$postData['mihpayid'] ?? '', $postData['amount'] ?? 0, $txnId]
    );
    PayUGateway::updatePayment($txnId, 'success', $postData, $postData['mihpayid'] ?? null, $postData['bank_ref_num'] ?? null, $postData['mode'] ?? null);

    // Send confirmation email
    if (!$reg['confirmation_sent']) {
        EmailService::sendRegistrationConfirmation($reg, ['name'=>$reg['tournament_name'],'slug'=>$reg['slug'],'tournament_start'=>date('Y-m-d')]);
        EmailService::sendAdminNotification($reg, ['name'=>$reg['tournament_name'],'slug'=>$reg['slug']]);
        Database::query("UPDATE registrations SET confirmation_sent=1 WHERE id=?", [$reg['id']]);
    }

    renderSuccess($reg, false);
} else {
    Database::query("UPDATE registrations SET payment_status='failed' WHERE transaction_id=?", [$txnId]);
    PayUGateway::updatePayment($txnId, 'failure', $postData);
    redirect('/payment/failure?txn=' . urlencode($txnId));
}

function renderSuccess(array $reg, bool $isFree): void {
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registration Confirmed! – Kabuto Esports</title>
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.success-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.success-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-xl);padding:48px;max-width:600px;width:100%;text-align:center}
.checkmark{width:80px;height:80px;border-radius:50%;background:rgba(16,185,129,.15);border:2px solid var(--success);display:flex;align-items:center;justify-content:center;font-size:36px;margin:0 auto 24px;animation:pop .5s cubic-bezier(.17,.67,.3,1.33)}
@keyframes pop{from{transform:scale(0)}to{transform:scale(1)}}
.reg-id{font-family:'Orbitron',sans-serif;font-size:22px;font-weight:700;color:var(--primary);background:rgba(245,166,35,.1);border:1px solid rgba(245,166,35,.2);border-radius:var(--radius-md);padding:16px 24px;margin:24px 0;letter-spacing:3px}
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;text-align:left;margin:24px 0}
.detail-item{background:var(--bg-surface);border-radius:var(--radius-sm);padding:12px}
.detail-item .lbl{font-size:11px;color:var(--text-dim);text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px}
.detail-item .val{font-size:15px;font-weight:600;color:var(--text-primary)}
.confetti{position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:999}
</style>
</head>
<body>
<canvas class="confetti" id="confetti"></canvas>

<nav class="navbar scrolled"><div class="navbar-inner"><a href="/" class="navbar-brand">⚔️ <span>KABUTO <span style="color:var(--primary)">ESPORTS</span></span></a><div class="nav-links"><a href="/tournaments">Browse Tournaments</a></div></div></nav>

<div style="padding-top:68px" class="success-wrap">
  <div class="success-card">
    <div class="checkmark">✓</div>
    <h1 style="font-size:28px;margin-bottom:8px">Registration Confirmed!</h1>
    <p style="color:var(--text-muted);margin-bottom:8px">
      <?= $isFree ? 'Your free entry has been confirmed!' : 'Payment received! Your registration is confirmed.' ?>
    </p>
    <p style="color:var(--text-muted);font-size:14px">A confirmation email has been sent to <strong style="color:var(--text-primary)"><?= Security::sanitize($reg['email']) ?></strong></p>

    <div class="reg-id"><?= Security::sanitize($reg['registration_id']) ?></div>

    <div class="detail-grid">
      <div class="detail-item"><div class="lbl">Team Name</div><div class="val"><?= Security::sanitize($reg['team_name']) ?></div></div>
      <div class="detail-item"><div class="lbl">Tournament</div><div class="val"><?= Security::sanitize($reg['tournament_name'] ?? '') ?></div></div>
      <div class="detail-item"><div class="lbl">Team Leader</div><div class="val"><?= Security::sanitize($reg['leader_name']) ?></div></div>
      <div class="detail-item"><div class="lbl">Payment Status</div><div class="val" style="color:var(--success)"><?= $isFree ? 'Free Entry ✓' : 'Paid ✓' ?></div></div>
    </div>

    <div style="background:var(--bg-surface);border-radius:var(--radius-md);padding:16px;margin-bottom:24px;font-size:14px;color:var(--text-muted);text-align:left">
      <strong style="color:var(--primary);display:block;margin-bottom:8px">📢 What's Next?</strong>
      <ul style="padding-left:20px;line-height:2">
        <li>Join our Discord server for room ID and match updates</li>
        <li>Be ready 15 minutes before the tournament start time</li>
        <li>Save your Registration ID: <strong style="color:var(--text-primary)"><?= Security::sanitize($reg['registration_id']) ?></strong></li>
        <li>Check your email for detailed instructions</li>
      </ul>
    </div>

    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
      <a href="/receipt/<?= urlencode($reg['registration_id']) ?>" class="btn btn-outline">
        <i class="fas fa-download"></i> Download Receipt
      </a>
      <a href="/tournaments" class="btn btn-primary">
        <i class="fas fa-trophy"></i> More Tournaments
      </a>
    </div>

    <div style="margin-top:20px">
      <a href="https://discord.gg/kabutoesports" target="_blank" class="btn btn-sm" style="background:rgba(114,137,218,.15);color:#7289da;border:1px solid rgba(114,137,218,.3)">
        <i class="fab fa-discord"></i> Join Discord for Room ID
      </a>
    </div>
  </div>
</div>

<script>
// Simple confetti animation
const canvas=document.getElementById('confetti'),ctx=canvas.getContext('2d');
canvas.width=window.innerWidth;canvas.height=window.innerHeight;
const particles=[];const colors=['#f5a623','#7c3aed','#10b981','#3b82f6','#ef4444'];
for(let i=0;i<120;i++)particles.push({x:Math.random()*canvas.width,y:Math.random()*-canvas.height,r:Math.random()*6+2,c:colors[Math.floor(Math.random()*colors.length)],s:Math.random()*3+1,a:Math.random()*360});
let frame=0;
function draw(){
  ctx.clearRect(0,0,canvas.width,canvas.height);
  particles.forEach(p=>{
    ctx.save();ctx.translate(p.x,p.y);ctx.rotate(p.a*Math.PI/180);
    ctx.fillStyle=p.c;ctx.globalAlpha=0.8;
    ctx.fillRect(-p.r/2,-p.r/2,p.r,p.r);ctx.restore();
    p.y+=p.s;p.a+=2;
    if(p.y>canvas.height)p.y=-10;
  });
  frame++;if(frame<200)requestAnimationFrame(draw);else{canvas.style.display='none';}
}
draw();
</script>
</body>
</html>
<?php } ?>
