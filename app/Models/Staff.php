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
            $sql = "SELECT u.id, u.role, u.branch_id, u.name, u.mobile, u.is_active, u.created_at, u.updated_at,
                           b.name AS branch_name, b.code AS branch_code
                    FROM users u
                    LEFT JOIN branches b ON u.branch_id = b.id
                    WHERE u.role = 'staff' AND u.deleted_at IS NULL AND (u.name LIKE :term1 OR u.mobile LIKE :term2)
                    ORDER BY u.name ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute(['term1' => $term, 'term2' => $term]);
        } else {
            $sql = "SELECT u.id, u.role, u.branch_id, u.name, u.mobile, u.is_active, u.created_at, u.updated_at,
                           b.name AS branch_name, b.code AS branch_code
                    FROM users u
                    LEFT JOIN branches b ON u.branch_id = b.id
                    WHERE u.role = 'staff' AND u.deleted_at IS NULL
                    ORDER BY u.name ASC";
            $stmt = $db->query($sql);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all active staff accounts, optionally prioritizing or filtering by branch.
     */
    public static function allActive(?int $branchId = null): array {
        $db = Database::getConnection();
        if ($branchId !== null && $branchId > 0) {
            $stmt = $db->prepare("SELECT u.id, u.role, u.branch_id, u.name, u.mobile, u.is_active,
                                         b.name AS branch_name, b.code AS branch_code
                                  FROM users u
                                  LEFT JOIN branches b ON u.branch_id = b.id
                                  WHERE u.role = 'staff' AND u.deleted_at IS NULL AND u.is_active = 1 AND u.branch_id = :branch_id
                                  ORDER BY u.name ASC");
            $stmt->execute(['branch_id' => $branchId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                return $rows;
            }
        }

        // Return all active staff across branches (or fallback if branch has no staff)
        $stmt = $db->query("SELECT u.id, u.role, u.branch_id, u.name, u.mobile, u.is_active,
                                   b.name AS branch_name, b.code AS branch_code
                            FROM users u
                            LEFT JOIN branches b ON u.branch_id = b.id
                            WHERE u.role = 'staff' AND u.deleted_at IS NULL AND u.is_active = 1
                            ORDER BY u.name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a staff account by ID (strictly role = 'staff').
     */
    public static function find(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT u.id, u.role, u.branch_id, u.name, u.mobile, u.is_active, u.created_at, u.updated_at,
                                     b.name AS branch_name, b.code AS branch_code
                              FROM users u
                              LEFT JOIN branches b ON u.branch_id = b.id
                              WHERE u.id = :id AND u.role = 'staff' AND u.deleted_at IS NULL
                              LIMIT 1");
        $stmt->execute(['id' => $id]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        return $staff ?: null;
    }

    /**
     * Find any user with given mobile to check for uniqueness across all active/non-deleted accounts.
     */
    public static function findByMobile(string $mobile, ?int $excludeId = null): ?array {
        $db = Database::getConnection();
        if ($excludeId !== null) {
            $stmt = $db->prepare("SELECT id, role, branch_id, name, mobile FROM users WHERE mobile = :mobile AND id != :exclude_id AND deleted_at IS NULL LIMIT 1");
            $stmt->execute(['mobile' => trim($mobile), 'exclude_id' => $excludeId]);
        } else {
            $stmt = $db->prepare("SELECT id, role, branch_id, name, mobile FROM users WHERE mobile = :mobile AND deleted_at IS NULL LIMIT 1");
            $stmt->execute(['mobile' => trim($mobile)]);
        }
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Create a new staff account. Role is strictly forced to 'staff'.
     */
    public static function create(array $data): int {
        $db = Database::getConnection();
        $pin = trim($data['pin'] ?? '');
        $pinHash = password_hash($pin, PASSWORD_DEFAULT);

        $branchId = !empty($data['branch_id']) ? (int)$data['branch_id'] : Branch::getDefaultId();
        if ($branchId <= 0 || !Branch::findActive($branchId)) {
            throw new \InvalidArgumentException("A valid active branch assignment is required for staff.");
        }

        $sql = "INSERT INTO users (role, branch_id, name, mobile, pin_hash, is_active)
                VALUES ('staff', :branch_id, :name, :mobile, :pin_hash, :is_active)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'branch_id' => $branchId,
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
        $existing = self::find($id);
        if (!$existing) {
            return false;
        }

        $branchId = isset($data['branch_id']) ? (int)$data['branch_id'] : (int)($existing['branch_id'] ?? Branch::getDefaultId());
        if ($branchId <= 0 || !Branch::findActive($branchId)) {
            throw new \InvalidArgumentException("A valid active branch assignment is required.");
        }

        $params = [
            'id'        => $id,
            'branch_id' => $branchId,
            'name'      => trim($data['name'] ?? ''),
            'mobile'    => trim($data['mobile'] ?? ''),
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
        ];

        if (!empty($data['pin'])) {
            $sql = "UPDATE users
                    SET branch_id = :branch_id, name = :name, mobile = :mobile, pin_hash = :pin_hash, is_active = :is_active, updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id AND role = 'staff' AND deleted_at IS NULL";
            $params['pin_hash'] = password_hash(trim($data['pin']), PASSWORD_DEFAULT);
        } else {
            $sql = "UPDATE users
                    SET branch_id = :branch_id, name = :name, mobile = :mobile, is_active = :is_active, updated_at = CURRENT_TIMESTAMP
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
