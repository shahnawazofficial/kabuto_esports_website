<?php
require_once __DIR__ . '/includes/functions.php';

// Filters
$mode   = Security::clean($_GET['mode']   ?? '');
$type   = Security::clean($_GET['type']   ?? ''); // free|paid
$search = Security::clean($_GET['search'] ?? '');
$page   = max(1, Security::sanitizeInt($_GET['page'] ?? 1));
$perPage = TOURNAMENTS_PER_PAGE;
$offset  = ($page - 1) * $perPage;

// Build query
$where  = ["t.status != 'cancelled'"];
$params = [];

if ($mode && in_array($mode, ['solo','duo','squad'])) {
    $where[] = 't.mode = ?'; $params[] = $mode;
}
if ($type === 'free')  { $where[] = 't.entry_fee = 0'; }
if ($type === 'paid')  { $where[] = 't.entry_fee > 0'; }
if ($search)           { $where[] = 't.name LIKE ?'; $params[] = '%' . $search . '%'; }

$whereStr    = 'WHERE ' . implode(' AND ', $where);
$totalResult = Database::fetchOne("SELECT COUNT(*) as cnt FROM tournaments t $whereStr", $params);
$total       = (int)($totalResult['cnt'] ?? 0);
$totalPages  = (int)ceil($total / $perPage);

$paramsPage = array_merge($params, [$perPage, $offset]);
$tournaments = Database::fetchAll(
    "SELECT * FROM tournaments t $whereStr ORDER BY registration_deadline ASC LIMIT ? OFFSET ?",
    $paramsPage
);

// Get IDs of tournaments this user has CONFIRMED (paid/free) registered for
$myRegisteredIds = [];
if (UserAuth::check()) {
    $myRegs = Database::fetchAll(
        "SELECT tournament_id FROM registrations WHERE user_id = ? AND payment_status IN ('paid','free')",
        [$_SESSION['user_id']]
    );
    $myRegisteredIds = array_column($myRegs, 'tournament_id');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Tournaments – Kabuto Esports</title>
<meta name="description" content="Browse all active BGMI tournaments on Kabuto Esports. Filter by game mode, entry fee, and find your perfect competition.">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.filter-panel{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;margin-bottom:32px}
.filter-grid{display:grid;grid-template-columns:1fr auto auto auto;gap:12px;align-items:end}
.chip-group{display:flex;gap:8px;flex-wrap:wrap}
.chip{padding:7px 16px;border-radius:100px;border:1px solid var(--border);color:var(--text-muted);font-size:13px;cursor:pointer;transition:var(--transition);background:var(--bg-surface);text-decoration:none;display:inline-block}
.chip:hover,.chip.active{background:var(--primary-glow);border-color:var(--primary);color:var(--primary)}
.results-info{color:var(--text-muted);font-size:14px;margin-bottom:20px}
.empty-state{text-align:center;padding:80px 20px}
.empty-state i{font-size:64px;color:var(--text-dim);margin-bottom:20px;display:block}
@media(max-width:768px){.filter-grid{grid-template-columns:1fr}}
</style>
</head>
<body>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="page-header">
  <div class="container">
    <div class="breadcrumb"><a href="/">Home</a> <i class="fas fa-chevron-right" style="font-size:10px"></i> <span>Tournaments</span></div>
    <h1>🏆 All Tournaments</h1>
    <p style="color:var(--text-muted);margin-top:8px">Find and join the perfect tournament for your skill level</p>
  </div>
</div>

<section class="section-sm">
  <div class="container">

    <!-- Filter Panel -->
    <div class="filter-panel">
      <form method="GET" action="/tournaments">
        <div class="filter-grid">
          <div class="form-group" style="margin:0">
            <label class="form-label">Search</label>
            <input type="text" name="search" class="form-control" placeholder="Search tournament..." value="<?= Security::sanitize($search) ?>">
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Mode</label>
            <select name="mode" class="form-control form-select">
              <option value="">All Modes</option>
              <option value="solo"  <?= $mode==='solo'  ? 'selected':'' ?>>Solo</option>
              <option value="duo"   <?= $mode==='duo'   ? 'selected':'' ?>>Duo</option>
              <option value="squad" <?= $mode==='squad' ? 'selected':'' ?>>Squad</option>
            </select>
          </div>
          <div class="form-group" style="margin:0">
            <label class="form-label">Entry Fee</label>
            <select name="type" class="form-control form-select">
              <option value="">All</option>
              <option value="free" <?= $type==='free' ? 'selected':'' ?>>Free</option>
              <option value="paid" <?= $type==='paid' ? 'selected':'' ?>>Paid</option>
            </select>
          </div>
          <div style="display:flex;gap:8px;align-items:flex-end;padding-bottom:0">
            <button type="submit" class="btn btn-primary" style="white-space:nowrap">
              <i class="fas fa-search"></i> Search
            </button>
            <a href="/tournaments" class="btn btn-outline" title="Clear filters"><i class="fas fa-times"></i></a>
          </div>
        </div>
      </form>
      <!-- Quick Chips -->
      <div class="chip-group" style="margin-top:16px">
        <a href="/tournaments" class="chip <?= !$mode && !$type ? 'active':'' ?>">All</a>
        <a href="/tournaments?type=free" class="chip <?= $type==='free' ? 'active':'' ?>">🆓 Free</a>
        <a href="/tournaments?mode=solo" class="chip <?= $mode==='solo' ? 'active':'' ?>">🎯 Solo</a>
        <a href="/tournaments?mode=duo" class="chip <?= $mode==='duo' ? 'active':'' ?>">👥 Duo</a>
        <a href="/tournaments?mode=squad" class="chip <?= $mode==='squad' ? 'active':'' ?>">⚔️ Squad</a>
      </div>
    </div>

    <p class="results-info">Showing <strong><?= count($tournaments) ?></strong> of <strong><?= $total ?></strong> tournaments</p>

    <?php if (empty($tournaments)): ?>
    <div class="empty-state">
      <i class="fas fa-trophy"></i>
      <h3>No tournaments found</h3>
      <p style="color:var(--text-muted);margin-top:8px">Try adjusting your filters or check back later!</p>
      <a href="/tournaments" class="btn btn-primary" style="margin-top:20px">Clear Filters</a>
    </div>
    <?php else: ?>
    <div class="grid-3">
      <?php foreach ($tournaments as $t):
        $avail    = max(0, $t['total_slots'] - $t['registered_slots']);
        $pct      = $t['total_slots'] > 0 ? ($t['registered_slots'] / $t['total_slots']) * 100 : 0;
        $fillClass = $pct >= 90 ? 'full' : ($pct >= 70 ? 'warn' : '');
        $isFree   = (float)$t['entry_fee'] == 0;
        $isOpen   = $avail > 0 && strtotime($t['registration_deadline']) > time() && $t['registration_open'];
        $bannerUrl = !empty($t['banner']) ? '/uploads/banners/' . htmlspecialchars($t['banner']) : '/assets/img/default-banner.svg';
        $alreadyRegistered = in_array($t['id'], $myRegisteredIds);
      ?>
      <div class="card tournament-card">
        <div class="card-banner">
          <img src="<?= $bannerUrl ?>" alt="<?= Security::sanitize($t['name']) ?>" loading="lazy">
          <div class="badge-overlay">
            <?php if ($alreadyRegistered): ?><span class="badge badge-success" style="background:rgba(16,185,129,.9)">✓ Registered</span><?php endif; ?>
            <?php if ($isFree): ?><span class="badge badge-success">FREE</span><?php endif; ?>
            <span class="badge badge-<?= $t['mode'] ?>"><?= strtoupper($t['mode']) ?></span>
            <?php if (!$isOpen): ?><span class="badge badge-danger">CLOSED</span><?php endif; ?>
          </div>
        </div>
        <div class="card-body" style="flex:1">
          <h3><?= Security::sanitize($t['name']) ?></h3>
          <p style="color:var(--text-muted);font-size:13px;margin-bottom:12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
            <?= Security::sanitize($t['description'] ?? '') ?>
          </p>
          <div class="tc-stats">
            <div class="tc-stat"><div class="label">Entry Fee</div><div class="value"><?= $isFree ? 'FREE' : formatCurrency((float)$t['entry_fee']) ?></div></div>
            <div class="tc-stat"><div class="label">Prize Pool</div><div class="value"><?= formatCurrency((float)$t['prize_pool']) ?></div></div>
          </div>
          <div class="slots-bar"><div class="slots-fill <?= $fillClass ?>" style="width:<?= min(100,$pct) ?>%"></div></div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px">
            <span class="slots-text"><?= $avail ?> / <?= $t['total_slots'] ?> slots left</span>
            <span class="deadline-text"><i class="far fa-clock"></i> <?= date('d M', strtotime($t['registration_deadline'])) ?></span>
          </div>
        </div>
        <div class="card-footer" style="display:flex;gap:10px">
          <a href="/tournament/<?= Security::sanitize($t['slug']) ?>" class="btn btn-outline btn-sm" style="flex:1">View Details</a>
          <?php if ($alreadyRegistered): ?>
          <a href="/dashboard" class="btn btn-sm" style="flex:1;background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.3)">✓ Registered</a>
          <?php elseif ($isOpen): ?>
          <a href="/register/<?= $t['id'] ?>" class="btn btn-primary btn-sm" style="flex:1">Register</a>
          <?php else: ?>
          <span class="btn btn-sm" style="flex:1;background:var(--bg-surface);color:var(--text-dim);cursor:not-allowed">Closed</span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination" style="margin-top:40px">
      <?php if ($page > 1): ?>
      <a href="?page=<?= $page-1 ?>&mode=<?= urlencode($mode) ?>&type=<?= urlencode($type) ?>&search=<?= urlencode($search) ?>" class="page-link"><i class="fas fa-chevron-left"></i></a>
      <?php endif; ?>
      <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
      <a href="?page=<?= $p ?>&mode=<?= urlencode($mode) ?>&type=<?= urlencode($type) ?>&search=<?= urlencode($search) ?>" class="page-link <?= $p==$page?'active':'' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
      <a href="?page=<?= $page+1 ?>&mode=<?= urlencode($mode) ?>&type=<?= urlencode($type) ?>&search=<?= urlencode($search) ?>" class="page-link"><i class="fas fa-chevron-right"></i></a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<footer class="footer" style="padding:30px 0">
  <div class="container">
    <div class="footer-bottom" style="border:none;padding:0">
      <p>© 2026 Kabuto Esports · <a href="/">kabutoesports.com</a></p>
      <div class="social-links">
        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
        <a href="#" class="social-link"><i class="fab fa-discord"></i></a>
        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
      </div>
    </div>
  </div>
</footer>

<script>
window.addEventListener('scroll',()=>document.getElementById('navbar').classList.toggle('scrolled',window.scrollY>50));
</script>
</body>
</html>
