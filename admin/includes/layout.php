<?php
/**
 * Shared admin sidebar/layout partials.
 */
function adminSidebarLink(string $href, string $label, string $icon, string $current): string {
    $active = strpos($current, basename($href, '.php')) !== false ? 'active' : '';
    return "<a href='$href' class='sidebar-link $active'><svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='currentColor'>$icon</svg> $label</a>";
}

function startAdminLayout(string $title, array $admin, string $current = ''): void {
    $page = basename($current ?: $_SERVER['PHP_SELF']);
    echo '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>' . htmlspecialchars($title) . ' – Kabuto Admin</title>
<link rel="stylesheet" href="/assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div><div class="brand-text">⚔️ KABUTO</div><div class="brand-sub">Admin Panel</div></div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>
    <a href="/admin/index.php" class="sidebar-link ' . ($page==='index.php'?'active':'') . '"><i class="fas fa-tachometer-alt fa-fw"></i> Dashboard</a>
    <div class="nav-section-label">Management</div>
    <a href="/admin/tournaments.php" class="sidebar-link ' . ($page==='tournaments.php'?'active':'') . '"><i class="fas fa-trophy fa-fw"></i> Tournaments</a>
    <a href="/admin/registrations.php" class="sidebar-link ' . ($page==='registrations.php'?'active':'') . '"><i class="fas fa-users fa-fw"></i> Registrations</a>
    <a href="/admin/payments.php" class="sidebar-link ' . ($page==='payments.php'?'active':'') . '"><i class="fas fa-credit-card fa-fw"></i> Payments</a>
    <div class="nav-section-label">Settings</div>
    <a href="/admin/admins.php" class="sidebar-link ' . ($page==='admins.php'?'active':'') . '"><i class="fas fa-user-shield fa-fw"></i> Admins</a>
    <a href="/admin/coupons.php" class="sidebar-link ' . ($page==='coupons.php'?'active':'') . '"><i class="fas fa-tag fa-fw"></i> Coupons</a>
    <a href="/" class="sidebar-link" target="_blank"><i class="fas fa-external-link-alt fa-fw"></i> View Site</a>
    <a href="/admin/logout.php" class="sidebar-link" style="color:var(--danger)"><i class="fas fa-sign-out-alt fa-fw"></i> Logout</a>
  </nav>
  <div class="sidebar-footer">
    <strong>' . htmlspecialchars($admin['name']) . '</strong>
    ' . ucfirst($admin['role']) . '
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">' . htmlspecialchars($title) . '</div>
    <div class="topbar-right">
      <button onclick="document.getElementById(\'sidebar\').classList.toggle(\'open\')" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:18px"><i class="fas fa-bars"></i></button>
      <a href="/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>
  <div class="content">';
}

function endAdminLayout(): void {
    echo '</div></div></body></html>';
}

function flashMessage(): string {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $type = htmlspecialchars($f['type']);
        $msg  = htmlspecialchars($f['msg']);
        return "<div class='alert alert-$type'><i class='fas fa-info-circle'></i> $msg</div>";
    }
    return '';
}

function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
