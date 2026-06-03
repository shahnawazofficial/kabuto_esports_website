<?php
require_once __DIR__ . '/includes/functions.php';

$regCode = Security::clean($_GET['reg_id'] ?? '');
if (!$regCode) redirect('/check-status');

$reg = Database::fetchOne(
    "SELECT r.*, t.name as tournament_name, t.slug, t.mode, t.entry_fee, t.prize_pool, t.tournament_start
     FROM registrations r JOIN tournaments t ON r.tournament_id = t.id
     WHERE r.registration_id = ?",
    [$regCode]
);

if (!$reg) {
    http_response_code(404);
    $error = 'Registration not found. Please check your Registration ID.';
}

$isFree = $reg ? (float)$reg['entry_fee'] == 0 || $reg['payment_status'] === 'free' : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registration Receipt – Kabuto Esports</title>
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.receipt-page{max-width:700px;margin:40px auto;padding:20px}
.receipt-card{background:var(--bg-card);border:1px solid var(--border);border-radius:20px;overflow:hidden}
.receipt-header{background:linear-gradient(135deg,#0a0a0f,#1a1a2e);padding:32px;border-bottom:3px dashed rgba(245,166,35,.3);position:relative}
.receipt-header::after{content:'KABUTO ESPORTS';position:absolute;bottom:-12px;right:24px;font-family:'Orbitron',sans-serif;font-size:10px;color:rgba(245,166,35,.3);letter-spacing:3px}
.receipt-body{padding:28px}
.receipt-row{display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px dashed var(--border)}
.receipt-row:last-child{border-bottom:none}
.receipt-label{font-size:13px;color:var(--text-muted)}
.receipt-value{font-size:14px;font-weight:600;color:var(--text-primary);text-align:right}
.player-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px}
.player-item{background:var(--bg-surface);padding:12px;border-radius:8px}
.player-item .pname{font-weight:600;font-size:14px}
.player-item .puid{font-family:'Courier New',monospace;font-size:12px;color:var(--text-muted);margin-top:2px}
@media print{nav,#printBtn,#backBtn{display:none!important}.receipt-card{border:1px solid #ccc}body{background:#fff;color:#000}}
@media(max-width:480px){.player-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<nav class="navbar scrolled"><div class="navbar-inner"><a href="/" class="navbar-brand">⚔️ <span>KABUTO <span style="color:var(--primary)">ESPORTS</span></span></a></div></nav>
<div style="padding-top:88px"></div>

<div class="receipt-page">
  <?php if (!empty($error)): ?>
  <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
  <a href="/check-status" class="btn btn-primary">← Check Another</a>
  <?php else: ?>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <a href="/check-status" class="btn btn-outline btn-sm" id="backBtn"><i class="fas fa-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="btn btn-primary btn-sm" id="printBtn"><i class="fas fa-print"></i> Print / Save PDF</button>
  </div>

  <div class="receipt-card">
    <!-- Header -->
    <div class="receipt-header">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div>
          <div style="font-family:'Orbitron',sans-serif;font-size:20px;font-weight:900;color:var(--primary)">⚔️ KABUTO ESPORTS</div>
          <div style="color:var(--text-muted);font-size:12px;margin-top:4px">Tournament Registration Receipt</div>
        </div>
        <div style="text-align:right">
          <div style="font-size:10px;color:var(--text-dim);margin-bottom:2px">Registration ID</div>
          <div style="font-family:'Orbitron',sans-serif;font-size:16px;color:var(--primary);letter-spacing:2px"><?= htmlspecialchars($reg['registration_id']) ?></div>
        </div>
      </div>
    </div>

    <!-- Body -->
    <div class="receipt-body">
      <!-- Tournament Info -->
      <div style="margin-bottom:20px">
        <div style="font-size:12px;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:1px;margin-bottom:12px">Tournament Details</div>
        <div class="receipt-row"><span class="receipt-label">Tournament</span><span class="receipt-value"><?= htmlspecialchars($reg['tournament_name']) ?></span></div>
        <div class="receipt-row"><span class="receipt-label">Game Mode</span><span class="receipt-value"><span class="badge badge-<?= $reg['mode'] ?>"><?= strtoupper($reg['mode']) ?></span></span></div>
        <div class="receipt-row"><span class="receipt-label">Entry Fee</span><span class="receipt-value" style="color:var(--primary)">₹<?= number_format((float)$reg['entry_fee'],2) ?></span></div>
        <?php if ($reg['tournament_start']): ?><div class="receipt-row"><span class="receipt-label">Tournament Date</span><span class="receipt-value"><?= date('d M Y, h:i A', strtotime($reg['tournament_start'])) ?></span></div><?php endif; ?>
      </div>

      <!-- Team Info -->
      <div style="margin-bottom:20px;padding-top:16px;border-top:1px solid var(--border)">
        <div style="font-size:12px;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:1px;margin-bottom:12px">Team Information</div>
        <div class="receipt-row"><span class="receipt-label">Team Name</span><span class="receipt-value"><?= htmlspecialchars($reg['team_name']) ?></span></div>
        <div class="receipt-row"><span class="receipt-label">Team Leader</span><span class="receipt-value"><?= htmlspecialchars($reg['leader_name']) ?></span></div>
        <div class="receipt-row"><span class="receipt-label">Leader UID</span><span class="receipt-value" style="font-family:'Courier New',monospace"><?= htmlspecialchars($reg['leader_uid']) ?></span></div>
        <div class="receipt-row"><span class="receipt-label">IGN</span><span class="receipt-value"><?= htmlspecialchars($reg['leader_ign']) ?></span></div>
        <div class="receipt-row"><span class="receipt-label">Contact</span><span class="receipt-value"><?= htmlspecialchars($reg['mobile']) ?></span></div>
        <div class="receipt-row"><span class="receipt-label">Email</span><span class="receipt-value" style="font-size:13px"><?= htmlspecialchars($reg['email']) ?></span></div>
      </div>

      <!-- Squad Players -->
      <?php if ($reg['player2_name']): ?>
      <div style="margin-bottom:20px;padding-top:16px;border-top:1px solid var(--border)">
        <div style="font-size:12px;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:1px;margin-bottom:12px">Squad Roster</div>
        <div class="player-grid">
          <div class="player-item"><div style="font-size:10px;color:var(--text-dim);margin-bottom:4px">LEADER</div><div class="pname"><?= htmlspecialchars($reg['leader_name']) ?></div><div class="puid"><?= htmlspecialchars($reg['leader_uid']) ?></div></div>
          <?php if ($reg['player2_name']): ?><div class="player-item"><div style="font-size:10px;color:var(--text-dim);margin-bottom:4px">PLAYER 2</div><div class="pname"><?= htmlspecialchars($reg['player2_name']) ?></div><div class="puid"><?= htmlspecialchars($reg['player2_uid']) ?></div></div><?php endif; ?>
          <?php if ($reg['player3_name']): ?><div class="player-item"><div style="font-size:10px;color:var(--text-dim);margin-bottom:4px">PLAYER 3</div><div class="pname"><?= htmlspecialchars($reg['player3_name']) ?></div><div class="puid"><?= htmlspecialchars($reg['player3_uid']) ?></div></div><?php endif; ?>
          <?php if ($reg['player4_name']): ?><div class="player-item"><div style="font-size:10px;color:var(--text-dim);margin-bottom:4px">PLAYER 4</div><div class="pname"><?= htmlspecialchars($reg['player4_name']) ?></div><div class="puid"><?= htmlspecialchars($reg['player4_uid']) ?></div></div><?php endif; ?>
          <?php if ($reg['sub_name']): ?><div class="player-item"><div style="font-size:10px;color:var(--text-dim);margin-bottom:4px">SUBSTITUTE</div><div class="pname"><?= htmlspecialchars($reg['sub_name']) ?></div><div class="puid"><?= htmlspecialchars($reg['sub_uid']) ?></div></div><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Payment Info -->
      <div style="padding-top:16px;border-top:1px solid var(--border)">
        <div style="font-size:12px;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:1px;margin-bottom:12px">Payment Information</div>
        <div class="receipt-row"><span class="receipt-label">Payment Status</span>
          <span class="receipt-value">
            <?php $s=$reg['payment_status']; echo '<span class="badge badge-'.($s==='paid'||$s==='free'?'success':($s==='pending'?'warning':'danger')).'">'.strtoupper($s).'</span>'; ?>
          </span>
        </div>
        <?php if ($reg['transaction_id']): ?><div class="receipt-row"><span class="receipt-label">Transaction ID</span><span class="receipt-value" style="font-family:'Courier New',monospace;font-size:12px"><?= htmlspecialchars($reg['transaction_id']) ?></span></div><?php endif; ?>
        <?php if ($reg['amount_paid'] > 0): ?><div class="receipt-row"><span class="receipt-label">Amount Paid</span><span class="receipt-value" style="color:var(--success)">₹<?= number_format((float)$reg['amount_paid'],2) ?></span></div><?php endif; ?>
        <div class="receipt-row"><span class="receipt-label">Registered On</span><span class="receipt-value"><?= date('d M Y, h:i A', strtotime($reg['created_at'])) ?></span></div>
      </div>

      <!-- Footer note -->
      <div style="margin-top:24px;padding:16px;background:var(--bg-surface);border-radius:8px;text-align:center;font-size:12px;color:var(--text-muted)">
        This is a computer-generated receipt. For queries: support@kabutoesports.com | kabutoesports.com
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
