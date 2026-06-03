<?php
require_once __DIR__ . '/includes/auth.php';
Security::destroySession();
header('Location: /admin/login.php');
exit;
