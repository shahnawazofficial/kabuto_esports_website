<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$admin = requireAdmin();

$page   = max(1, Security::sanitizeInt($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;
$search  = Security::clean($_GET['search'] ?? '');
$status  = Security::clean($_GET['status'] ?? '');

$where  = ['1=1'];
$params = [];
if ($search) {
    $where[] = "(p.transaction_id LIKE ? OR p.payu_txn_id LIKE ? OR r.team_name LIKE ?)";
    $s = '%'.$search.'%';
    $params = array_merge($params, [$s,$s,$s]);
}
if ($status && in_array($status, ['initiated','success','failure','pending','refunded','cancelled'])) {
    $where[] = 'p.status = ?'; $params[] = $status;
}

$whereStr = 'WHERE ' . implode(' AND ', $where);
$total    = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM payments p JOIN registrations r ON p.registration_id=r.id $whereStr", $params)['c']);
$pages    = max(1, (int)ceil($total / $perPage));

$payments = Database::fetchAll(
    "SELECT p.*, r.registration_id, r.team_name, r.email, r.mobile, t.name as tournament_name
     FROM payments p JOIN registrations r ON p.registration_id=r.id JOIN tournaments t ON r.tournament_id=t.id
     $whereStr ORDER BY p.created_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
);

// Summary stats
$pStats = Database::fetchOne(
    "SELECT SUM(CASE WHEN status='success' THEN amount ELSE 0 END) as revenue,
            COUNT(CASE WHEN status='success' THEN 1 END) as success_count,
            COUNT(CASE WHEN status='failure' THEN 1 END) as fail_count,
            COUNT(CASE WHEN status='refunded' THEN 1 END) as refund_count
     FROM payments"
);

startAdminLayout('Payments', $admin);
echo flashMessage();
?>

<div style="margin-bottom:24px">
  <h2 style="font-family:'Rajdhani',sans-serif;font-size:22px">Payment Management</h2>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
  <div class="stat-card"><div class="stat-icon green"><i class="fas fa-rupee-sign"></i></div><div class="stat-info"><div class="num">₹<?= number_format((float)($pStats['revenue']??0)) ?></div><div class="lbl">Total Revenue</div></div></div>
  <div class="stat-card"><div class="stat-icon gold"><i class="fas fa-check-circle"></i></div><div class="stat-info"><div class="num"><?= $pStats['success_count'] ?></div><div class="lbl">Successful</div></div></div>
  <div class="stat-card"><div class="stat-icon" style="background:rgba(239,68,68,.1);color:var(--danger)"><i class="fas fa-times-circle"></i></div><div class="stat-info"><div class="num"><?= $pStats['fail_count'] ?></div><div class="lbl">Failed</div></div></div>
  <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-undo"></i></div><div class="stat-info"><div class="num"><?= $pStats['refund_count'] ?></div><div class="lbl">Refunded</div></div></div>
</div>

<!-- Filters -->
<div class="admin-card" style="margin-bottom:20px">
  <div class="admin-card-body">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
      <div style="flex:1;min-width:200px">
        <label class="form-label">Search TxnID / Team</label>
        <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Transaction ID, team name...">
      </div>
      <div>
        <label class="form-label">Status</label>
        <select name="status" class="form-control form-select">
          <option value="">All</option>
          <?php foreach (['initiated','success','failure','pending','refunded','cancelled'] as $s): ?><option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex;gap:8px;align-items:flex-end">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
        <a href="/admin/payments.php" class="btn btn-outline"><i class="fas fa-times"></i></a>
      </div>
    </form>
  </div>
</div>

<div class="admin-card">
  <div class="table-responsive">
    <table>
      <thead><tr><th>Transaction ID</th><th>Reg ID</th><th>Team</th><th>Tournament</th><th>Amount</th><th>Mode</th><th>PayU TxnID</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
          <td style="font-family:'Courier New',monospace;font-size:11px"><?= htmlspecialchars($p['transaction_id']) ?></td>
          <td><a href="/admin/registrations.php?search=<?= urlencode($p['registration_id']) ?>" style="color:var(--primary);font-size:12px"><?= htmlspecialchars($p['registration_id']) ?></a></td>
          <td><?= htmlspecialchars($p['team_name']) ?></td>
          <td style="font-size:12px"><?= htmlspecialchars(substr($p['tournament_name'],0,24)) ?></td>
          <td style="font-weight:700;color:var(--success)">₹<?= number_format((float)$p['amount'],2) ?></td>
          <td style="font-size:12px"><?= htmlspecialchars($p['payment_mode'] ?? '—') ?></td>
          <td style="font-family:'Courier New',monospace;font-size:11px"><?= htmlspecialchars($p['payu_txn_id'] ?? '—') ?></td>
          <td><span class="badge badge-<?= $p['status']==='success'?'success':($p['status']==='failure'?'danger':($p['status']==='pending'?'warning':($p['status']==='refunded'?'info':'secondary'))) ?>"><?= $p['status'] ?></span></td>
          <td style="font-size:12px;color:var(--text-muted)"><?= date('d M Y, h:i A', strtotime($p['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($payments)): ?><tr><td colspan="9" style="text-align:center;color:var(--text-dim);padding:40px">No payment records</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($pages > 1): ?>
<div class="pagination">
  <?php for ($p=max(1,$page-3); $p<=min($pages,$page+3); $p++): ?>
  <a href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>" class="page-link <?= $p==$page?'active':'' ?>"><?= $p ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<?php endAdminLayout(); ?>
