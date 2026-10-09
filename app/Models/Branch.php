<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Models;

use Faizal\LaundryLms\Core\Database;
use PDO;

class Branch {
    /**
     * Get all branches (optionally filtered by search term).
     */
    public static function all(?string $search = null, bool $includeDeleted = false): array {
        $db = Database::getConnection();
        $conditions = [];
        $params = [];

        if (!$includeDeleted) {
            $conditions[] = "deleted_at IS NULL";
        }

        if (!empty($search)) {
            $term = '%' . trim($search) . '%';
            $conditions[] = "(name LIKE :term1 OR code LIKE :term2 OR phone LIKE :term3)";
            $params['term1'] = $term;
            $params['term2'] = $term;
            $params['term3'] = $term;
        }

        $where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        $sql = "SELECT * FROM branches {$where} ORDER BY is_active DESC, name ASC";

        if (!empty($params)) {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all active and non-deleted branches.
     */
    public static function allActive(): array {
        $db = Database::getConnection();
        $sql = "SELECT * FROM branches WHERE is_active = 1 AND deleted_at IS NULL ORDER BY name ASC";
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a branch by ID (non-deleted).
     */
    public static function find(int $id): ?array {
        if ($id <= 0) {
            return null;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM branches WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Find an active non-deleted branch by ID.
     */
    public static function findActive(int $id): ?array {
        if ($id <= 0) {
            return null;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM branches WHERE id = :id AND is_active = 1 AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Find a branch by its unique code.
     */
    public static function findByCode(string $code, ?int $excludeId = null): ?array {
        $cleanCode = strtoupper(trim($code));
        if (empty($cleanCode)) {
            return null;
        }
        $db = Database::getConnection();
        if ($excludeId !== null && $excludeId > 0) {
            $stmt = $db->prepare("SELECT * FROM branches WHERE code = :code AND id != :exclude_id AND deleted_at IS NULL LIMIT 1");
            $stmt->execute(['code' => $cleanCode, 'exclude_id' => $excludeId]);
        } else {
            $stmt = $db->prepare("SELECT * FROM branches WHERE code = :code AND deleted_at IS NULL LIMIT 1");
            $stmt->execute(['code' => $cleanCode]);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get the authoritative default active branch.
     * Prevents hardcoding branch_id = 1 throughout application code.
     */
    public static function getDefault(): ?array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM branches WHERE is_active = 1 AND deleted_at IS NULL ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get the default branch ID. Falls back safely if needed.
     */
    public static function getDefaultId(): int {
        $default = self::getDefault();
        if ($default) {
            return (int)$default['id'];
        }
        // Fallback to any branch if all are deactivated
        $db = Database::getConnection();
        $stmt = $db->query("SELECT id FROM branches ORDER BY id ASC LIMIT 1");
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : 1;
    }

    /**
     * Check if any orders reference this branch.
     */
    public static function hasOrders(int $id): bool {
        if ($id <= 0) {
            return false;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE branch_id = :id");
        $stmt->execute(['id' => $id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Check if any staff members are assigned to this branch.
     */
    public static function hasStaff(int $id): bool {
        if ($id <= 0) {
            return false;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE branch_id = :id AND deleted_at IS NULL");
        $stmt->execute(['id' => $id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Create a new branch.
     */
    public static function create(array $data): int {
        $name    = trim((string)($data['name'] ?? ''));
        $code    = strtoupper(trim((string)($data['code'] ?? '')));
        $phone   = !empty($data['phone']) ? trim((string)$data['phone']) : null;
        $address = !empty($data['address']) ? trim((string)$data['address']) : null;
        $active  = isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1;

        if (empty($name)) {
            throw new \InvalidArgumentException("Branch name is required.");
        }
        if (empty($code)) {
            throw new \InvalidArgumentException("Branch code is required.");
        }
        if (!preg_match('/^[A-Z0-9_-]{2,20}$/', $code)) {
            throw new \InvalidArgumentException("Branch code must be 2-20 alphanumeric characters (uppercase, digits, hyphens, underscores).");
        }
        if (self::findByCode($code)) {
            throw new \InvalidArgumentException("Branch code '{$code}' is already in use.");
        }

        $workingHours = isset($data['working_hours']) ? trim((string)$data['working_hours']) : null;
        $workingHours = ($workingHours !== null && $workingHours !== '') ? $workingHours : null;
        if ($workingHours !== null && mb_strlen($workingHours) > 100) {
            throw new \InvalidArgumentException("Working hours cannot exceed 100 characters.");
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO branches (name, code, phone, address, working_hours, is_active)
            VALUES (:name, :code, :phone, :address, :working_hours, :is_active)
        ");
        $stmt->execute([
            'name'          => $name,
            'code'          => $code,
            'phone'         => $phone,
            'address'       => $address,
            'working_hours' => $workingHours,
            'is_active'     => $active
        ]);

        return (int)$db->lastInsertId();
    }

    /**
     * Update an existing branch.
     */
    public static function update(int $id, array $data): bool {
        if ($id <= 0) {
            throw new \InvalidArgumentException("Invalid branch ID.");
        }
        $existing = self::find($id);
        if (!$existing) {
            throw new \InvalidArgumentException("Branch not found.");
        }

        $name    = trim((string)($data['name'] ?? ''));
        $code    = strtoupper(trim((string)($data['code'] ?? '')));
        $phone   = !empty($data['phone']) ? trim((string)$data['phone']) : null;
        $address = !empty($data['address']) ? trim((string)$data['address']) : null;
        $active  = isset($data['is_active']) ? (int)(bool)$data['is_active'] : (int)$existing['is_active'];

        if (empty($name)) {
            throw new \InvalidArgumentException("Branch name cannot be empty.");
        }
        if (empty($code)) {
            throw new \InvalidArgumentException("Branch code cannot be empty.");
        }
        if (!preg_match('/^[A-Z0-9_-]{2,20}$/', $code)) {
            throw new \InvalidArgumentException("Branch code must be 2-20 alphanumeric characters.");
        }

        $dup = self::findByCode($code, $id);
        if ($dup) {
            throw new \InvalidArgumentException("Branch code '{$code}' is already in use by another branch.");
        }

        $workingHours = isset($data['working_hours']) ? trim((string)$data['working_hours']) : null;
        $workingHours = ($workingHours !== null && $workingHours !== '') ? $workingHours : null;
        if ($workingHours !== null && mb_strlen($workingHours) > 100) {
            throw new \InvalidArgumentException("Working hours cannot exceed 100 characters.");
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE branches
            SET name = :name, code = :code, phone = :phone, address = :address, working_hours = :working_hours, is_active = :is_active, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND deleted_at IS NULL
        ");
        return $stmt->execute([
            'id'            => $id,
            'name'          => $name,
            'code'          => $code,
            'phone'         => $phone,
            'address'       => $address,
            'working_hours' => $workingHours,
            'is_active'     => $active
        ]);
    }

    /**
     * Toggle active/inactive status.
     */
    public static function toggleStatus(int $id): bool {
        if ($id <= 0) {
            return false;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE branches
            SET is_active = IF(is_active = 1, 0, 1), updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND deleted_at IS NULL
        ");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Soft delete branch. Rejects deletion if orders are attached.
     */
    public static function delete(int $id): bool {
        if ($id <= 0) {
            return false;
        }
        if (self::hasOrders($id)) {
            throw new \DomainException("Cannot delete branch: Historical orders reference this branch. Deactivate the branch instead.");
        }
        if (self::hasStaff($id)) {
            throw new \DomainException("Cannot delete branch: Staff members are currently assigned to this branch. Reassign them first.");
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE branches
            SET deleted_at = CURRENT_TIMESTAMP, is_active = 0
            WHERE id = :id AND deleted_at IS NULL
        ");
        return $stmt->execute(['id' => $id]);
    }
}
