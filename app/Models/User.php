<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Models;

use Faizal\LaundryLms\Core\Database;

class User {
    public static function findByMobile(string $mobile): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE mobile = :mobile AND is_active = 1 AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(['mobile' => $mobile]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function find(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    // Temporary helper to create the first admin user
    public static function createInitialAdmin(): void {
        $db = Database::getConnection();
        // Check if admin already exists
        $stmt = $db->query("SELECT id FROM users WHERE mobile = '9999999999'");
        if (!$stmt->fetch()) {
            $hash = password_hash('1234', PASSWORD_DEFAULT);
            $insert = $db->prepare("INSERT INTO users (role, name, mobile, pin_hash) VALUES ('admin', 'System Admin', '9999999999', :hash)");
            $insert->execute(['hash' => $hash]);
        }
    }
}