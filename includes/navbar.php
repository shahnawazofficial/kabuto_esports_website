<?php
/**
 * KABUTO ESPORTS — Shared Navbar
 * Include this in every public page for consistent auth-aware navigation
 * Requires: UserAuth class already loaded via functions.php
 */
$_isLoggedIn = UserAuth::check();
$_userName   = $_isLoggedIn ? ($_SESSION['user_name'] ?? 'Player') : '';
?>
<nav class="navbar" id="navbar">
  <div class="navbar-inner">
    <a href="/" class="navbar-brand" style="padding:0;background:none;border:none;box-shadow:none;display:flex;align-items:center;gap:10px">
      <img src="/data/kabutologo.png" alt="Kabuto Esports"
           style="height:52px;width:auto;display:block;filter:invert(1) brightness(1.15);mix-blend-mode:screen">
      <span style="font-family:'Orbitron','Rajdhani',sans-serif;font-size:16px;font-weight:800;letter-spacing:1px;color:#fff;line-height:1.1">KABUTO<br><span style="color:var(--primary);font-size:12px;font-weight:700;letter-spacing:2px">ESPORTS</span></span>
    </a>

    <div class="nav-links" id="navLinks">
      <a href="/">Home</a>
      <a href="/tournaments">Tournaments</a>

      <?php if ($_isLoggedIn): ?>
        <a href="/dashboard" style="display:flex;align-items:center;gap:6px">
          <span style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#8b5cf6);display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff">
            <?= strtoupper(substr($_userName, 0, 1)) ?>
          </span>
          <?= htmlspecialchars(explode(' ', $_userName)[0]) ?>
        </a>
        <a href="/logout" style="background:rgba(239,68,68,.12);color:#f87171;border:1px solid rgba(239,68,68,.25);padding:6px 14px;border-radius:8px;font-size:13px;font-weight:600">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      <?php else: ?>
        <a href="/login">Login</a>
        <a href="/signup" class="nav-cta">Sign Up</a>
      <?php endif; ?>
    </div>

    <div class="nav-hamburger" id="hamburger" onclick="toggleNav()">
      <span></span><span></span><span></span>
    </div>
  </div>
</nav>
