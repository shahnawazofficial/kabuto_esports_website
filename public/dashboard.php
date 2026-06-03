<?php
/**
 * KABUTO ESPORTS — User Dashboard
 * Shows user's tournament registrations, payment status
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/UserAuth.php';

// Must be logged in
$user = UserAuth::require('/login');
$registrations = UserAuth::getRegistrations($user['id']);

$welcome = isset($_GET['welcome']);

// Stats
$totalRegs  = count($registrations);
$paidRegs   = count(array_filter($registrations, fn($r) => $r['payment_status'] === 'paid' || $r['payment_status'] === 'free'));
$pendingRegs = count(array_filter($registrations, fn($r) => $r['payment_status'] === 'pending'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Dashboard – Kabuto Esports</title>
<link rel="stylesheet" href="/assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.dashboard-wrap{max-width:1100px;margin:0 auto;padding:100px 20px 60px}
.dash-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:36px}
.dash-greeting h1{font-size:26px;font-weight:700;margin:0 0 4px}
.dash-greeting h1 span{color:var(--primary)}
.dash-greeting p{color:var(--text-muted);font-size:14px;margin:0}
.dash-actions{display:flex;gap:12px;flex-wrap:wrap}
.stat-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:36px}
.stat-card{background:var(--bg-card);border:1px solid var(--border);border-radius:14px;padding:20px 22px;position:relative;overflow:hidden}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.stat-card.blue::before{background:linear-gradient(90deg,#3b82f6,#6366f1)}
.stat-card.green::before{background:linear-gradient(90deg,#10b981,#06b6d4)}
.stat-card.orange::before{background:linear-gradient(90deg,var(--primary),#f97316)}
.stat-card.purple::before{background:linear-gradient(90deg,#8b5cf6,#ec4899)}
.stat-num{font-family:'Orbitron',sans-serif;font-size:30px;font-weight:700;margin:8px 0 4px}
.stat-label{color:var(--text-muted);font-size:13px}
.stat-icon{position:absolute;right:16px;top:50%;transform:translateY(-50%);font-size:32px;opacity:.1}
.section-title{font-size:18px;font-weight:700;margin-bottom:20px;display:flex;align-items:center;gap:10px}
.section-title .badge{font-size:12px;background:var(--primary-glow);color:var(--primary);padding:3px 10px;border-radius:20px;font-family:'Inter',sans-serif;font-weight:600}
.reg-card{background:var(--bg-card);border:1px solid var(--border);border-radius:16px;overflow:hidden;margin-bottom:16px;transition:.2s}
.reg-card:hover{border-color:var(--border-bright);box-shadow:0 8px 30px rgba(0,0,0,.3)}
.reg-card-inner{display:grid;grid-template-columns:80px 1fr auto;gap:0}
.reg-banner{width:80px;min-height:100px;object-fit:cover;background:#1a1a2e}
.reg-body{padding:18px 20px}
.reg-body h3{font-size:15px;font-weight:700;margin:0 0 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.reg-meta{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px}
.reg-tag{font-size:11px;padding:3px 10px;border-radius:20px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.tag-squad{background:rgba(59,130,246,.15);color:#60a5fa}
.tag-solo{background:rgba(16,185,129,.15);color:#34d399}
.tag-duo{background:rgba(139,92,246,.15);color:#a78bfa}
.reg-info{display:flex;flex-wrap:wrap;gap:16px;font-size:13px;color:var(--text-muted)}
.reg-info span i{margin-right:4px;color:var(--primary);font-size:11px}
.reg-side{padding:18px 20px;display:flex;flex-direction:column;align-items:flex-end;gap:10px;border-left:1px solid var(--border);min-width:160px}
.status-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.status-paid,.status-free{background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.3)}
.status-pending{background:rgba(245,158,11,.15);color:#fbbf24;border:1px solid rgba(245,158,11,.3)}
.status-failed{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3)}
.reg-id{font-family:'Orbitron',sans-serif;font-size:11px;color:var(--text-dim)}
.reg-date{font-size:12px;color:var(--text-dim)}
.empty-state{text-align:center;padding:60px 20px;background:var(--bg-card);border:1px solid var(--border);border-radius:16px}
.empty-state .icon{font-size:56px;margin-bottom:16px;opacity:.5}
.empty-state h3{font-size:18px;margin-bottom:8px}
.empty-state p{color:var(--text-muted);margin-bottom:24px}
.profile-card{background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:24px;margin-bottom:24px}
.profile-avatar{width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:#fff;font-family:'Orbitron',sans-serif;flex-shrink:0}
.profile-header{display:flex;align-items:center;gap:16px;margin-bottom:16px}
.profile-name{font-size:18px;font-weight:700;margin:0 0 2px}
.profile-email{font-size:13px;color:var(--text-muted)}
.profile-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;border-top:1px solid var(--border);padding-top:16px}
.profile-stat{text-align:center}
.profile-stat-num{font-family:'Orbitron',sans-serif;font-size:18px;font-weight:700;color:var(--primary)}
.profile-stat-label{font-size:11px;color:var(--text-dim);text-transform:uppercase;letter-spacing:.5px}
.welcome-banner{background:linear-gradient(135deg,rgba(245,166,35,.15),rgba(139,92,246,.1));border:1px solid rgba(245,166,35,.3);border-radius:14px;padding:20px 24px;margin-bottom:28px;display:flex;align-items:center;gap:16px}
.welcome-banner .icon{font-size:32px}
@media(max-width:640px){.reg-card-inner{grid-template-columns:1fr}.reg-banner{width:100%;height:120px;min-height:unset}.reg-side{border-left:none;border-top:1px solid var(--border);flex-direction:row;flex-wrap:wrap;align-items:center;justify-content:space-between}.dash-header{flex-direction:column;align-items:flex-start}}
</style>
</head>
<body>

<nav class="navbar" id="navbar">
  <div class="navbar-inner">
    <a href="/" class="navbar-brand">&#9876; <span>KABUTO <span style="color:var(--primary)">ESPORTS</span></span></a>
    <div class="nav-links">
      <a href="/">Home</a>
      <a href="/tournaments">Tournaments</a>
      <a href="/dashboard" class="active">My Dashboard</a>
      <a href="/logout" class="nav-cta" style="background:rgba(239,68,68,.15);color:#f87171;border-color:rgba(239,68,68,.3)">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
    <div class="nav-hamburger" id="hamburger" onclick="toggleNav()"><span></span><span></span><span></span></div>
  </div>
</nav>

<div class="dashboard-wrap">

  <?php if ($welcome): ?>
  <div class="welcome-banner">
    <div class="icon">&#127881;</div>
    <div>
      <strong style="font-size:16px">Welcome to Kabuto Esports, <?= htmlspecialchars($user['name']) ?>!</strong>
      <p style="margin:4px 0 0;color:var(--text-muted);font-size:14px">Your account is ready. Browse tournaments and register to compete!</p>
    </div>
  </div>
  <?php endif; ?>

  <div class="dash-header">
    <div class="dash-greeting">
      <h1>Hello, <span><?= htmlspecialchars(explode(' ', $user['name'])[0]) ?></span> &#128075;</h1>
      <p>Here's your tournament activity overview</p>
    </div>
    <div class="dash-actions">
      <a href="/tournaments" class="btn btn-primary"><i class="fas fa-trophy"></i> Browse Tournaments</a>
    </div>
  </div>

  <!-- Stats -->
  <div class="stat-cards">
    <div class="stat-card blue">
      <div class="stat-icon"><i class="fas fa-list"></i></div>
      <div class="stat-label">Total Registered</div>
      <div class="stat-num"><?= $totalRegs ?></div>
    </div>
    <div class="stat-card green">
      <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
      <div class="stat-label">Confirmed</div>
      <div class="stat-num"><?= $paidRegs ?></div>
    </div>
    <div class="stat-card orange">
      <div class="stat-icon"><i class="fas fa-clock"></i></div>
      <div class="stat-label">Payment Pending</div>
      <div class="stat-num"><?= $pendingRegs ?></div>
    </div>
    <div class="stat-card purple">
      <div class="stat-icon"><i class="fas fa-user"></i></div>
      <div class="stat-label">Player UID</div>
      <div class="stat-num" style="font-size:16px"><?= $user['bgmi_uid'] ? htmlspecialchars($user['bgmi_uid']) : '—' ?></div>
    </div>
  </div>

  <!-- Profile Card -->
  <div class="profile-card">
    <div class="profile-header">
      <div class="profile-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
      <div>
        <div class="profile-name"><?= htmlspecialchars($user['name']) ?></div>
        <div class="profile-email"><?= htmlspecialchars($user['email']) ?> &nbsp;|&nbsp; <?= htmlspecialchars($user['mobile'] ?? '—') ?></div>
      </div>
    </div>
    <div class="profile-stats">
      <div class="profile-stat">
        <div class="profile-stat-num"><?= htmlspecialchars($user['bgmi_uid'] ?? '—') ?></div>
        <div class="profile-stat-label">BGMI UID</div>
      </div>
      <div class="profile-stat">
        <div class="profile-stat-num"><?= htmlspecialchars($user['bgmi_ign'] ?? '—') ?></div>
        <div class="profile-stat-label">IGN</div>
      </div>
      <div class="profile-stat">
        <div class="profile-stat-num"><?= date('M Y', strtotime($user['created_at'])) ?></div>
        <div class="profile-stat-label">Member Since</div>
      </div>
    </div>
  </div>

  <!-- Registrations -->
  <div class="section-title">
    <i class="fas fa-gamepad" style="color:var(--primary)"></i>
    My Registrations
    <span class="badge"><?= $totalRegs ?></span>
  </div>

  <?php if (empty($registrations)): ?>
  <div class="empty-state">
    <div class="icon">&#127942;</div>
    <h3>No Registrations Yet</h3>
    <p>You haven't registered for any tournaments. Join one now and start competing!</p>
    <a href="/tournaments" class="btn btn-primary btn-lg">
      <i class="fas fa-trophy"></i> Browse Tournaments
    </a>
  </div>

  <?php else: ?>
  <?php foreach ($registrations as $reg):
    $bannerUrl = !empty($reg['banner']) ? '/uploads/banners/' . htmlspecialchars($reg['banner']) : '/assets/img/default-banner.svg';
    $statusClass = 'status-' . $reg['payment_status'];
    $statusLabel = match($reg['payment_status']) {
      'paid'    => '&#10003; Confirmed',
      'free'    => '&#10003; Free Entry',
      'pending' => '&#8987; Payment Pending',
      'failed'  => '&#10007; Payment Failed',
      default   => ucfirst($reg['payment_status'])
    };
  ?>
  <div class="reg-card">
    <div class="reg-card-inner">
      <img src="<?= $bannerUrl ?>" alt="<?= htmlspecialchars($reg['tournament_name']) ?>" class="reg-banner">
      <div class="reg-body">
        <h3><?= htmlspecialchars($reg['tournament_name']) ?></h3>
        <div class="reg-meta">
          <span class="reg-tag tag-<?= $reg['mode'] ?>"><?= strtoupper($reg['mode']) ?></span>
          <span class="reg-tag" style="background:rgba(245,166,35,.1);color:var(--primary)"><?= htmlspecialchars($reg['game']) ?></span>
          <?php if($reg['tournament_status'] === 'ongoing'): ?>
          <span class="reg-tag" style="background:rgba(239,68,68,.15);color:#f87171">&#128308; LIVE</span>
          <?php endif; ?>
        </div>
        <div class="reg-info">
          <span><i class="fas fa-users"></i> <?= htmlspecialchars($reg['team_name']) ?></span>
          <span><i class="fas fa-crown"></i> <?= htmlspecialchars($reg['leader_name']) ?></span>
          <?php if ($reg['tournament_start']): ?>
          <span><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($reg['tournament_start'])) ?></span>
          <?php endif; ?>
          <?php if ($reg['discord_link']): ?>
          <span><a href="<?= htmlspecialchars($reg['discord_link']) ?>" target="_blank" style="color:#7289da"><i class="fab fa-discord"></i> Discord</a></span>
          <?php endif; ?>
        </div>
      </div>
      <div class="reg-side">
        <span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
        <div class="reg-id"><?= htmlspecialchars($reg['registration_id']) ?></div>
        <div class="reg-date"><i class="far fa-clock"></i> <?= date('d M Y', strtotime($reg['created_at'])) ?></div>
        <a href="/receipt/<?= htmlspecialchars($reg['registration_id']) ?>"
           class="btn btn-sm btn-outline" style="font-size:11px">
          <i class="fas fa-receipt"></i> Receipt
        </a>
        <?php if ($reg['payment_status'] === 'pending'): ?>
        <a href="/register/<?= $reg['tournament_id'] ?>"
           class="btn btn-sm btn-primary" style="font-size:11px">
          <i class="fas fa-credit-card"></i> Pay Now
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

</div>

<script>
window.addEventListener('scroll',()=>{document.getElementById('navbar').classList.toggle('scrolled',window.scrollY>50);});
function toggleNav(){document.getElementById('navLinks')?.classList.toggle('open');}
</script>
</body>
</html>
