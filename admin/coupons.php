<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$admin = requireAdmin();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = Security::clean($_POST['action'] ?? '');

    if ($action === 'create') {
        $code          = strtoupper(trim(Security::clean($_POST['code'] ?? '')));
        $description   = Security::clean($_POST['description'] ?? '');
        $discountType  = in_array($_POST['discount_type'] ?? '', ['percent', 'fixed']) ? $_POST['discount_type'] : 'percent';
        $discountValue = max(0, (float)($_POST['discount_value'] ?? 0));
        $maxUses       = Security::sanitizeInt($_POST['max_uses'] ?? 0);
        $minFee        = max(0, (float)($_POST['min_fee'] ?? 0));
        $expiresAt     = Security::clean($_POST['expires_at'] ?? '');
        $isActive      = isset($_POST['is_active']) ? 1 : 0;

        if (!$code || strlen($code) < 3) {
            setFlash('danger', 'Coupon code must be at least 3 characters.');
        } elseif ($discountType === 'percent' && $discountValue > 100) {
            setFlash('danger', 'Percentage discount cannot exceed 100%.');
        } else {
            // Check unique
            $existing = Database::fetchOne("SELECT id FROM coupons WHERE code = ?", [$code]);
            if ($existing) {
                setFlash('danger', "Coupon code '$code' already exists.");
            } else {
                Database::query(
                    "INSERT INTO coupons (code, description, discount_type, discount_value, max_uses, min_fee, expires_at, is_active, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$code, $description, $discountType, $discountValue, $maxUses ?: null, $minFee, $expiresAt ?: null, $isActive, $admin['id']]
                );
                setFlash('success', "Coupon '$code' created successfully!");
            }
        }
        header('Location: /admin/coupons.php'); exit;
    }

    if ($action === 'toggle') {
        $cid = Security::sanitizeInt($_POST['coupon_id'] ?? 0);
        Database::query("UPDATE coupons SET is_active = NOT is_active WHERE id = ?", [$cid]);
        setFlash('success', 'Coupon status updated.');
        header('Location: /admin/coupons.php'); exit;
    }

    if ($action === 'delete') {
        $cid = Security::sanitizeInt($_POST['coupon_id'] ?? 0);
        Database::query("DELETE FROM coupons WHERE id = ?", [$cid]);
        setFlash('success', 'Coupon deleted.');
        header('Location: /admin/coupons.php'); exit;
    }
}

$coupons = Database::fetchAll("SELECT * FROM coupons ORDER BY created_at DESC");

startAdminLayout('Coupon Management', $admin);
echo flashMessage();
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
  <h2 style="font-family:'Rajdhani',sans-serif;font-size:22px">🎟️ Coupon Codes</h2>
  <button class="btn btn-primary" onclick="document.getElementById('createForm').style.display='block'">
    <i class="fas fa-plus"></i> Generate Coupon
  </button>
</div>

<!-- Create Coupon Form -->
<div class="admin-card" id="createForm" style="display:none;margin-bottom:24px">
  <div class="admin-card-header">
    <div class="admin-card-title"><i class="fas fa-tag" style="color:var(--primary)"></i> Generate New Coupon</div>
    <button onclick="this.closest('#createForm').style.display='none'" class="btn btn-sm btn-outline"><i class="fas fa-times"></i></button>
  </div>
  <div class="admin-card-body">
    <form method="POST">
      <?= Security::csrfField() ?>
      <input type="hidden" name="action" value="create">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Coupon Code *</label>
          <input type="text" class="form-control" name="code" placeholder="e.g. KABUTO20" style="text-transform:uppercase" required maxlength="30">
          <small style="color:var(--text-dim)">Alphanumeric, no spaces. Will be uppercased automatically.</small>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <input type="text" class="form-control" name="description" placeholder="e.g. 20% off for all squads">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Discount Type *</label>
          <select name="discount_type" class="form-control form-select" id="discTypeSelect" onchange="updatePlaceholder(this)">
            <option value="percent">Percentage (%)</option>
            <option value="fixed">Fixed Amount (₹)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Discount Value *</label>
          <input type="number" class="form-control" name="discount_value" id="discValueInput" placeholder="e.g. 20 (for 20%)" min="0" step="0.01" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Max Uses <small style="color:var(--text-dim)">(leave 0 = unlimited)</small></label>
          <input type="number" class="form-control" name="max_uses" placeholder="0 = unlimited" min="0" value="0">
        </div>
        <div class="form-group">
          <label class="form-label">Minimum Entry Fee (₹) <small style="color:var(--text-dim)">(0 = any)</small></label>
          <input type="number" class="form-control" name="min_fee" placeholder="0" min="0" step="0.01" value="0">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Expiry Date & Time <small style="color:var(--text-dim)">(optional)</small></label>
          <input type="datetime-local" class="form-control" name="expires_at">
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:4px">
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;color:var(--text-muted)">
            <input type="checkbox" name="is_active" value="1" checked style="accent-color:var(--primary)">
            Active (usable immediately)
          </label>
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Coupon</button>
        <button type="button" onclick="document.getElementById('createForm').style.display='none'" class="btn btn-outline">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Coupons Table -->
<div class="admin-card">
  <div class="table-responsive">
    <table>
      <thead><tr>
        <th>Code</th><th>Description</th><th>Discount</th>
        <th>Uses</th><th>Min Fee</th><th>Expires</th><th>Status</th><th>Actions</th>
      </tr></thead>
      <tbody>
        <?php foreach ($coupons as $c): ?>
        <tr>
          <td>
            <code style="background:var(--bg-surface);padding:4px 10px;border-radius:6px;font-size:14px;font-weight:700;color:var(--primary);letter-spacing:1px">
              <?= htmlspecialchars($c['code']) ?>
            </code>
          </td>
          <td style="font-size:13px;color:var(--text-muted)"><?= htmlspecialchars($c['description'] ?? '–') ?></td>
          <td>
            <?php if ($c['discount_type'] === 'percent'): ?>
              <span style="color:var(--primary);font-weight:700;font-family:'Rajdhani',sans-serif;font-size:17px"><?= (float)$c['discount_value'] ?>%</span>
            <?php else: ?>
              <span style="color:var(--primary);font-weight:700;font-family:'Rajdhani',sans-serif;font-size:17px">₹<?= number_format((float)$c['discount_value']) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <span style="color:var(--text-primary)"><?= $c['used_count'] ?></span>
            <?php if ($c['max_uses']): ?><span style="color:var(--text-dim);font-size:12px"> / <?= $c['max_uses'] ?></span><?php else: ?><span style="color:var(--text-dim);font-size:12px"> / ∞</span><?php endif; ?>
          </td>
          <td><?= $c['min_fee'] > 0 ? '₹'.number_format((float)$c['min_fee']) : '–' ?></td>
          <td style="font-size:12px">
            <?= $c['expires_at'] ? date('d M Y H:i', strtotime($c['expires_at'])) : '<span style="color:var(--text-dim)">Never</span>' ?>
          </td>
          <td>
            <span class="badge badge-<?= $c['is_active'] ? 'success' : 'danger' ?>">
              <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
          </td>
          <td>
            <div style="display:flex;gap:6px">
              <form method="POST" style="display:inline">
                <?= Security::csrfField() ?><input type="hidden" name="action" value="toggle">
                <input type="hidden" name="coupon_id" value="<?= $c['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline" title="Toggle Status">
                  <i class="fas fa-<?= $c['is_active'] ? 'pause' : 'play' ?>"></i>
                </button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete coupon <?= htmlspecialchars($c['code']) ?>?')">
                <?= Security::csrfField() ?><input type="hidden" name="action" value="delete">
                <input type="hidden" name="coupon_id" value="<?= $c['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($coupons)): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--text-dim);padding:40px">No coupons yet. Create your first one!</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function updatePlaceholder(sel) {
  const inp = document.getElementById('discValueInput');
  inp.placeholder = sel.value === 'percent' ? 'e.g. 20 (for 20% off)' : 'e.g. 50 (₹50 off)';
  inp.max = sel.value === 'percent' ? '100' : '';
}
// Auto-uppercase coupon code input
document.querySelector('input[name="code"]').addEventListener('input', function() {
  this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
});
</script>

<?php endAdminLayout(); ?>
