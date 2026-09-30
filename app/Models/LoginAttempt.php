<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Models;

use Faizal\LaundryLms\Core\Database;
use PDO;

class LoginAttempt {
    public const MAX_ATTEMPTS = 5;
    public const WINDOW_MINUTES = 15;

    /**
     * Resolve the client IP address safely.
     */
    public static function getClientIp(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        return '127.0.0.1';
    }

    /**
     * Count failed authentication attempts within the active sliding window for a mobile + IP pair.
     */
    public static function countRecentFailures(string $mobile, ?string $ip = null): int {
        $ipAddress = $ip ?? self::getClientIp();
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts 
                              WHERE mobile = :mobile AND ip_address = :ip 
                              AND attempted_at >= (NOW() - INTERVAL :window MINUTE)");
        $stmt->bindValue(':mobile', $mobile);
        $stmt->bindValue(':ip', $ipAddress);
        $stmt->bindValue(':window', self::WINDOW_MINUTES, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Check if the mobile + IP combination is currently locked out.
     */
    public static function isLocked(string $mobile, ?string $ip = null): bool {
        return self::countRecentFailures($mobile, $ip) >= self::MAX_ATTEMPTS;
    }

    /**
     * Record a failed login attempt.
     */
    public static function recordFailure(string $mobile, ?string $ip = null): void {
        $ipAddress = $ip ?? self::getClientIp();
        $db = Database::getConnection();

        $stmt = $db->prepare("INSERT INTO login_attempts (ip_address, mobile, attempted_at) VALUES (:ip, :mobile, CURRENT_TIMESTAMP)");
        $stmt->execute([
            'ip'     => $ipAddress,
            'mobile' => $mobile
        ]);

        // Probabilistic cleanup of expired records (older than 24 hours)
        if (mt_rand(1, 20) === 1) {
            self::pruneOldAttempts($db);
        }
    }

    /**
     * Clear failed attempts for a mobile + IP pair upon successful authentication.
     */
    public static function clearAttempts(string $mobile, ?string $ip = null): void {
        $ipAddress = $ip ?? self::getClientIp();
        $db = Database::getConnection();

        $stmt = $db->prepare("DELETE FROM login_attempts WHERE mobile = :mobile AND ip_address = :ip");
        $stmt->execute([
            'mobile' => $mobile,
            'ip'     => $ipAddress
        ]);
    }

    /**
     * Delete expired login attempt records older than the specified retention window (default 24 hours).
     */
    public static function pruneOldAttempts(?PDO $db = null, int $retentionHours = 24): int {
        $db = $db ?? Database::getConnection();

        $stmt = $db->prepare("DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL :hours HOUR)");
        $stmt->bindValue(':hours', $retentionHours, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    public const REGISTRATION_MARKER = '__REGISTER__';
    public const MAX_REGISTRATION_ATTEMPTS = 5;
    public const REGISTRATION_WINDOW_MINUTES = 15;

    /**
     * Count registration attempts within the rolling window for an IP address.
     */
    public static function countRecentRegistrationAttempts(?string $ip = null): int {
        $ipAddress = $ip ?? self::getClientIp();
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts 
                              WHERE mobile = :marker AND ip_address = :ip 
                              AND attempted_at >= (NOW() - INTERVAL :window MINUTE)");
        $stmt->bindValue(':marker', self::REGISTRATION_MARKER);
        $stmt->bindValue(':ip', $ipAddress);
        $stmt->bindValue(':window', self::REGISTRATION_WINDOW_MINUTES, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Check if registration is locked for the client IP.
     */
    public static function isRegistrationLocked(?string $ip = null): bool {
        return self::countRecentRegistrationAttempts($ip) >= self::MAX_REGISTRATION_ATTEMPTS;
    }

    /**
     * Record a registration attempt for the client IP.
     */
    public static function recordRegistrationAttempt(?string $ip = null): void {
        $ipAddress = $ip ?? self::getClientIp();
        $db = Database::getConnection();

        $stmt = $db->prepare("INSERT INTO login_attempts (ip_address, mobile, attempted_at) VALUES (:ip, :marker, CURRENT_TIMESTAMP)");
        $stmt->execute([
            'ip'     => $ipAddress,
            'marker' => self::REGISTRATION_MARKER
        ]);

        // Probabilistic cleanup of expired records (older than 24 hours)
        if (mt_rand(1, 20) === 1) {
            self::pruneOldAttempts($db);
        }
    }
}
