<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Models;

use Faizal\LaundryLms\Core\Database;
use PDO;

class Customer {
    public static function all(?string $search = null): array {
        $db = Database::getConnection();
        if (!empty($search)) {
            $term = '%' . trim($search) . '%';
            $sql = "SELECT * FROM users
                    WHERE role = 'customer' AND deleted_at IS NULL AND (name LIKE :term1 OR mobile LIKE :term2)
                    ORDER BY name ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute(['term1' => $term, 'term2' => $term]);
        } else {
            $sql = "SELECT * FROM users
                    WHERE role = 'customer' AND deleted_at IS NULL
                    ORDER BY name ASC";
            $stmt = $db->query($sql);
        }
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND role = 'customer' AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(['id' => $id]);
        $customer = $stmt->fetch();
        return $customer ?: null;
    }

    public static function findByMobile(string $mobile, ?int $excludeId = null): ?array {
        $db = Database::getConnection();
        if ($excludeId !== null) {
            $stmt = $db->prepare("SELECT * FROM users WHERE mobile = :mobile AND id != :exclude_id AND deleted_at IS NULL LIMIT 1");
            $stmt->execute(['mobile' => trim($mobile), 'exclude_id' => $excludeId]);
        } else {
            $stmt = $db->prepare("SELECT * FROM users WHERE mobile = :mobile AND deleted_at IS NULL LIMIT 1");
            $stmt->execute(['mobile' => trim($mobile)]);
        }
        $customer = $stmt->fetch();
        return $customer ?: null;
    }

    public static function create(array $data): int {
        $db = Database::getConnection();
        $pin = !empty($data['pin']) ? trim($data['pin']) : '1234';
        $pinHash = password_hash($pin, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (role, name, mobile, pin_hash, address, is_active)
                VALUES ('customer', :name, :mobile, :pin_hash, :address, :is_active)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'name'      => trim($data['name'] ?? ''),
            'mobile'    => trim($data['mobile'] ?? ''),
            'pin_hash'  => $pinHash,
            'address'   => !empty($data['address']) ? trim($data['address']) : null,
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getConnection();
        $params = [
            'id'        => $id,
            'name'      => trim($data['name'] ?? ''),
            'mobile'    => trim($data['mobile'] ?? ''),
            'address'   => !empty($data['address']) ? trim($data['address']) : null,
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
        ];

        if (!empty($data['pin'])) {
            $sql = "UPDATE users
                    SET name = :name, mobile = :mobile, pin_hash = :pin_hash, address = :address, is_active = :is_active
                    WHERE id = :id AND role = 'customer' AND deleted_at IS NULL";
            $params['pin_hash'] = password_hash(trim($data['pin']), PASSWORD_DEFAULT);
        } else {
            $sql = "UPDATE users
                    SET name = :name, mobile = :mobile, address = :address, is_active = :is_active
                    WHERE id = :id AND role = 'customer' AND deleted_at IS NULL";
        }

        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public static function hasOrders(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = :id");
        $stmt->execute(['id' => $id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        if (self::hasOrders($id)) {
            throw new \RuntimeException("Cannot delete customer because they have associated orders.");
        }
        $stmt = $db->prepare("UPDATE users SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND role = 'customer'");
        return $stmt->execute(['id' => $id]);
    }

    public static function updateProfile(int $id, array $data): bool {
        $db = Database::getConnection();
        $sql = "UPDATE users
                SET name = :name, mobile = :mobile, address = :address, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND role = 'customer' AND deleted_at IS NULL";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'id'      => $id,
            'name'    => trim($data['name'] ?? ''),
            'mobile'  => trim($data['mobile'] ?? ''),
            'address' => !empty($data['address']) ? trim($data['address']) : null
        ]);
    }

    public static function updatePin(int $id, string $newPin): bool {
        $db = Database::getConnection();
        $hash = password_hash($newPin, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET pin_hash = :hash, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL");
        return $stmt->execute(['hash' => $hash, 'id' => $id]);
    }
}
