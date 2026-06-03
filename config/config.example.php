<?php
/**
 * KABUTO ESPORTS — Configuration Template
 * =========================================
 * Copy this file to config.php and fill in your actual values.
 * NEVER commit config.php to version control!
 *
 * Usage:  cp config/config.example.php config/config.php
 */

// ============================================================
// ENVIRONMENT
// ============================================================
define('APP_ENV',     'production');   // 'production' | 'development'
define('APP_DEBUG',   false);
define('APP_NAME',    'Kabuto Esports');
define('APP_URL',     'https://yourdomain.com');  // ← Your domain
define('APP_VERSION', '1.0.0');

// ============================================================
// DATABASE
// ============================================================
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'your_database_name');   // ← Your DB name
define('DB_USER',    'your_database_user');   // ← Your DB username
define('DB_PASS',    'your_database_password'); // ← Your DB password
define('DB_CHARSET', 'utf8mb4');

// ============================================================
// PAYU NOW PAYMENT GATEWAY
// Get credentials from: https://onboarding.payu.in
// ============================================================
define('PAYU_ENV',         'production');  // 'test' | 'production'
define('PAYU_KEY',         'YOUR_PAYU_MERCHANT_KEY');   // ← PayU Merchant Key
define('PAYU_SALT',        'YOUR_PAYU_SALT_KEY');       // ← PayU Salt (v1)
define('PAYU_AUTH_HEADER', '');

if (PAYU_ENV === 'production') {
    define('PAYU_BASE_URL',   'https://secure.payu.in/_payment');
    define('PAYU_VERIFY_URL', 'https://info.payu.in/merchant/postservice?form=2');
} else {
    define('PAYU_BASE_URL',   'https://test.payu.in/_payment');
    define('PAYU_VERIFY_URL', 'https://test.payu.in/merchant/postservice?form=2');
}

define('PAYU_SUCCESS_URL', APP_URL . '/payment/success');
define('PAYU_FAILURE_URL', APP_URL . '/payment/failure');

// ============================================================
// EMAIL (SMTP)
// ============================================================
define('MAIL_DRIVER',     'smtp');
define('MAIL_HOST',       'smtp.hostinger.com');   // ← Your SMTP host
define('MAIL_PORT',       465);
define('MAIL_USERNAME',   'noreply@yourdomain.com');  // ← Your email
define('MAIL_PASSWORD',   'YOUR_EMAIL_PASSWORD');     // ← Your email password
define('MAIL_ENCRYPTION', 'ssl');
define('MAIL_FROM_NAME',  'Kabuto Esports');
define('MAIL_FROM_EMAIL', 'noreply@yourdomain.com');
define('ADMIN_NOTIFY_EMAIL', 'admin@yourdomain.com');

// ============================================================
// SESSION & SECURITY
// ============================================================
define('SESSION_LIFETIME',       7200);
define('CSRF_TOKEN_LIFETIME',    3600);
define('BCRYPT_ROUNDS',          12);
define('REGISTRATION_ID_PREFIX', 'KAB');
define('SECRET_KEY',             'change-this-to-a-random-32-char-string');  // ← Change!
define('USER_SESSION_KEY',       'kabuto_user_session');

// ============================================================
// UPLOADS
// ============================================================
define('UPLOAD_PATH',        __DIR__ . '/../uploads/');
define('UPLOAD_MAX_SIZE',    5 * 1024 * 1024);
define('ALLOWED_IMG_TYPES',  ['image/jpeg', 'image/png', 'image/webp']);
define('BANNER_UPLOAD_PATH', UPLOAD_PATH . 'banners/');

// ============================================================
// PAGINATION
// ============================================================
define('ITEMS_PER_PAGE',       20);
define('TOURNAMENTS_PER_PAGE', 12);

// ============================================================
// TIMEZONE
// ============================================================
date_default_timezone_set('Asia/Kolkata');

// ============================================================
// ERROR REPORTING
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');
