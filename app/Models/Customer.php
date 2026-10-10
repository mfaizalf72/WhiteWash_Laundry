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
        $mustChangePin = ($pin === '1234') ? 1 : 0;

        $sql = "INSERT INTO users (role, name, mobile, pin_hash, address, is_active, must_change_pin, session_version)
                VALUES ('customer', :name, :mobile, :pin_hash, :address, :is_active, :must_change_pin, 1)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'name'            => trim($data['name'] ?? ''),
            'mobile'          => trim($data['mobile'] ?? ''),
            'pin_hash'        => $pinHash,
            'address'         => !empty($data['address']) ? trim($data['address']) : null,
            'is_active'       => isset($data['is_active']) ? (int)$data['is_active'] : 1,
            'must_change_pin' => $mustChangePin
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

        $sql = "UPDATE users
                SET name = :name, mobile = :mobile, address = :address, is_active = :is_active, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND role = 'customer' AND deleted_at IS NULL";

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
        $stmt = $db->prepare("UPDATE users
                              SET pin_hash = :hash,
                                  must_change_pin = 0,
                                  session_version = session_version + 1,
                                  updated_at = CURRENT_TIMESTAMP
                              WHERE id = :id AND deleted_at IS NULL");
        return $stmt->execute(['hash' => $hash, 'id' => $id]);
    }

    /**
     * Admin-initiated temporary PIN assignment.
     * Sets temporary pin hash, must_change_pin = 1, and increments session_version to invalidate old sessions.
     */
    public static function setTemporaryPin(int $id, string $tempPin): bool {
        $db = Database::getConnection();
        $hash = password_hash($tempPin, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users
                              SET pin_hash = :hash,
                                  must_change_pin = 1,
                                  session_version = session_version + 1,
                                  updated_at = CURRENT_TIMESTAMP
                              WHERE id = :id AND role = 'customer' AND deleted_at IS NULL");
        return $stmt->execute(['hash' => $hash, 'id' => $id]);
    }

    /**
     * Shared validator for new PIN choices.
     * Enforces numeric 4-digit format, confirmation match, and rejects weak/trivial PINs.
     *
     * @return string|null Error message string on failure, or null if valid.
     */
    public static function validatePinChoice(string $newPin, string $confirmPin, ?string $currentPinHash = null): ?string {
        if (strlen($newPin) !== 4 || !ctype_digit($newPin)) {
            return "PIN must be exactly 4 numeric digits.";
        }
        if ($newPin !== $confirmPin) {
            return "New PIN and confirmation PIN do not match.";
        }
        if ($newPin === '1234') {
            return "The default PIN '1234' is not allowed for security reasons.";
        }
        if ($newPin === '4321') {
            return "PIN '4321' is too obvious. Please choose a more secure PIN.";
        }
        if (preg_match('/^(\d)\1{3}$/', $newPin)) {
            return "PIN cannot consist of four identical digits (e.g. 0000, 1111).";
        }
        if (!empty($currentPinHash) && password_verify($newPin, $currentPinHash)) {
            return "New PIN cannot be the same as your temporary or current PIN. Please choose a different PIN.";
        }
        return null;
    }
}
