<?php
/**
 * KABUTO ESPORTS — Admin Login (Production Clean Version)
 */

// Load dependencies — auth.php handles session + security headers already
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

// auth.php already called Security::startSession() and Security::setSecurityHeaders()
// DO NOT call them again here — causes 500 from double header send

$error     = '';
$reason    = isset($_GET['reason']) ? htmlspecialchars($_GET['reason']) : '';
$reasonMsg = '';
if ($reason === 'timeout')  $reasonMsg = 'Session expired. Please login again.';
if ($reason === 'inactive') $reasonMsg = 'Your account has been deactivated.';

// ── Redirect if already logged in ───────────────────────────
if (!empty($_SESSION['admin_id'])) {
    header('Location: /admin/index.php');
    exit;
}

// ── Handle Login Form Submission ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Basic CSRF check
    $token = $_POST['csrf_token'] ?? '';
    if (!Security::validateCsrfToken($token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $email    = Security::clean($_POST['email']    ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter your email and password.';
        } else {
            // Look up admin
            $admin = Database::fetchOne(
                "SELECT id, name, email, password_hash, role, is_active FROM admins WHERE email = ?",
                [$email]
            );

            if (!$admin || !password_verify($password, $admin['password_hash'])) {
                $error = 'Invalid email or password.';
            } elseif (!$admin['is_active']) {
                $error = 'Your account has been deactivated.';
            } else {
                // ── Successful login ─────────────────────────────────
                session_regenerate_id(true);
                $_SESSION['admin_id']   = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_email']= $admin['email'];
                $_SESSION['login_time'] = time();

                // Update last_login
                Database::execute(
                    "UPDATE admins SET last_login = NOW() WHERE id = ?",
                    [$admin['id']]
                );

                header('Location: /admin/index.php');
                exit;
            }
        }
    }
}

$csrfToken = Security::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login – Kabuto Esports</title>
<link rel="stylesheet" href="/assets/css/admin.css">
<style>
  body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:#0a0a0f}
  .login-box{background:#13131a;border:1px solid #2a2a3e;border-radius:16px;padding:40px 36px;width:100%;max-width:380px;box-shadow:0 20px 60px rgba(0,0,0,.5)}
  .login-logo{text-align:center;margin-bottom:28px}
  .login-logo .brand{font-family:'Orbitron',sans-serif;font-size:20px;font-weight:700;color:#fff;letter-spacing:2px}
  .login-logo .brand span{color:#f5a623}
  .login-logo .sub{color:#666;font-size:13px;margin-top:4px}
  .login-logo .icon{font-size:36px;margin-bottom:8px}
  .form-group{margin-bottom:18px}
  .form-group label{display:block;font-size:12px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px}
  .form-group input{width:100%;padding:12px 14px;background:#0d0d14;border:1px solid #2a2a3e;border-radius:8px;color:#fff;font-size:14px;outline:none;box-sizing:border-box;transition:.2s}
  .form-group input:focus{border-color:#f5a623}
  .btn-login{width:100%;padding:13px;background:linear-gradient(135deg,#f5a623,#e8922a);border:none;border-radius:8px;color:#fff;font-size:15px;font-weight:600;cursor:pointer;transition:.2s;margin-top:6px}
  .btn-login:hover{opacity:.9;transform:translateY(-1px)}
  .alert{padding:12px 14px;border-radius:8px;font-size:13px;margin-bottom:18px;display:flex;align-items:center;gap:8px}
  .alert-danger{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#ef4444}
  .alert-warning{background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.3);color:#f59e0b}
  .footer-note{text-align:center;font-size:12px;color:#444;margin-top:20px}
  .pw-wrap{position:relative}
  .pw-wrap input{padding-right:42px}
  .pw-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#666;cursor:pointer;font-size:16px;padding:0}
</style>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="login-box">
  <div class="login-logo">
    <div class="icon">&#9876;</div>
    <div class="brand">KABUTO <span>ESPORTS</span></div>
    <div class="sub">Admin Panel</div>
  </div>

  <?php if ($reasonMsg): ?>
  <div class="alert alert-warning">&#9888; <?= $reasonMsg ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
  <div class="alert alert-danger">&#10006; <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="/admin/login.php">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

    <div class="form-group">
      <label>Email Address</label>
      <input type="email" name="email" id="email" required
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
             placeholder="admin@kabutoesports.com" autocomplete="username">
    </div>

    <div class="form-group">
      <label>Password</label>
      <div class="pw-wrap">
        <input type="password" name="password" id="password" required
               placeholder="••••••••" autocomplete="current-password">
        <button type="button" class="pw-toggle" onclick="togglePw()" title="Show/hide password">&#128065;</button>
      </div>
    </div>

    <button type="submit" class="btn-login">&#8594; Sign In</button>
  </form>

  <div class="footer-note">Protected admin area. Unauthorized access is prohibited.</div>
</div>

<script>
function togglePw(){
  var p=document.getElementById('password');
  p.type=p.type==='password'?'text':'password';
}
</script>
</body>
</html>
