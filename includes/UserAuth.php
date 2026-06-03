<?php
/**
 * KABUTO ESPORTS — User Authentication Helper
 */

class UserAuth
{
    // ── Login ────────────────────────────────────────────────
    public static function login(string $email, string $password): array
    {
        $user = Database::fetchOne(
            "SELECT id, name, email, password_hash, mobile, bgmi_uid, bgmi_ign, is_active
             FROM users WHERE email = ?",
            [strtolower(trim($email))]
        );

        if (!$user)                              return ['success' => false, 'error' => 'Invalid email or password.'];
        if (!password_verify($password, $user['password_hash'])) return ['success' => false, 'error' => 'Invalid email or password.'];
        if (!$user['is_active'])                 return ['success' => false, 'error' => 'Account is deactivated. Contact support.'];

        // Start session
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_login'] = time();

        Database::query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

        return ['success' => true, 'user' => $user];
    }

    // ── Register ─────────────────────────────────────────────
    public static function register(array $data): array
    {
        $email = strtolower(trim($data['email'] ?? ''));

        // Check duplicate email
        $exists = Database::fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($exists) return ['success' => false, 'error' => 'An account with this email already exists.'];

        $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        Database::query(
            "INSERT INTO users (name, email, password_hash, mobile, bgmi_uid, bgmi_ign)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                Security::clean($data['name']     ?? ''),
                $email,
                $hash,
                Security::clean($data['mobile']   ?? ''),
                Security::clean($data['bgmi_uid'] ?? ''),
                Security::clean($data['bgmi_ign'] ?? ''),
            ]
        );

        $userId = (int)Database::lastInsertId();

        // Auto-login after registration
        $_SESSION['user_id']    = $userId;
        $_SESSION['user_name']  = Security::clean($data['name'] ?? '');
        $_SESSION['user_email'] = $email;
        $_SESSION['user_login'] = time();

        return ['success' => true, 'user_id' => $userId];
    }

    // ── Check if logged in ───────────────────────────────────
    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    // ── Get current user ─────────────────────────────────────
    public static function current(): ?array
    {
        if (!self::check()) return null;
        return Database::fetchOne(
            "SELECT id, name, email, mobile, bgmi_uid, bgmi_ign, created_at FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }

    // ── Require login — redirect if not ─────────────────────
    public static function require(string $redirectTo = '/login'): array
    {
        if (!self::check()) {
            header('Location: ' . $redirectTo . '?next=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        return self::current();
    }

    // ── Logout ───────────────────────────────────────────────
    public static function logout(): void
    {
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_login']);
    }

    // ── Get user's registrations ─────────────────────────────
    public static function getRegistrations(int $userId): array
    {
        return Database::fetchAll(
            "SELECT r.*, t.name as tournament_name, t.slug, t.mode, t.game,
                    t.entry_fee, t.prize_pool, t.tournament_start, t.status as tournament_status,
                    t.banner, t.discord_link
             FROM registrations r
             JOIN tournaments t ON t.id = r.tournament_id
             WHERE r.user_id = ?
             ORDER BY r.created_at DESC",
            [$userId]
        );
    }
}
