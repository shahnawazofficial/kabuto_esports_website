<?php
/**
 * KABUTO ESPORTS - Shared Header/Footer helpers & Bootstrap functions
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/UserAuth.php';

Security::startSession();
Security::setSecurityHeaders();

function getUpcomingTournaments(int $limit = 6): array {
    return Database::fetchAll(
        "SELECT * FROM tournaments WHERE status IN ('upcoming','active') 
         AND registration_deadline > NOW() ORDER BY registration_deadline ASC LIMIT ?",
        [$limit]
    );
}

function getTournamentBySlug(string $slug): ?array {
    return Database::fetchOne(
        "SELECT * FROM tournaments WHERE slug = ? AND status != 'cancelled'",
        [$slug]
    );
}

function getTournamentById(int $id): ?array {
    return Database::fetchOne(
        "SELECT * FROM tournaments WHERE id = ?",
        [$id]
    );
}

function formatCurrency(float $amount): string {
    return '₹' . number_format($amount, 0, '.', ',');
}

function formatDateTime(string $dt): string {
    return date('d M Y, h:i A', strtotime($dt));
}

function slugify(string $text): string {
    $text = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
    return preg_replace('/-+/', '-', $text);
}

function redirect(string $url, int $code = 302): never {
    header("Location: $url", true, $code);
    exit;
}

function jsonResponse(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function getClientIp(): string {
    $keys = ['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'];
    foreach ($keys as $k) {
        if (!empty($_SERVER[$k])) {
            $ips = explode(',', $_SERVER[$k]);
            $ip  = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
