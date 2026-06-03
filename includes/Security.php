<?php
/**
 * KABUTO ESPORTS - Security Helper
 * CSRF, XSS, Input Sanitization, Session Security
 */

require_once __DIR__ . '/../config/config.php';

class Security
{
    // --------------------------------------------------------
    // SESSION MANAGEMENT
    // --------------------------------------------------------

    /**
     * Initialize secure session.
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'domain'   => '',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();

            // Regenerate session ID periodically to prevent fixation
            if (!isset($_SESSION['_created'])) {
                $_SESSION['_created'] = time();
                session_regenerate_id(true);
            } elseif (time() - $_SESSION['_created'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['_created'] = time();
            }
        }
    }

    /**
     * Destroy session completely.
     */
    public static function destroySession(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    // --------------------------------------------------------
    // CSRF PROTECTION
    // --------------------------------------------------------

    /**
     * Generate CSRF token and store in session.
     */
    public static function generateCsrfToken(): string
    {
        self::startSession();
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_tokens'][$token] = time() + CSRF_TOKEN_LIFETIME;
        
        // Clean expired tokens
        if (isset($_SESSION['csrf_tokens'])) {
            foreach ($_SESSION['csrf_tokens'] as $t => $expires) {
                if ($expires < time()) {
                    unset($_SESSION['csrf_tokens'][$t]);
                }
            }
        }

        return $token;
    }

    /**
     * Validate CSRF token (single-use).
     */
    public static function validateCsrfToken(string $token): bool
    {
        self::startSession();
        if (
            !empty($token) &&
            isset($_SESSION['csrf_tokens'][$token]) &&
            $_SESSION['csrf_tokens'][$token] >= time()
        ) {
            unset($_SESSION['csrf_tokens'][$token]);
            return true;
        }
        return false;
    }

    /**
     * Output CSRF hidden input field.
     */
    public static function csrfField(): string
    {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Require valid CSRF or abort with 403.
     */
    public static function requireCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!self::validateCsrfToken($token)) {
            http_response_code(403);
            die(json_encode(['success' => false, 'message' => 'Invalid or expired security token. Please refresh and try again.']));
        }
    }

    // --------------------------------------------------------
    // INPUT SANITIZATION
    // --------------------------------------------------------

    /**
     * Sanitize a string for safe output in HTML context.
     */
    public static function sanitize(mixed $input): string
    {
        if ($input === null) return '';
        return htmlspecialchars(trim((string)$input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize string for database input (trim only, prepared statements handle escaping).
     */
    public static function clean(mixed $input): string
    {
        if ($input === null) return '';
        return trim(strip_tags((string)$input));
    }

    /**
     * Validate and sanitize email.
     */
    public static function sanitizeEmail(string $email): string|false
    {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
    }

    /**
     * Validate Indian mobile number.
     */
    public static function validateMobile(string $mobile): bool
    {
        $mobile = preg_replace('/\D/', '', $mobile);
        return preg_match('/^[6-9]\d{9}$/', $mobile) === 1;
    }

    /**
     * Sanitize integer input.
     */
    public static function sanitizeInt(mixed $input): int
    {
        return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize positive integer or null.
     */
    public static function sanitizePositiveInt(mixed $input): ?int
    {
        $val = self::sanitizeInt($input);
        return $val > 0 ? $val : null;
    }

    /**
     * Generate secure random token.
     */
    public static function randomToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    // --------------------------------------------------------
    // SECURITY HEADERS
    // --------------------------------------------------------

    /**
     * Set recommended security headers.
     */
    public static function setSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: blob: https:; connect-src 'self';");
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    // --------------------------------------------------------
    // RATE LIMITING (simple IP-based using session)
    // --------------------------------------------------------

    /**
     * Simple rate limiter — max $max attempts per $window seconds.
     */
    public static function rateLimit(string $action, int $max = 5, int $window = 300): bool
    {
        self::startSession();
        $key = 'rl_' . $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $now = time();

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'reset_at' => $now + $window];
        }

        if ($now > $_SESSION[$key]['reset_at']) {
            $_SESSION[$key] = ['count' => 0, 'reset_at' => $now + $window];
        }

        if ($_SESSION[$key]['count'] >= $max) {
            return false;
        }

        $_SESSION[$key]['count']++;
        return true;
    }
}
