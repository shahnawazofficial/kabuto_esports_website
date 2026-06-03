<?php
require_once __DIR__ . '/includes/functions.php';

$slug = Security::clean($_GET['slug'] ?? '');
if (!$slug) redirect('/tournaments');

$tournament = getTournamentBySlug($slug);
if (!$tournament) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>Not Found - Kabuto Esports</title></head><body style="background:#0a0a0f;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;font-family:sans-serif;flex-direction:column;gap:16px"><h1 style="color:#f5a623;font-size:64px">404</h1><p>Tournament not found.</p><a href="/tournaments" style="color:#f5a623">← Back to Tournaments</a></body></html>';
    exit;
}

$avail  = max(0, $tournament['total_slots'] - $tournament['registered_slots']);
$isFree = (float)$tournament['entry_fee'] == 0;
$isOpen = $avail > 0 && strtotime($tournament['registration_deadline']) > time() && $tournament['registration_open'];
$bannerUrl = !empty($tournament['banner']) ? '/uploads/banners/' . htmlspecialchars($tournament['banner']) : '/assets/img/default-banner.svg';

// Check if logged-in user is already CONFIRMED registered for this tournament
$alreadyRegistered = false;
if (UserAuth::check()) {
    $existingReg = Database::fetchOne(
        "SELECT registration_id, payment_status FROM registrations 
         WHERE user_id = ? AND tournament_id = ? AND payment_status IN ('paid','free')",
        [$_SESSION['user_id'], $tournament['id']]
    );
    $alreadyRegistered = !empty($existingReg);
}

// Prize distribution formatting
$prizeLines = $tournament['prize_distribution'] ? explode("\n", $tournament['prize_distribution']) : [];
// Schedule formatting
$scheduleLines = $tournament['schedule'] ? explode("\n", $tournament['schedule']) : [];
// Rules formatting
$rulesLines = $tournament['rules'] ? explode("\n", $tournament['rules']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= Security::sanitize($tournament['name']) ?> – Kabuto Esports</title>
<meta name="description" content="<?= Security::sanitize(substr($tournament['description'] ?? '', 0, 155)) ?>">
<meta property="og:title" content="<?= Security::sanitize($tournament['name']) ?> – Kabuto Esports">
<meta property="og:image" content="<?= APP_URL . $bannerUrl ?>">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.tournament-detail{display:grid;grid-template-columns:1fr 340px;gap:32px;align-items:start}
.detail-banner{border-radius:var(--radius-lg);overflow:hidden;margin-bottom:28px;aspect-ratio:16/6;background:var(--bg-surface)}
.detail-banner img{width:100%;height:100%;object-fit:cover}
.info-sidebar{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:28px;position:sticky;top:88px}
.info-row{display:flex;justify-content:space-between;align-items:center;padding:14px 0;border-bottom:1px solid var(--border)}
.info-row:last-child{border-bottom:none}
.info-label{font-size:13px;color:var(--text-muted)}
.info-value{font-size:15px;font-weight:700;color:var(--text-primary);text-align:right}
.info-value.highlight{color:var(--primary);font-family:'Rajdhani',sans-serif;font-size:18px}
.tab-nav{display:flex;gap:4px;border-bottom:1px solid var(--border);margin-bottom:24px}
.tab-btn{padding:12px 20px;background:none;border:none;color:var(--text-muted);font-size:15px;font-weight:600;cursor:pointer;border-bottom:2px solid transparent;transition:var(--transition);font-family:'Rajdhani',sans-serif}
.tab-btn:hover{color:var(--text-primary)}
.tab-btn.active{color:var(--primary);border-bottom-color:var(--primary)}
.tab-content{display:none}
.tab-content.active{display:block}
.rules-list li{padding:8px 0 8px 20px;position:relative;color:var(--text-muted);font-size:15px;line-height:1.6;border-bottom:1px solid var(--border)}
.rules-list li::before{content:'';position:absolute;left:0;top:17px;width:6px;height:6px;background:var(--primary);border-radius:50%}
.prize-row{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-radius:var(--radius-sm);margin-bottom:8px;background:var(--bg-surface)}
.prize-row.top{background:linear-gradient(135deg,rgba(245,166,35,.15),rgba(245,166,35,.05));border:1px solid rgba(245,166,35,.2)}
.prize-rank{font-size:20px}
.prize-money{font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;color:var(--primary)}
.schedule-item{display:flex;gap:16px;padding:14px 0;border-bottom:1px solid var(--border)}
.schedule-icon{color:var(--primary);width:20px;text-align:center;margin-top:2px}
.register-cta{background:linear-gradient(135deg,rgba(245,166,35,.08),rgba(124,58,237,.05));border:1px solid var(--border-bright);border-radius:var(--radius-lg);padding:24px;text-align:center;margin-top:20px}
@media(max-width:900px){.tournament-detail{grid-template-columns:1fr}.info-sidebar{position:static}}
</style>
</head>
<body>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div style="padding-top:68px"></div>

<section class="section-sm">
  <div class="container">
    <div class="breadcrumb" style="margin-bottom:20px">
      <a href="/">Home</a> <i class="fas fa-chevron-right" style="font-size:10px"></i>
      <a href="/tournaments">Tournaments</a> <i class="fas fa-chevron-right" style="font-size:10px"></i>
      <span><?= Security::sanitize($tournament['name']) ?></span>
    </div>

    <div class="tournament-detail">
      <!-- Left Content -->
      <div>
        <div class="detail-banner">
          <img src="<?= $bannerUrl ?>" alt="<?= Security::sanitize($tournament['name']) ?>">
        </div>

        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px">
          <div>
            <div style="display:flex;gap:8px;margin-bottom:10px">
              <span class="badge badge-<?= $t['mode'] ?? $tournament['mode'] ?>"><?= strtoupper($tournament['mode']) ?></span>
              <?php if ($isFree): ?><span class="badge badge-success">FREE ENTRY</span><?php endif; ?>
              <span class="badge badge-<?= $tournament['status'] === 'active' ? 'success' : 'warning' ?>"><?= strtoupper($tournament['status']) ?></span>
            </div>
            <h1 style="font-size:clamp(22px,4vw,36px)"><?= Security::sanitize($tournament['name']) ?></h1>
          </div>
        </div>

        <!-- Tabs -->
        <div class="tab-nav">
          <button class="tab-btn active" onclick="showTab('overview',this)">Overview</button>
          <button class="tab-btn" onclick="showTab('rules',this)">Rules</button>
          <button class="tab-btn" onclick="showTab('prizes',this)">Prizes</button>
          <button class="tab-btn" onclick="showTab('schedule',this)">Schedule</button>
        </div>

        <div id="tab-overview" class="tab-content active">
          <p style="color:var(--text-muted);line-height:1.8;font-size:16px"><?= nl2br(Security::sanitize($tournament['description'] ?? '')) ?></p>
          <?php if ($tournament['discord_link']): ?>
          <a href="<?= Security::sanitize($tournament['discord_link']) ?>" target="_blank" rel="noopener" class="btn btn-outline" style="margin-top:20px">
            <i class="fab fa-discord"></i> Join Discord for Updates
          </a>
          <?php endif; ?>
        </div>

        <div id="tab-rules" class="tab-content">
          <?php if (empty($rulesLines)): ?>
          <p style="color:var(--text-muted)">Rules will be announced soon.</p>
          <?php else: ?>
          <ul class="rules-list">
            <?php foreach ($rulesLines as $rule): if (trim($rule)): ?>
            <li><?= Security::sanitize($rule) ?></li>
            <?php endif; endforeach; ?>
          </ul>
          <?php endif; ?>
        </div>

        <div id="tab-prizes" class="tab-content">
          <?php if (empty($prizeLines)): ?>
          <p style="color:var(--text-muted)">Prize distribution will be announced soon.</p>
          <?php else: ?>
          <?php foreach ($prizeLines as $i => $line): if (trim($line)): ?>
          <div class="prize-row <?= $i === 0 ? 'top' : '' ?>">
            <span class="prize-rank"><?= $i===0?'🥇':($i===1?'🥈':($i===2?'🥉':'🏅')) ?></span>
            <span style="flex:1;padding:0 12px;color:var(--text-primary);font-size:15px"><?= Security::sanitize($line) ?></span>
          </div>
          <?php endif; endforeach; ?>
          <?php endif; ?>
        </div>

        <div id="tab-schedule" class="tab-content">
          <?php if (empty($scheduleLines)): ?>
          <p style="color:var(--text-muted)">Schedule will be announced soon.</p>
          <?php else: ?>
          <?php foreach ($scheduleLines as $line): if (trim($line)): ?>
          <div class="schedule-item">
            <span class="schedule-icon"><i class="fas fa-calendar-alt"></i></span>
            <span style="color:var(--text-muted);font-size:15px"><?= Security::sanitize($line) ?></span>
          </div>
          <?php endif; endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="info-sidebar">
        <div style="font-family:'Rajdhani',sans-serif;font-size:18px;font-weight:700;margin-bottom:16px;color:var(--primary)">Tournament Info</div>

        <div class="info-row"><span class="info-label">Entry Fee</span><span class="info-value highlight"><?= $isFree ? 'FREE' : formatCurrency((float)$tournament['entry_fee']) ?></span></div>
        <div class="info-row"><span class="info-label">Prize Pool</span><span class="info-value highlight"><?= formatCurrency((float)$tournament['prize_pool']) ?></span></div>
        <div class="info-row"><span class="info-label">Game Mode</span><span class="info-value"><?= strtoupper($tournament['mode']) ?></span></div>
        <div class="info-row"><span class="info-label">Total Slots</span><span class="info-value"><?= $tournament['total_slots'] ?></span></div>
        <div class="info-row"><span class="info-label">Slots Left</span>
          <span class="info-value <?= $avail <= 10 ? 'text-danger' : '' ?>" style="<?= $avail <= 10 ? 'color:var(--danger)' : '' ?>"><?= $avail ?></span>
        </div>
        <div class="info-row"><span class="info-label">Deadline</span><span class="info-value" style="font-size:13px"><?= formatDateTime($tournament['registration_deadline']) ?></span></div>
        <?php if ($tournament['tournament_start']): ?>
        <div class="info-row"><span class="info-label">Start Date</span><span class="info-value" style="font-size:13px"><?= formatDateTime($tournament['tournament_start']) ?></span></div>
        <?php endif; ?>
        <?php if ($tournament['contact_info']): ?>
        <div class="info-row"><span class="info-label">Contact</span><span class="info-value" style="font-size:13px"><?= Security::sanitize($tournament['contact_info']) ?></span></div>
        <?php endif; ?>

        <!-- Slots progress -->
        <div style="margin:16px 0">
          <?php $pct = $tournament['total_slots'] > 0 ? ($tournament['registered_slots'] / $tournament['total_slots']) * 100 : 0; ?>
          <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-muted);margin-bottom:6px">
            <span><?= $tournament['registered_slots'] ?> registered</span>
            <span><?= round($pct) ?>% full</span>
          </div>
          <div class="slots-bar"><div class="slots-fill <?= $pct>=90?'full':($pct>=70?'warn':'') ?>" style="width:<?= min(100,$pct) ?>%"></div></div>
        </div>

        <?php if ($alreadyRegistered): ?>
        <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:var(--radius-md);padding:20px;text-align:center;margin-top:8px">
          <div style="font-size:28px;margin-bottom:8px">✅</div>
          <div style="font-family:'Rajdhani',sans-serif;font-size:18px;font-weight:700;color:#34d399;margin-bottom:4px">You're Registered!</div>
          <div style="font-size:13px;color:var(--text-muted);margin-bottom:16px">You have already registered for this tournament.</div>
          <a href="/dashboard" class="btn btn-sm" style="background:rgba(16,185,129,.2);color:#34d399;border:1px solid rgba(16,185,129,.4);width:100%;justify-content:center">
            <i class="fas fa-tachometer-alt"></i> View My Dashboard
          </a>
        </div>
        <?php elseif ($isOpen): ?>
        <a href="/register/<?= $tournament['id'] ?>" class="btn btn-primary btn-block btn-lg" style="margin-top:8px">
          <i class="fas fa-gamepad"></i> Register Now
        </a>
        <?php else: ?>
        <div class="btn btn-block btn-lg" style="background:var(--bg-surface);color:var(--text-dim);cursor:not-allowed;text-align:center;margin-top:8px">
          <i class="fas fa-lock"></i> Registration Closed
        </div>
        <?php endif; ?>

        <div class="register-cta" style="margin-top:16px">
          <i class="fas fa-shield-alt" style="color:var(--primary);margin-bottom:8px;display:block"></i>
          <p style="font-size:13px;color:var(--text-muted)">Secure payment via <strong style="color:var(--text-primary)">PayU India</strong>. Your data is protected.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="footer" style="padding:24px 0">
  <div class="container">
    <div class="footer-bottom" style="border:none;padding:0">
      <p>© 2026 Kabuto Esports · kabutoesports.com</p>
      <a href="/tournaments" style="color:var(--text-muted);font-size:14px">← All Tournaments</a>
    </div>
  </div>
</footer>

<script>
function showTab(id,btn){
  document.querySelectorAll('.tab-content').forEach(el=>el.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(el=>el.classList.remove('active'));
  document.getElementById('tab-'+id).classList.add('active');
  btn.classList.add('active');
}
window.addEventListener('scroll',()=>document.getElementById('navbar').classList.toggle('scrolled',window.scrollY>10));
</script>
</body>
</html>
