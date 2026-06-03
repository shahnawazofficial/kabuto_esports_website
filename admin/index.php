<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$admin = requireAdmin();

// Stats
$stats = [
    'tournaments'   => Database::fetchOne("SELECT COUNT(*) as c FROM tournaments WHERE status != 'cancelled'")['c'],
    'total_regs'    => Database::fetchOne("SELECT COUNT(*) as c FROM registrations")['c'],
    'paid_regs'     => Database::fetchOne("SELECT COUNT(*) as c FROM registrations WHERE payment_status = 'paid'")['c'],
    'free_regs'     => Database::fetchOne("SELECT COUNT(*) as c FROM registrations WHERE payment_status = 'free'")['c'],
    'revenue'       => Database::fetchOne("SELECT COALESCE(SUM(amount_paid),0) as r FROM registrations WHERE payment_status = 'paid'")['r'],
    'active_t'      => Database::fetchOne("SELECT COUNT(*) as c FROM tournaments WHERE status = 'active'")['c'],
];

$recentRegs = Database::fetchAll(
    "SELECT r.registration_id, r.team_name, r.email, r.payment_status, r.created_at, t.name as tournament_name
     FROM registrations r JOIN tournaments t ON r.tournament_id = t.id
     ORDER BY r.created_at DESC LIMIT 10"
);

$upcomingT = Database::fetchAll(
    "SELECT name, entry_fee, total_slots, registered_slots, registration_deadline, status
     FROM tournaments WHERE status IN ('upcoming','active') ORDER BY registration_deadline ASC LIMIT 5"
);

startAdminLayout('Dashboard', $admin);
echo flashMessage();
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon gold"><i class="fas fa-trophy"></i></div>
    <div class="stat-info"><div class="num"><?= $stats['tournaments'] ?></div><div class="lbl">Total Tournaments</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
    <div class="stat-info"><div class="num"><?= $stats['total_regs'] ?></div><div class="lbl">Total Registrations</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
    <div class="stat-info"><div class="num"><?= $stats['paid_regs'] ?></div><div class="lbl">Paid Registrations</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple"><i class="fas fa-rupee-sign"></i></div>
    <div class="stat-info"><div class="num">₹<?= number_format((float)$stats['revenue'],0) ?></div><div class="lbl">Total Revenue</div></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px">
  <!-- Recent Registrations -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title"><i class="fas fa-users" style="color:var(--primary)"></i> Recent Registrations</div>
      <a href="/admin/registrations.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="table-responsive">
      <table>
        <thead><tr><th>ID</th><th>Team</th><th>Tournament</th><th>Payment</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($recentRegs as $r): ?>
          <tr>
            <td><a href="/admin/registrations.php?search=<?= urlencode($r['registration_id']) ?>" style="color:var(--primary);font-size:12px;font-family:'Courier New',monospace"><?= htmlspecialchars($r['registration_id']) ?></a></td>
            <td><?= htmlspecialchars($r['team_name']) ?></td>
            <td style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars(substr($r['tournament_name'],0,20)) ?>...</td>
            <td><span class="badge badge-<?= $r['payment_status']==='paid'?'success':($r['payment_status']==='free'?'info':($r['payment_status']==='pending'?'warning':'danger')) ?>"><?= $r['payment_status'] ?></span></td>
            <td style="font-size:12px;color:var(--text-muted)"><?= date('d M, h:i A', strtotime($r['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recentRegs)): ?><tr><td colspan="5" style="text-align:center;color:var(--text-dim)">No registrations yet</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Upcoming Tournaments -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title"><i class="fas fa-trophy" style="color:var(--primary)"></i> Active Tournaments</div>
      <a href="/admin/tournaments.php" class="btn btn-sm btn-outline">Manage</a>
    </div>
    <div style="padding:16px">
      <?php foreach ($upcomingT as $t): ?>
      <div style="padding:12px;background:var(--bg-surface);border-radius:8px;margin-bottom:8px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px">
          <strong style="font-size:14px"><?= htmlspecialchars($t['name']) ?></strong>
          <span class="badge badge-<?= $t['status']==='active'?'success':'warning' ?>"><?= $t['status'] ?></span>
        </div>
        <div style="display:flex;gap:16px;font-size:12px;color:var(--text-muted)">
          <span>₹<?= number_format((float)$t['entry_fee'],0) ?> entry</span>
          <span><?= $t['registered_slots'] ?>/<?= $t['total_slots'] ?> slots</span>
          <span>Deadline: <?= date('d M', strtotime($t['registration_deadline'])) ?></span>
        </div>
        <?php $pct = $t['total_slots'] > 0 ? ($t['registered_slots']/$t['total_slots'])*100 : 0; ?>
        <div style="background:var(--bg-dark);border-radius:100px;height:4px;margin-top:8px">
          <div style="height:4px;background:var(--primary);border-radius:100px;width:<?= min(100,$pct) ?>%"></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($upcomingT)): ?><p style="color:var(--text-dim);text-align:center;padding:20px 0">No active tournaments</p><?php endif; ?>
    </div>
  </div>
</div>

<!-- Quick Stats Row -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
  <div class="admin-card">
    <div class="admin-card-body" style="text-align:center">
      <div style="font-size:36px;font-weight:700;color:var(--success)"><?= $stats['free_regs'] ?></div>
      <div style="color:var(--text-muted);font-size:13px">Free Registrations</div>
    </div>
  </div>
  <div class="admin-card">
    <div class="admin-card-body" style="text-align:center">
      <div style="font-size:36px;font-weight:700;color:var(--primary)"><?= $stats['active_t'] ?></div>
      <div style="color:var(--text-muted);font-size:13px">Active Tournaments</div>
    </div>
  </div>
  <div class="admin-card">
    <div class="admin-card-body" style="text-align:center">
      <div style="font-size:36px;font-weight:700;color:var(--info)"><?= $stats['paid_regs'] + $stats['free_regs'] ?></div>
      <div style="color:var(--text-muted);font-size:13px">Confirmed Entries</div>
    </div>
  </div>
</div>

<?php endAdminLayout(); ?>
