<?php
/**
 * KABUTO ESPORTS - Admin Authentication Guard
 * Include at the top of every admin page.
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Security.php';
require_once __DIR__ . '/../../includes/functions.php'; // slugify, formatCurrency, redirect etc.

Security::startSession();
Security::setSecurityHeaders();

function requireAdmin(): array {
    if (empty($_SESSION['admin_id'])) {
        header('Location: /admin/login.php');
        exit;
    }

    $admin = Database::fetchOne(
        "SELECT id, name, email, role, is_active FROM admins WHERE id = ?",
        [(int)$_SESSION['admin_id']]
    );

    if (!$admin || !$admin['is_active']) {
        Security::destroySession();
        header('Location: /admin/login.php?reason=inactive');
        exit;
    }

    // Renew last activity
    $_SESSION['_last_activity'] = time();
    return $admin;
}

// Session timeout
if (!empty($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity']) > SESSION_LIFETIME) {
    Security::destroySession();
    header('Location: /admin/login.php?reason=timeout');
    exit;
}
