<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$admin = requireAdmin();
if ($admin['role'] !== 'super_admin') {
    setFlash('danger', 'Only super admins can manage admin accounts.');
    header('Location: /admin/index.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = Security::clean($_POST['action'] ?? '');

    if ($action === 'create') {
        $name  = Security::clean($_POST['name'] ?? '');
        $email = Security::sanitizeEmail($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $role  = in_array($_POST['role']??'', ['admin','moderator','super_admin']) ? $_POST['role'] : 'admin';

        if (!$name || !$email || strlen($pass) < 8) {
            setFlash('danger', 'All fields required. Password min 8 chars.');
        } else {
            $exists = Database::fetchOne("SELECT id FROM admins WHERE email=?", [$email]);
            if ($exists) {
                setFlash('danger', 'An admin with this email already exists.');
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => BCRYPT_ROUNDS]);
                Database::query("INSERT INTO admins (name,email,password_hash,role) VALUES (?,?,?,?)", [$name,$email,$hash,$role]);
                setFlash('success', 'Admin account created for ' . $name);
            }
        }
    }

    if ($action === 'toggle') {
        $aId = Security::sanitizeInt($_POST['admin_id'] ?? 0);
        if ($aId == $_SESSION['admin_id']) { setFlash('danger', 'Cannot deactivate yourself.'); }
        else { Database::query("UPDATE admins SET is_active = NOT is_active WHERE id=?", [$aId]); setFlash('success','Admin status updated.'); }
    }

    if ($action === 'reset_password') {
        $aId  = Security::sanitizeInt($_POST['admin_id'] ?? 0);
        $pass = $_POST['new_password'] ?? '';
        if (strlen($pass) < 8) { setFlash('danger','Password min 8 chars.'); }
        else {
            $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => BCRYPT_ROUNDS]);
            Database::query("UPDATE admins SET password_hash=? WHERE id=?", [$hash,$aId]);
            setFlash('success','Password updated.');
        }
    }

    header('Location: /admin/admins.php'); exit;
}

$admins = Database::fetchAll("SELECT id,name,email,role,is_active,last_login,created_at FROM admins ORDER BY created_at");
startAdminLayout('Admin Accounts', $admin);
echo flashMessage();
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
  <h2 style="font-family:'Rajdhani',sans-serif;font-size:22px">Admin Accounts</h2>
  <button class="btn btn-primary" onclick="document.getElementById('createForm').style.display='block'"><i class="fas fa-user-plus"></i> Add Admin</button>
</div>

<div class="admin-card" id="createForm" style="display:none;margin-bottom:24px">
  <div class="admin-card-header"><div class="admin-card-title">Create Admin Account</div><button onclick="this.closest('#createForm').style.display='none'" class="btn btn-sm btn-outline">✕</button></div>
  <div class="admin-card-body">
    <form method="POST">
      <?= Security::csrfField() ?><input type="hidden" name="action" value="create">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Full Name</label><input type="text" class="form-control" name="name" required></div>
        <div class="form-group"><label class="form-label">Email</label><input type="email" class="form-control" name="email" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Password (min 8 chars)</label><input type="password" class="form-control" name="password" minlength="8" required></div>
        <div class="form-group"><label class="form-label">Role</label>
          <select name="role" class="form-control form-select">
            <option value="moderator">Moderator</option>
            <option value="admin">Admin</option>
            <option value="super_admin">Super Admin</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create Account</button>
    </form>
  </div>
</div>

<div class="admin-card">
  <div class="table-responsive">
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Created</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($admins as $a): ?>
        <tr>
          <td><strong><?= htmlspecialchars($a['name']) ?></strong><?= $a['id'] == $_SESSION['admin_id'] ? ' <span class="badge badge-info">You</span>' : '' ?></td>
          <td style="font-size:13px"><?= htmlspecialchars($a['email']) ?></td>
          <td><span class="badge badge-<?= $a['role']==='super_admin'?'primary':($a['role']==='admin'?'info':'secondary') ?>"><?= $a['role'] ?></span></td>
          <td><span class="badge badge-<?= $a['is_active']?'success':'danger' ?>"><?= $a['is_active']?'Active':'Inactive' ?></span></td>
          <td style="font-size:12px;color:var(--text-muted)"><?= $a['last_login'] ? date('d M Y, h:i A', strtotime($a['last_login'])) : 'Never' ?></td>
          <td style="font-size:12px;color:var(--text-muted)"><?= date('d M Y', strtotime($a['created_at'])) ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <?php if ($a['id'] != $_SESSION['admin_id']): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Toggle this admin status?')">
                <?= Security::csrfField() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="admin_id" value="<?= $a['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline"><?= $a['is_active']?'<i class="fas fa-ban"></i>':'<i class="fas fa-check"></i>' ?></button>
              </form>
              <?php endif; ?>
              <button onclick="showResetForm(<?= $a['id'] ?>,'<?= htmlspecialchars($a['name']) ?>')" class="btn btn-sm btn-warning" style="background:rgba(251,191,36,.15);color:var(--warning);border:1px solid rgba(251,191,36,.3)"><i class="fas fa-key"></i></button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Password Reset Modal -->
<div id="resetModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;display:none;align-items:center;justify-content:center;padding:20px">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:28px;max-width:400px;width:100%">
    <h3 id="resetTitle" style="margin-bottom:16px;font-family:'Rajdhani',sans-serif;color:var(--primary)">Reset Password</h3>
    <form method="POST">
      <?= Security::csrfField() ?><input type="hidden" name="action" value="reset_password"><input type="hidden" name="admin_id" id="resetAdminId">
      <div class="form-group"><label class="form-label">New Password (min 8 chars)</label><input type="password" class="form-control" name="new_password" minlength="8" required></div>
      <div style="display:flex;gap:8px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Password</button>
        <button type="button" onclick="document.getElementById('resetModal').style.display='none'" class="btn btn-outline">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function showResetForm(id,name){
  document.getElementById('resetAdminId').value=id;
  document.getElementById('resetTitle').textContent='Reset Password – '+name;
  document.getElementById('resetModal').style.display='flex';
}
</script>

<?php endAdminLayout(); ?>
