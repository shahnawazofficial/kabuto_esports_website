<?php
/**
 * KABUTO ESPORTS — User Sign Up Page
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/UserAuth.php';

if (UserAuth::check()) redirect('/dashboard');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token error. Please refresh and try again.';
    } else {
        $name     = Security::clean($_POST['name']     ?? '');
        $email    = Security::clean($_POST['email']    ?? '');
        $mobile   = Security::clean($_POST['mobile']   ?? '');
        $bgmiUid  = Security::clean($_POST['bgmi_uid'] ?? '');
        $bgmiIgn  = Security::clean($_POST['bgmi_ign'] ?? '');
        $password = $_POST['password']  ?? '';
        $confirm  = $_POST['confirm']   ?? '';

        // Validate
        if (strlen($name) < 2)           $error = 'Please enter your full name.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Please enter a valid email address.';
        elseif (!preg_match('/^[6-9]\d{9}$/', $mobile))    $error = 'Please enter a valid 10-digit Indian mobile number.';
        elseif (strlen($password) < 8)   $error = 'Password must be at least 8 characters.';
        elseif ($password !== $confirm)  $error = 'Passwords do not match.';
        else {
            $result = UserAuth::register([
                'name'     => $name,
                'email'    => $email,
                'mobile'   => $mobile,
                'bgmi_uid' => $bgmiUid,
                'bgmi_ign' => $bgmiIgn,
                'password' => $password,
            ]);
            if ($result['success']) {
                redirect('/dashboard?welcome=1');
            } else {
                $error = $result['error'];
            }
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
<title>Create Account – Kabuto Esports</title>
<meta name="description" content="Sign up for Kabuto Esports to register for BGMI tournaments and track your registrations.">
<link rel="stylesheet" href="/assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.auth-page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:100px 20px 40px;background:radial-gradient(ellipse at 70% 20%,rgba(124,58,237,0.08),transparent 60%),radial-gradient(ellipse at 30% 80%,rgba(245,166,35,0.06),transparent 60%)}
.auth-card{background:var(--bg-card);border:1px solid var(--border);border-radius:20px;padding:44px 40px;width:100%;max-width:500px;box-shadow:0 24px 80px rgba(0,0,0,.5)}
.auth-logo{text-align:center;margin-bottom:32px}
.auth-logo-icon{font-size:40px;margin-bottom:10px}
.auth-logo h2{font-family:'Orbitron',sans-serif;font-size:22px;font-weight:900;letter-spacing:2px;margin:0}
.auth-logo h2 span{color:var(--primary)}
.auth-logo p{color:var(--text-muted);font-size:14px;margin:6px 0 0}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-group{margin-bottom:18px}
.form-label{display:block;font-size:12px;font-weight:600;color:var(--text-dim);text-transform:uppercase;letter-spacing:1px;margin-bottom:7px}
.form-input{width:100%;padding:12px 16px;background:var(--bg-surface);border:1px solid var(--border);border-radius:10px;color:var(--text-primary);font-size:14px;font-family:'Inter',sans-serif;outline:none;transition:.2s;box-sizing:border-box}
.form-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(245,166,35,0.1)}
.form-input::placeholder{color:var(--text-dim)}
.input-icon-wrap{position:relative}
.input-icon-wrap .form-input{padding-left:42px}
.input-icon-wrap .icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text-dim);font-size:14px}
.pw-wrap{position:relative}
.pw-wrap .form-input{padding-right:44px}
.pw-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-dim);cursor:pointer;font-size:14px;padding:4px}
.btn-auth{width:100%;padding:14px;background:linear-gradient(135deg,var(--primary),#e8922a);border:none;border-radius:10px;color:#fff;font-size:16px;font-weight:700;cursor:pointer;transition:.2s;letter-spacing:.5px;margin-top:4px}
.btn-auth:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 8px 24px rgba(245,166,35,.3)}
.alert-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#f87171;padding:12px 16px;border-radius:10px;font-size:14px;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.section-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:var(--primary);margin:22px 0 14px;padding-bottom:8px;border-bottom:1px solid var(--border)}
.auth-footer{text-align:center;margin-top:24px;font-size:14px;color:var(--text-muted)}
.auth-footer a{color:var(--primary);text-decoration:none;font-weight:600}
.optional-tag{font-size:10px;color:var(--text-dim);font-weight:400;text-transform:none;letter-spacing:0;margin-left:4px}
.terms-note{font-size:12px;color:var(--text-dim);text-align:center;margin-top:14px}
@media(max-width:480px){.form-row{grid-template-columns:1fr}.auth-card{padding:32px 24px}}
</style>
</head>
<body>

<nav class="navbar" id="navbar">
  <div class="navbar-inner">
    <a href="/" class="navbar-brand">&#9876; <span>KABUTO <span style="color:var(--primary)">ESPORTS</span></span></a>
    <div class="nav-links">
      <a href="/">Home</a>
      <a href="/tournaments">Tournaments</a>
      <a href="/login">Login</a>
      <a href="/signup" class="nav-cta active">Sign Up</a>
    </div>
    <div class="nav-hamburger" id="hamburger" onclick="toggleNav()"><span></span><span></span><span></span></div>
  </div>
</nav>

<div class="auth-page">
  <div class="auth-card">

    <div class="auth-logo">
      <div class="auth-logo-icon">&#9876;</div>
      <h2>KABUTO <span>ESPORTS</span></h2>
      <p>Create your free player account</p>
    </div>

    <?php if ($error): ?>
    <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/signup">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

      <div class="section-label">&#128100; Account Info</div>

      <div class="form-group">
        <label class="form-label">Full Name</label>
        <div class="input-icon-wrap">
          <i class="fas fa-user icon"></i>
          <input type="text" name="name" class="form-input" required
                 value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                 placeholder="Your full name">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Email</label>
          <div class="input-icon-wrap">
            <i class="fas fa-envelope icon"></i>
            <input type="email" name="email" class="form-input" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="your@email.com">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Mobile</label>
          <div class="input-icon-wrap">
            <i class="fas fa-phone icon"></i>
            <input type="tel" name="mobile" class="form-input" required
                   value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>"
                   placeholder="10-digit number" maxlength="10" pattern="[6-9][0-9]{9}">
          </div>
        </div>
      </div>

      <div class="section-label">&#127918; BGMI Info <span class="optional-tag">(optional — add later)</span></div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">BGMI UID</label>
          <div class="input-icon-wrap">
            <i class="fas fa-hashtag icon"></i>
            <input type="text" name="bgmi_uid" class="form-input"
                   value="<?= htmlspecialchars($_POST['bgmi_uid'] ?? '') ?>"
                   placeholder="e.g. 5123456789">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">In-Game Name</label>
          <div class="input-icon-wrap">
            <i class="fas fa-gamepad icon"></i>
            <input type="text" name="bgmi_ign" class="form-input"
                   value="<?= htmlspecialchars($_POST['bgmi_ign'] ?? '') ?>"
                   placeholder="Your BGMI IGN">
          </div>
        </div>
      </div>

      <div class="section-label">&#128274; Set Password</div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="pw-wrap">
            <input type="password" name="password" id="pw1" class="form-input"
                   required placeholder="Min 8 characters" minlength="8">
            <button type="button" class="pw-toggle" onclick="togglePw('pw1','e1')">
              <i class="fas fa-eye" id="e1"></i>
            </button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm</label>
          <div class="pw-wrap">
            <input type="password" name="confirm" id="pw2" class="form-input"
                   required placeholder="Repeat password">
            <button type="button" class="pw-toggle" onclick="togglePw('pw2','e2')">
              <i class="fas fa-eye" id="e2"></i>
            </button>
          </div>
        </div>
      </div>

      <button type="submit" class="btn-auth">
        <i class="fas fa-user-plus"></i> Create Account
      </button>

      <div class="terms-note">
        By signing up you agree to our <a href="#" style="color:var(--primary)">Terms</a> &amp; <a href="#" style="color:var(--primary)">Privacy Policy</a>
      </div>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="/login">Sign in</a>
    </div>

  </div>
</div>

<script>
window.addEventListener('scroll',()=>{document.getElementById('navbar').classList.toggle('scrolled',window.scrollY>50);});
function toggleNav(){document.getElementById('navLinks')?.classList.toggle('open');}
function togglePw(id,iconId){
  var p=document.getElementById(id);
  var i=document.getElementById(iconId);
  if(p.type==='password'){p.type='text';i.className='fas fa-eye-slash';}
  else{p.type='password';i.className='fas fa-eye';}
}
</script>
</body>
</html>
