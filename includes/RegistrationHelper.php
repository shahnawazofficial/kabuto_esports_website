<?php
/**
 * KABUTO ESPORTS - Registration ID Generator
 * Format: KAB-YYYY-00001
 */

require_once __DIR__ . '/../config/database.php';

class RegistrationHelper
{
    /**
     * Generate unique registration ID.
     * Format: KAB-2026-00001
     */
    public static function generateRegistrationId(): string
    {
        $year = date('Y');
        $prefix = REGISTRATION_ID_PREFIX . '-' . $year . '-';

        // Find the last ID for this year
        $last = Database::fetchOne(
            "SELECT registration_id FROM registrations 
             WHERE registration_id LIKE ? 
             ORDER BY id DESC LIMIT 1",
            [$prefix . '%']
        );

        if ($last) {
            $parts = explode('-', $last['registration_id']);
            $num   = (int) end($parts) + 1;
        } else {
            $num = 1;
        }

        return $prefix . str_pad((string)$num, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Check if a UID is already registered for the same tournament.
     */
    public static function isUidRegistered(string $uid, int $tournamentId, ?int $excludeRegId = null): bool
    {
        // Only CONFIRMED (paid/free) registrations block re-registration
        // Pending = payment not completed = not a real registration
        $sql = "SELECT COUNT(*) as cnt FROM registrations 
                WHERE tournament_id = ? 
                AND payment_status IN ('paid', 'free')
                AND (leader_uid = ? OR player2_uid = ? OR player3_uid = ? OR player4_uid = ? OR sub_uid = ?)";
        $params = [$tournamentId, $uid, $uid, $uid, $uid, $uid];

        if ($excludeRegId) {
            $sql    .= ' AND id != ?';
            $params[] = $excludeRegId;
        }

        $result = Database::fetchOne($sql, $params);
        return ($result['cnt'] ?? 0) > 0;
    }

    /**
     * Check if a team name is already registered for the same tournament.
     */
    public static function isTeamRegistered(string $teamName, int $tournamentId, ?int $excludeRegId = null): bool
    {
        // Only CONFIRMED registrations block duplicate team names
        $sql = "SELECT COUNT(*) as cnt FROM registrations 
                WHERE tournament_id = ? 
                AND LOWER(TRIM(team_name)) = LOWER(TRIM(?))
                AND payment_status IN ('paid', 'free')";
        $params = [$tournamentId, $teamName];

        if ($excludeRegId) {
            $sql    .= ' AND id != ?';
            $params[] = $excludeRegId;
        }

        $result = Database::fetchOne($sql, $params);
        return ($result['cnt'] ?? 0) > 0;
    }

    /**
     * Clean up stale pending registrations for a user+tournament
     * before creating a new attempt.
     */
    public static function cleanupPendingRegistrations(int $tournamentId, ?int $userId, string $leaderUid): void
    {
        // Delete old pending rows for same UID in same tournament (older than 30 min)
        Database::query(
            "DELETE FROM registrations 
             WHERE tournament_id = ? 
             AND payment_status = 'pending'
             AND leader_uid = ?
             AND created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)",
            [$tournamentId, $leaderUid]
        );
        // Also clean up by user_id if logged in
        if ($userId) {
            Database::query(
                "DELETE FROM registrations 
                 WHERE tournament_id = ? 
                 AND user_id = ?
                 AND payment_status = 'pending'
                 AND created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)",
                [$tournamentId, $userId]
            );
        }
    }

    /**
     * Get available slots for a tournament.
     */
    public static function getAvailableSlots(int $tournamentId): int
    {
        $t = Database::fetchOne(
            "SELECT total_slots, registered_slots FROM tournaments WHERE id = ?",
            [$tournamentId]
        );
        if (!$t) return 0;
        return max(0, (int)$t['total_slots'] - (int)$t['registered_slots']);
    }

    /**
     * Increment registered slot count atomically.
     */
    public static function incrementSlot(int $tournamentId): void
    {
        Database::query(
            "UPDATE tournaments SET registered_slots = registered_slots + 1 WHERE id = ?",
            [$tournamentId]
        );
    }

    /**
     * Format registration data for display.
     */
    public static function formatRegistration(array $reg): array
    {
        $reg['created_at_formatted'] = date('d M Y, h:i A', strtotime($reg['created_at']));
        $reg['payment_badge']        = match($reg['payment_status']) {
            'paid'     => '<span class="badge badge-success">Paid</span>',
            'pending'  => '<span class="badge badge-warning">Pending</span>',
            'failed'   => '<span class="badge badge-danger">Failed</span>',
            'refunded' => '<span class="badge badge-info">Refunded</span>',
            'free'     => '<span class="badge badge-primary">Free</span>',
            default    => '<span class="badge badge-secondary">Unknown</span>',
        };
        return $reg;
    }
}
