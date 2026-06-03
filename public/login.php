<?php
/**
 * KABUTO ESPORTS — User Login Page
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/UserAuth.php';

// Already logged in?
if (UserAuth::check()) {
    redirect('/dashboard');
}

$error  = '';
$next   = Security::clean($_GET['next'] ?? '/dashboard');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token error. Please try again.';
    } else {
        $result = UserAuth::login(
            $_POST['email']    ?? '',
            $_POST['password'] ?? ''
        );
        if ($result['success']) {
            redirect($next ?: '/dashboard');
        } else {
            $error = $result['error'];
        }
    }
}

$csrf = Security::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login – Kabuto Esports</title>
<meta name="description" content="Login to your Kabuto Esports account to view and manage your tournament registrations.">
<link rel="stylesheet" href="/assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.auth-page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:100px 20px 40px;background:radial-gradient(ellipse at 30% 20%,rgba(124,58,237,0.08),transparent 60%),radial-gradient(ellipse at 70% 80%,rgba(245,166,35,0.06),transparent 60%)}
.auth-card{background:var(--bg-card);border:1px solid var(--border);border-radius:20px;padding:44px 40px;width:100%;max-width:440px;box-shadow:0 24px 80px rgba(0,0,0,.5)}
.auth-logo{text-align:center;margin-bottom:32px}
.auth-logo-icon{font-size:40px;margin-bottom:10px}
.auth-logo h2{font-family:'Orbitron',sans-serif;font-size:22px;font-weight:900;letter-spacing:2px;margin:0}
.auth-logo h2 span{color:var(--primary)}
.auth-logo p{color:var(--text-muted);font-size:14px;margin:6px 0 0}
.auth-divider{display:flex;align-items:center;gap:12px;margin:24px 0;color:var(--text-dim);font-size:12px;text-transform:uppercase;letter-spacing:1px}
.auth-divider::before,.auth-divider::after{content:'';flex:1;height:1px;background:var(--border)}
.auth-footer{text-align:center;margin-top:24px;font-size:14px;color:var(--text-muted)}
.auth-footer a{color:var(--primary);text-decoration:none;font-weight:600}
.auth-footer a:hover{text-decoration:underline}
.form-group{margin-bottom:20px}
.form-label{display:block;font-size:12px;font-weight:600;color:var(--text-dim);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px}
.form-input{width:100%;padding:13px 16px;background:var(--bg-surface);border:1px solid var(--border);border-radius:10px;color:var(--text-primary);font-size:15px;font-family:'Inter',sans-serif;outline:none;transition:.2s;box-sizing:border-box}
.form-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(245,166,35,0.1)}
.form-input::placeholder{color:var(--text-dim)}
.input-icon-wrap{position:relative}
.input-icon-wrap .form-input{padding-left:44px}
.input-icon-wrap .icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-dim);font-size:16px}
.pw-wrap{position:relative}
.pw-wrap .form-input{padding-right:46px}
.pw-toggle{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-dim);cursor:pointer;font-size:15px;padding:4px}
.btn-auth{width:100%;padding:14px;background:linear-gradient(135deg,var(--primary),#e8922a);border:none;border-radius:10px;color:#fff;font-size:16px;font-weight:700;cursor:pointer;transition:.2s;letter-spacing:.5px;margin-top:4px}
.btn-auth:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 8px 24px rgba(245,166,35,.3)}
.alert-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#f87171;padding:12px 16px;border-radius:10px;font-size:14px;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.forgot-link{text-align:right;margin-top:-12px;margin-bottom:20px}
.forgot-link a{font-size:13px;color:var(--text-muted);text-decoration:none}
.forgot-link a:hover{color:var(--primary)}
</style>
</head>
<body>

<nav class="navbar" id="navbar">
  <div class="navbar-inner">
    <a href="/" class="navbar-brand">&#9876; <span>KABUTO <span style="color:var(--primary)">ESPORTS</span></span></a>
    <div class="nav-links">
      <a href="/">Home</a>
      <a href="/tournaments">Tournaments</a>
      <a href="/login" class="active">Login</a>
      <a href="/signup" class="nav-cta">Sign Up</a>
    </div>
    <div class="nav-hamburger" id="hamburger" onclick="toggleNav()"><span></span><span></span><span></span></div>
  </div>
</nav>

<div class="auth-page">
  <div class="auth-card">

    <div class="auth-logo">
      <div class="auth-logo-icon">&#9876;</div>
      <h2>KABUTO <span>ESPORTS</span></h2>
      <p>Sign in to your account</p>
    </div>

    <?php if ($error): ?>
    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/login?next=<?= urlencode($next) ?>">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <div class="input-icon-wrap">
          <i class="fas fa-envelope icon"></i>
          <input type="email" name="email" class="form-input" required
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="your@email.com" autocomplete="email">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="pw-wrap">
          <input type="password" name="password" id="pw" class="form-input"
                 required placeholder="••••••••" autocomplete="current-password">
          <button type="button" class="pw-toggle" onclick="togglePw()">
            <i class="fas fa-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <div class="forgot-link"><a href="#">Forgot password?</a></div>

      <button type="submit" class="btn-auth">
        <i class="fas fa-sign-in-alt"></i> Sign In
      </button>
    </form>

    <div class="auth-divider">or</div>

    <div class="auth-footer">
      Don't have an account? <a href="/signup">Create one free</a>
    </div>

  </div>
</div>

<script>
window.addEventListener('scroll',()=>{
  document.getElementById('navbar').classList.toggle('scrolled',window.scrollY>50);
});
function toggleNav(){document.getElementById('navLinks')?.classList.toggle('open');}
function togglePw(){
  var p=document.getElementById('pw');
  var i=document.getElementById('eyeIcon');
  if(p.type==='password'){p.type='text';i.className='fas fa-eye-slash';}
  else{p.type='password';i.className='fas fa-eye';}
}
</script>
</body>
</html>
