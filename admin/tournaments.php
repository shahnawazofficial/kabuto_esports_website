<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$admin = requireAdmin();

// ── Handle Actions ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();

    $action = Security::clean($_POST['action'] ?? '');

    if ($action === 'create' || $action === 'edit') {
        $tId   = Security::sanitizeInt($_POST['tournament_id'] ?? 0);
        $name  = Security::clean($_POST['name'] ?? '');
        $slug  = slugify($name);
        $desc  = Security::clean($_POST['description'] ?? '');
        $rules = Security::clean($_POST['rules'] ?? '');
        $sched = Security::clean($_POST['schedule'] ?? '');
        $pDist = Security::clean($_POST['prize_distribution'] ?? '');
        $game  = Security::clean($_POST['game'] ?? 'BGMI');
        $mode  = in_array($_POST['mode']??'', ['solo','duo','squad']) ? $_POST['mode'] : 'squad';
        $fee   = max(0, (float)($_POST['entry_fee'] ?? 0));
        $prize = max(0, (float)($_POST['prize_pool'] ?? 0));
        $slots = max(1, Security::sanitizeInt($_POST['total_slots'] ?? 100));
        $deadline = Security::clean($_POST['registration_deadline'] ?? '');
        $start    = Security::clean($_POST['tournament_start'] ?? '');
        $status   = in_array($_POST['status']??'', ['upcoming','active','ongoing','completed','cancelled']) ? $_POST['status'] : 'upcoming';
        $contact  = Security::clean($_POST['contact_info'] ?? '');
        $discord  = Security::clean($_POST['discord_link'] ?? '');
        $regOpen  = isset($_POST['registration_open']) ? 1 : 0;

        // Handle banner upload
        $banner = Security::clean($_POST['existing_banner'] ?? '');
        if (!empty($_FILES['banner']['name'])) {
            $ext = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp']) && $_FILES['banner']['size'] <= UPLOAD_MAX_SIZE) {
                $filename = slugify($name) . '-' . time() . '.' . $ext;
                $uploadPath = BANNER_UPLOAD_PATH;
                if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
                if (move_uploaded_file($_FILES['banner']['tmp_name'], $uploadPath . $filename)) {
                    $banner = $filename;
                }
            }
        }

        if (empty($name) || empty($deadline)) {
            setFlash('danger', 'Tournament name and deadline are required.');
        } else {
            if ($action === 'create') {
                // Ensure unique slug
                $existing = Database::fetchOne("SELECT id FROM tournaments WHERE slug = ?", [$slug]);
                if ($existing) $slug .= '-' . time();
                Database::query(
                    "INSERT INTO tournaments (name,slug,description,rules,schedule,prize_distribution,game,mode,entry_fee,prize_pool,total_slots,registration_deadline,tournament_start,status,registration_open,contact_info,discord_link,banner,created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    [$name,$slug,$desc,$rules,$sched,$pDist,$game,$mode,$fee,$prize,$slots,$deadline,$start?:null,$status,$regOpen,$contact,$discord,$banner,$admin['id']]
                );
                setFlash('success', 'Tournament "' . $name . '" created successfully!');
            } else {
                Database::query(
                    "UPDATE tournaments SET name=?,description=?,rules=?,schedule=?,prize_distribution=?,game=?,mode=?,entry_fee=?,prize_pool=?,total_slots=?,registration_deadline=?,tournament_start=?,status=?,registration_open=?,contact_info=?,discord_link=?,banner=? WHERE id=?",
                    [$name,$desc,$rules,$sched,$pDist,$game,$mode,$fee,$prize,$slots,$deadline,$start?:null,$status,$regOpen,$contact,$discord,$banner,$tId]
                );
                setFlash('success', 'Tournament updated successfully!');
            }
        }
        header('Location: /admin/tournaments.php');
        exit;
    }

    if ($action === 'delete') {
        $tId = Security::sanitizeInt($_POST['tournament_id'] ?? 0);
        $hasRegs = Database::fetchOne("SELECT COUNT(*) as c FROM registrations WHERE tournament_id=?", [$tId])['c'];
        if ($hasRegs > 0) {
            setFlash('danger', 'Cannot delete tournament with existing registrations. Cancel it instead.');
        } else {
            Database::query("DELETE FROM tournaments WHERE id=?", [$tId]);
            setFlash('success', 'Tournament deleted.');
        }
        header('Location: /admin/tournaments.php');
        exit;
    }

    if ($action === 'toggle_reg') {
        $tId = Security::sanitizeInt($_POST['tournament_id'] ?? 0);
        Database::query("UPDATE tournaments SET registration_open = NOT registration_open WHERE id=?", [$tId]);
        setFlash('success', 'Registration status updated.');
        header('Location: /admin/tournaments.php');
        exit;
    }
}

$editTournament = null;
if (!empty($_GET['edit'])) {
    $editTournament = Database::fetchOne("SELECT * FROM tournaments WHERE id=?", [Security::sanitizeInt($_GET['edit'])]);
}

$tournaments = Database::fetchAll(
    "SELECT t.*, (SELECT COUNT(*) FROM registrations r WHERE r.tournament_id=t.id AND r.payment_status IN ('paid','free')) as confirmed_count
     FROM tournaments t ORDER BY created_at DESC"
);

startAdminLayout('Tournament Management', $admin);
echo flashMessage();
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
  <h2 style="font-family:'Rajdhani',sans-serif;font-size:22px">All Tournaments</h2>
  <button class="btn btn-primary" onclick="showForm()"><i class="fas fa-plus"></i> New Tournament</button>
</div>

<!-- Create/Edit Form (initially hidden) -->
<div class="admin-card" id="tournamentForm" style="<?= $editTournament ? '' : 'display:none' ?>;margin-bottom:24px">
  <div class="admin-card-header">
    <div class="admin-card-title"><i class="fas fa-<?= $editTournament ? 'edit' : 'plus' ?>" style="color:var(--primary)"></i> <?= $editTournament ? 'Edit Tournament' : 'Create Tournament' ?></div>
    <button onclick="hideForm()" class="btn btn-sm btn-outline"><i class="fas fa-times"></i></button>
  </div>
  <div class="admin-card-body">
    <form method="POST" enctype="multipart/form-data">
      <?= Security::csrfField() ?>
      <input type="hidden" name="action" value="<?= $editTournament ? 'edit' : 'create' ?>">
      <?php if ($editTournament): ?><input type="hidden" name="tournament_id" value="<?= $editTournament['id'] ?>"><?php endif; ?>
      <?php if ($editTournament): ?><input type="hidden" name="existing_banner" value="<?= htmlspecialchars($editTournament['banner'] ?? '') ?>"><?php endif; ?>

      <div class="form-row">
        <div class="form-group"><label class="form-label">Tournament Name *</label><input type="text" class="form-control" name="name" value="<?= htmlspecialchars($editTournament['name'] ?? '') ?>" required></div>
        <div class="form-group"><label class="form-label">Game</label><input type="text" class="form-control" name="game" value="<?= htmlspecialchars($editTournament['game'] ?? 'BGMI') ?>"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Mode *</label>
          <select name="mode" class="form-control form-select">
            <?php foreach (['solo','duo','squad'] as $m): ?><option value="<?= $m ?>" <?= ($editTournament['mode']??'squad')===$m?'selected':'' ?>><?= ucfirst($m) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Status</label>
          <select name="status" class="form-control form-select">
            <?php foreach (['upcoming','active','ongoing','completed','cancelled'] as $s): ?><option value="<?= $s ?>" <?= ($editTournament['status']??'upcoming')===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Entry Fee (₹)</label><input type="number" class="form-control" name="entry_fee" value="<?= $editTournament['entry_fee'] ?? 0 ?>" min="0" step="0.01"></div>
        <div class="form-group"><label class="form-label">Prize Pool (₹)</label><input type="number" class="form-control" name="prize_pool" value="<?= $editTournament['prize_pool'] ?? 0 ?>" min="0" step="0.01"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Total Slots</label><input type="number" class="form-control" name="total_slots" value="<?= $editTournament['total_slots'] ?? 100 ?>" min="1"></div>
        <div class="form-group"><label class="form-label">Registration Deadline *</label><input type="datetime-local" class="form-control" name="registration_deadline" value="<?= $editTournament ? date('Y-m-d\TH:i', strtotime($editTournament['registration_deadline'])) : '' ?>" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Tournament Start</label><input type="datetime-local" class="form-control" name="tournament_start" value="<?= $editTournament && $editTournament['tournament_start'] ? date('Y-m-d\TH:i', strtotime($editTournament['tournament_start'])) : '' ?>"></div>
        <div class="form-group"><label class="form-label">Tournament Banner (max 5MB)</label><input type="file" class="form-control" name="banner" accept="image/*"><?php if (!empty($editTournament['banner'])): ?><small style="color:var(--success)">Current: <?= htmlspecialchars($editTournament['banner']) ?></small><?php endif; ?></div>
      </div>
      <div class="form-group"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($editTournament['description'] ?? '') ?></textarea></div>
      <div class="form-group"><label class="form-label">Rules (one per line)</label><textarea class="form-control" name="rules" rows="5"><?= htmlspecialchars($editTournament['rules'] ?? '') ?></textarea></div>
      <div class="form-group"><label class="form-label">Schedule (one per line)</label><textarea class="form-control" name="schedule" rows="3"><?= htmlspecialchars($editTournament['schedule'] ?? '') ?></textarea></div>
      <div class="form-group"><label class="form-label">Prize Distribution (one per line)</label><textarea class="form-control" name="prize_distribution" rows="4"><?= htmlspecialchars($editTournament['prize_distribution'] ?? '') ?></textarea></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Contact Info</label><input type="text" class="form-control" name="contact_info" value="<?= htmlspecialchars($editTournament['contact_info'] ?? '') ?>"></div>
        <div class="form-group"><label class="form-label">Discord Link</label><input type="url" class="form-control" name="discord_link" value="<?= htmlspecialchars($editTournament['discord_link'] ?? '') ?>"></div>
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px;color:var(--text-muted);cursor:pointer">
          <input type="checkbox" name="registration_open" value="1" <?= (!$editTournament || $editTournament['registration_open']) ? 'checked' : '' ?> style="accent-color:var(--primary)">
          Registration Open
        </label>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $editTournament ? 'Update' : 'Create' ?> Tournament</button>
        <button type="button" onclick="hideForm()" class="btn btn-outline">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Tournaments Table -->
<div class="admin-card">
  <div class="table-responsive">
    <table>
      <thead><tr><th>Tournament</th><th>Mode</th><th>Fee</th><th>Prize</th><th>Slots</th><th>Deadline</th><th>Status</th><th>Reg.</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($tournaments as $t): ?>
        <tr>
          <td>
            <strong><?= htmlspecialchars($t['name']) ?></strong>
            <div style="font-size:11px;color:var(--text-dim)"><?= htmlspecialchars($t['game']) ?></div>
          </td>
          <td><span class="badge badge-<?= $t['mode'] ?>"><?= strtoupper($t['mode']) ?></span></td>
          <td><?= $t['entry_fee'] > 0 ? '₹'.number_format((float)$t['entry_fee']) : '<span style="color:var(--success)">FREE</span>' ?></td>
          <td>₹<?= number_format((float)$t['prize_pool']) ?></td>
          <td><span style="color:<?= $t['registered_slots'] >= $t['total_slots'] ? 'var(--danger)' : 'var(--success)' ?>"><?= $t['registered_slots'] ?></span>/<?= $t['total_slots'] ?></td>
          <td style="font-size:12px"><?= date('d M Y', strtotime($t['registration_deadline'])) ?></td>
          <td><span class="badge badge-<?= $t['status']==='active'?'success':($t['status']==='upcoming'?'warning':($t['status']==='completed'?'info':'danger')) ?>"><?= $t['status'] ?></span></td>
          <td><span class="badge badge-<?= $t['registration_open']?'success':'danger' ?>"><?= $t['registration_open']?'Open':'Closed' ?></span></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              <a href="/admin/registrations.php?tournament_id=<?= $t['id'] ?>" class="btn btn-sm btn-info" title="View Registrations"><i class="fas fa-users"></i> <?= $t['confirmed_count'] ?></a>
              <a href="?edit=<?= $t['id'] ?>" class="btn btn-sm btn-outline" title="Edit"><i class="fas fa-edit"></i></a>
              <form method="POST" style="display:inline" onsubmit="return confirm('Toggle registration status?')">
                <?= Security::csrfField() ?><input type="hidden" name="action" value="toggle_reg"><input type="hidden" name="tournament_id" value="<?= $t['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline" title="Toggle Registration"><?= $t['registration_open'] ? '<i class="fas fa-lock"></i>' : '<i class="fas fa-lock-open"></i>' ?></button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('DELETE this tournament? This cannot be undone!')">
                <?= Security::csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="tournament_id" value="<?= $t['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($tournaments)): ?><tr><td colspan="9" style="text-align:center;color:var(--text-dim);padding:40px">No tournaments yet. Create your first one!</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function showForm(){document.getElementById('tournamentForm').style.display='block';document.getElementById('tournamentForm').scrollIntoView({behavior:'smooth'});}
function hideForm(){document.getElementById('tournamentForm').style.display='none';}
</script>

<?php endAdminLayout(); ?>
