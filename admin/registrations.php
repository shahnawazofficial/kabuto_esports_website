<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$admin = requireAdmin();

// ── Filters ──────────────────────────────────────────────────
$search     = Security::clean($_GET['search'] ?? '');
$tId        = Security::sanitizeInt($_GET['tournament_id'] ?? 0);
$payStatus  = Security::clean($_GET['payment_status'] ?? '');
$page       = max(1, Security::sanitizeInt($_GET['page'] ?? 1));
$perPage    = 25;
$offset     = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(r.registration_id LIKE ? OR r.team_name LIKE ? OR r.leader_name LIKE ? OR r.email LIKE ? OR r.mobile LIKE ?)";
    $s = '%' . $search . '%';
    $params = array_merge($params, [$s,$s,$s,$s,$s]);
}
if ($tId)       { $where[] = 'r.tournament_id = ?'; $params[] = $tId; }
if ($payStatus && in_array($payStatus, ['paid','pending','failed','refunded','free'])) {
    $where[] = 'r.payment_status = ?'; $params[] = $payStatus;
}

$whereStr = 'WHERE ' . implode(' AND ', $where);
$total    = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM registrations r $whereStr", $params)['c']);
$pages    = max(1, (int)ceil($total / $perPage));

$regs = Database::fetchAll(
    "SELECT r.*, t.name as tournament_name, t.mode, t.entry_fee FROM registrations r
     JOIN tournaments t ON r.tournament_id = t.id
     $whereStr ORDER BY r.created_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
);

$tournaments = Database::fetchAll("SELECT id, name FROM tournaments ORDER BY name");

// ── Export CSV ──────────────────────────────────────────────
if (!empty($_GET['export']) && $_GET['export'] === 'csv') {
    $allRegs = Database::fetchAll(
        "SELECT r.registration_id, t.name as tournament_name, r.team_name, r.leader_name, r.leader_uid, r.leader_ign,
                r.mobile, r.whatsapp, r.email, r.player2_name, r.player2_uid, r.player3_name, r.player3_uid,
                r.player4_name, r.player4_uid, r.sub_name, r.sub_uid, r.payment_status, r.transaction_id,
                r.amount_paid, r.created_at FROM registrations r JOIN tournaments t ON r.tournament_id = t.id $whereStr ORDER BY r.created_at DESC",
        $params
    );

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="kabuto-registrations-' . date('Y-m-d') . '.csv"');
    $f = fopen('php://output', 'w');
    fwrite($f, "\xEF\xBB\xBF"); // BOM for Excel UTF-8
    fputcsv($f, ['Reg ID','Tournament','Team','Leader','UID','IGN','Mobile','WhatsApp','Email','P2','P2 UID','P3','P3 UID','P4','P4 UID','Sub','Sub UID','Payment','TxnID','Amount','Date']);
    foreach ($allRegs as $r) {
        fputcsv($f, [$r['registration_id'],$r['tournament_name'],$r['team_name'],$r['leader_name'],$r['leader_uid'],$r['leader_ign'],$r['mobile'],$r['whatsapp'],$r['email'],$r['player2_name'],$r['player2_uid'],$r['player3_name'],$r['player3_uid'],$r['player4_name'],$r['player4_uid'],$r['sub_name'],$r['sub_uid'],$r['payment_status'],$r['transaction_id'],$r['amount_paid'],$r['created_at']]);
    }
    fclose($f);
    exit;
}

startAdminLayout('Registrations', $admin);
echo flashMessage();
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <h2 style="font-family:'Rajdhani',sans-serif;font-size:22px">All Registrations <span style="font-size:14px;color:var(--text-muted)">(<?= number_format($total) ?> total)</span></h2>
  <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'csv'])) ?>" class="btn btn-success btn-sm"><i class="fas fa-file-csv"></i> Export CSV</a>
</div>

<!-- Filters -->
<div class="admin-card" style="margin-bottom:20px">
  <div class="admin-card-body">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
      <div style="flex:1;min-width:200px">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" placeholder="Reg ID, team, email, mobile..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <div>
        <label class="form-label">Tournament</label>
        <select name="tournament_id" class="form-control form-select" style="min-width:180px">
          <option value="">All Tournaments</option>
          <?php foreach ($tournaments as $t): ?><option value="<?= $t['id'] ?>" <?= $tId==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label">Payment Status</label>
        <select name="payment_status" class="form-control form-select">
          <option value="">All</option>
          <?php foreach (['paid','pending','failed','refunded','free'] as $s): ?><option value="<?= $s ?>" <?= $payStatus===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex;gap:8px;align-items:flex-end">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
        <a href="/admin/registrations.php" class="btn btn-outline"><i class="fas fa-times"></i></a>
      </div>
    </form>
  </div>
</div>

<!-- Table -->
<div class="admin-card">
  <div class="table-responsive">
    <table>
      <thead><tr><th>Reg ID</th><th>Tournament</th><th>Team</th><th>Leader</th><th>UID</th><th>Contact</th><th>Payment</th><th>Date</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($regs as $r): ?>
        <tr>
          <td><a href="/receipt/<?= urlencode($r['registration_id']) ?>" style="color:var(--primary);font-family:'Courier New',monospace;font-size:12px" target="_blank"><?= htmlspecialchars($r['registration_id']) ?></a></td>
          <td style="font-size:12px"><?= htmlspecialchars(substr($r['tournament_name'],0,24)) ?><br><span class="badge badge-<?= $r['mode'] ?>" style="margin-top:2px"><?= strtoupper($r['mode']) ?></span></td>
          <td><strong><?= htmlspecialchars($r['team_name']) ?></strong></td>
          <td><?= htmlspecialchars($r['leader_name']) ?><br><span style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($r['leader_ign']) ?></span></td>
          <td style="font-family:'Courier New',monospace;font-size:12px"><?= htmlspecialchars($r['leader_uid']) ?></td>
          <td style="font-size:12px"><?= htmlspecialchars($r['mobile']) ?><br><a href="mailto:<?= htmlspecialchars($r['email']) ?>" style="color:var(--text-muted)"><?= htmlspecialchars(substr($r['email'],0,20)) ?></a></td>
          <td>
            <span class="badge badge-<?= $r['payment_status']==='paid'?'success':($r['payment_status']==='free'?'info':($r['payment_status']==='pending'?'warning':'danger')) ?>"><?= $r['payment_status'] ?></span>
            <?php if ($r['payment_status'] === 'paid'): ?><br><span style="font-size:11px;color:var(--success)">₹<?= number_format((float)$r['amount_paid']) ?></span><?php endif; ?>
          </td>
          <td style="font-size:12px;color:var(--text-muted)"><?= date('d M Y<br>h:i A', strtotime($r['created_at'])) ?></td>
          <td>
            <button onclick="viewDetails(<?= htmlspecialchars(json_encode($r)) ?>)" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($regs)): ?><tr><td colspan="9" style="text-align:center;color:var(--text-dim);padding:40px">No registrations found</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="pagination">
  <?php for ($p=max(1,$page-3); $p<=min($pages,$page+3); $p++): ?>
  <a href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>" class="page-link <?= $p==$page?'active':'' ?>"><?= $p ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<!-- Detail Modal -->
<div id="detailModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;overflow-y:auto;padding:40px 20px">
  <div style="max-width:700px;margin:0 auto;background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:32px;position:relative">
    <button onclick="document.getElementById('detailModal').style.display='none'" style="position:absolute;top:16px;right:16px;background:none;border:none;color:var(--text-muted);font-size:20px;cursor:pointer">✕</button>
    <h3 style="color:var(--primary);margin-bottom:20px;font-family:'Rajdhani',sans-serif" id="modalTitle">Registration Details</h3>
    <div id="modalContent"></div>
  </div>
</div>

<script>
function viewDetails(r){
  const m=document.getElementById('modalContent');
  document.getElementById('modalTitle').textContent='Registration: '+r.registration_id;
  const payBadge={paid:'success',free:'info',pending:'warning',failed:'danger',refunded:'info'};
  m.innerHTML=`
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
      ${row('Team Name',r.team_name)}${row('Registration ID',r.registration_id,'color:var(--primary);font-family:monospace')}
      ${row('Leader Name',r.leader_name)}${row('In-Game Name',r.leader_ign)}
      ${row('Leader UID',r.leader_uid,'font-family:monospace')}${row('Email',r.email)}
      ${row('Mobile',r.mobile)}${row('WhatsApp',r.whatsapp)}
      ${row('Payment','<span class="badge badge-'+(payBadge[r.payment_status]||'secondary')+'">'+r.payment_status+'</span>')}
      ${row('Amount','₹'+(r.amount_paid||'0'))}
      ${row('Transaction ID',r.transaction_id||'N/A','font-size:12px')}${row('Registered',r.created_at)}
    </div>
    ${r.player2_name?`<h4 style="color:var(--primary);margin:12px 0 8px;font-family:'Rajdhani',sans-serif">Squad Players</h4>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
      ${r.player2_name?row('P2 Name',r.player2_name):''}${r.player2_uid?row('P2 UID',r.player2_uid,'font-family:monospace'):''}
      ${r.player3_name?row('P3 Name',r.player3_name):''}${r.player3_uid?row('P3 UID',r.player3_uid,'font-family:monospace'):''}
      ${r.player4_name?row('P4 Name',r.player4_name):''}${r.player4_uid?row('P4 UID',r.player4_uid,'font-family:monospace'):''}
      ${r.sub_name?row('Sub Name',r.sub_name):''}${r.sub_uid?row('Sub UID',r.sub_uid,'font-family:monospace'):''}
    </div>`:''}
  `;
  document.getElementById('detailModal').style.display='block';
}
function row(label,value,style=''){
  return `<div style="background:var(--bg-surface);padding:10px;border-radius:6px"><div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;letter-spacing:.5px">${label}</div><div style="font-size:13px;font-weight:600;margin-top:2px;${style}">${value||'—'}</div></div>`;
}
document.getElementById('detailModal').addEventListener('click',function(e){if(e.target===this)this.style.display='none';});
</script>

<?php endAdminLayout(); ?>
