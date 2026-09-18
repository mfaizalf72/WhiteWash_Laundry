<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Models;

use Faizal\LaundryLms\Core\Database;
use PDO;

class Staff {
    /**
     * Get all staff accounts (never exposes pin_hash).
     */
    public static function all(?string $search = null): array {
        $db = Database::getConnection();
        if (!empty($search)) {
            $term = '%' . trim($search) . '%';
            $sql = "SELECT id, role, name, mobile, is_active, created_at, updated_at
                    FROM users
                    WHERE role = 'staff' AND deleted_at IS NULL AND (name LIKE :term1 OR mobile LIKE :term2)
                    ORDER BY name ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute(['term1' => $term, 'term2' => $term]);
        } else {
            $sql = "SELECT id, role, name, mobile, is_active, created_at, updated_at
                    FROM users
                    WHERE role = 'staff' AND deleted_at IS NULL
                    ORDER BY name ASC";
            $stmt = $db->query($sql);
        }
        return $stmt->fetchAll();
    }

    /**
     * Find a staff account by ID (strictly role = 'staff').
     */
    public static function find(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, role, name, mobile, is_active, created_at, updated_at 
                              FROM users 
                              WHERE id = :id AND role = 'staff' AND deleted_at IS NULL 
                              LIMIT 1");
        $stmt->execute(['id' => $id]);
        $staff = $stmt->fetch();
        return $staff ?: null;
    }

    /**
     * Find any user with given mobile to check for uniqueness across all active/non-deleted accounts.
     */
    public static function findByMobile(string $mobile, ?int $excludeId = null): ?array {
        $db = Database::getConnection();
        if ($excludeId !== null) {
            $stmt = $db->prepare("SELECT id, role, name, mobile FROM users WHERE mobile = :mobile AND id != :exclude_id AND deleted_at IS NULL LIMIT 1");
            $stmt->execute(['mobile' => trim($mobile), 'exclude_id' => $excludeId]);
        } else {
            $stmt = $db->prepare("SELECT id, role, name, mobile FROM users WHERE mobile = :mobile AND deleted_at IS NULL LIMIT 1");
            $stmt->execute(['mobile' => trim($mobile)]);
        }
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Create a new staff account. Role is strictly forced to 'staff'.
     */
    public static function create(array $data): int {
        $db = Database::getConnection();
        $pin = trim($data['pin'] ?? '');
        $pinHash = password_hash($pin, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (role, name, mobile, pin_hash, is_active)
                VALUES ('staff', :name, :mobile, :pin_hash, :is_active)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'name'      => trim($data['name'] ?? ''),
            'mobile'    => trim($data['mobile'] ?? ''),
            'pin_hash'  => $pinHash,
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * Update an existing staff account. Strictly restricted to role = 'staff'.
     */
    public static function update(int $id, array $data): bool {
        $db = Database::getConnection();
        $params = [
            'id'        => $id,
            'name'      => trim($data['name'] ?? ''),
            'mobile'    => trim($data['mobile'] ?? ''),
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
        ];

        if (!empty($data['pin'])) {
            $sql = "UPDATE users
                    SET name = :name, mobile = :mobile, pin_hash = :pin_hash, is_active = :is_active, updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id AND role = 'staff' AND deleted_at IS NULL";
            $params['pin_hash'] = password_hash(trim($data['pin']), PASSWORD_DEFAULT);
        } else {
            $sql = "UPDATE users
                    SET name = :name, mobile = :mobile, is_active = :is_active, updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id AND role = 'staff' AND deleted_at IS NULL";
        }

        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Toggle active/inactive status. Strictly restricted to role = 'staff'.
     */
    public static function toggleStatus(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users 
                              SET is_active = IF(is_active = 1, 0, 1), updated_at = CURRENT_TIMESTAMP 
                              WHERE id = :id AND role = 'staff' AND deleted_at IS NULL");
        return $stmt->execute(['id' => $id]);
    }
}
