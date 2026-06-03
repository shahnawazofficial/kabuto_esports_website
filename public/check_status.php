<?php
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$reg    = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $input = Security::clean($_POST['lookup'] ?? '');
        if (strlen($input) < 3) {
            $errors[] = 'Please enter a valid Registration ID or email.';
        } else {
            $reg = Database::fetchOne(
                "SELECT r.*, t.name as tournament_name, t.slug, t.status as t_status
                 FROM registrations r JOIN tournaments t ON r.tournament_id = t.id
                 WHERE r.registration_id = ? OR r.email = ?",
                [$input, $input]
            );
            if (!$reg) $errors[] = 'No registration found for: ' . htmlspecialchars($input);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Check Registration Status – Kabuto Esports</title>
<meta name="description" content="Check your BGMI tournament registration status on Kabuto Esports.">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<nav class="navbar scrolled"><div class="navbar-inner">
  <a href="/" class="navbar-brand">⚔️ <span>KABUTO <span style="color:var(--primary)">ESPORTS</span></span></a>
  <div class="nav-links"><a href="/">Home</a><a href="/tournaments">Tournaments</a></div>
</div></nav>
<div style="padding-top:68px"></div>

<section class="section">
  <div class="container" style="max-width:600px">
    <div style="text-align:center;margin-bottom:40px">
      <h1>Check Registration Status</h1>
      <p style="color:var(--text-muted);margin-top:8px">Enter your Registration ID or email address</p>
    </div>

    <?php foreach ($errors as $e): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($e) ?></div><?php endforeach; ?>

    <div class="card" style="padding:32px">
      <form method="POST">
        <?= Security::csrfField() ?>
        <div class="form-group">
          <label class="form-label" for="lookup">Registration ID or Email *</label>
          <input type="text" class="form-control" id="lookup" name="lookup" value="<?= htmlspecialchars($_POST['lookup'] ?? '') ?>" placeholder="KAB-2026-00001 or your@email.com" required>
          <p style="font-size:12px;color:var(--text-muted);margin-top:6px">Your Registration ID was sent to your email after registration.</p>
        </div>
        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i> Check Status</button>
      </form>
    </div>

    <?php if ($reg): ?>
    <div class="card" style="margin-top:24px;padding:28px">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
        <?php $s=$reg['payment_status']; $paid=in_array($s,['paid','free']); ?>
        <div style="width:48px;height:48px;border-radius:50%;background:<?= $paid?'rgba(16,185,129,.15)':'rgba(251,191,36,.15)' ?>;display:flex;align-items:center;justify-content:center;font-size:22px">
          <?= $paid?'✅':'⏳' ?>
        </div>
        <div>
          <div style="font-size:18px;font-weight:700"><?= htmlspecialchars($reg['team_name']) ?></div>
          <div style="font-size:13px;color:var(--text-muted)"><?= htmlspecialchars($reg['tournament_name']) ?></div>
        </div>
      </div>

      <div style="background:var(--bg-surface);border-radius:8px;padding:16px;margin-bottom:16px;text-align:center">
        <div style="font-size:11px;color:var(--text-dim);margin-bottom:4px">Registration ID</div>
        <div style="font-family:'Orbitron',sans-serif;font-size:18px;color:var(--primary);letter-spacing:2px"><?= htmlspecialchars($reg['registration_id']) ?></div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px">
        <?php $rows=[['Leader',$reg['leader_name']],['Email',$reg['email']],['Payment',strtoupper($reg['payment_status'])],['Registered',date('d M Y',strtotime($reg['created_at']))]]; ?>
        <?php foreach ($rows as [$l,$v]): ?>
        <div style="background:var(--bg-surface);padding:10px;border-radius:6px"><div style="font-size:10px;color:var(--text-dim)"><?= $l ?></div><div style="font-size:13px;font-weight:600;margin-top:2px;color:<?= $l==='Payment'&&$paid?'var(--success)':($l==='Payment'?'var(--warning)':'var(--text-primary)') ?>"><?= htmlspecialchars($v) ?></div></div>
        <?php endforeach; ?>
      </div>

      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="/receipt/<?= urlencode($reg['registration_id']) ?>" class="btn btn-outline btn-sm"><i class="fas fa-file-alt"></i> View Receipt</a>
        <a href="/tournament/<?= htmlspecialchars($reg['slug']) ?>" class="btn btn-primary btn-sm"><i class="fas fa-trophy"></i> Tournament Details</a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
</body>
</html>
