<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Models;

use Faizal\LaundryLms\Core\Database;
use PDO;

class LaundryItem {
    public static function all(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM laundry_items WHERE deleted_at IS NULL ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM laundry_items WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    public static function findByName(string $name, ?int $excludeId = null): ?array {
        $db = Database::getConnection();
        if ($excludeId !== null) {
            $stmt = $db->prepare("SELECT * FROM laundry_items WHERE LOWER(name) = LOWER(:name) AND id != :exclude_id AND deleted_at IS NULL LIMIT 1");
            $stmt->execute(['name' => trim($name), 'exclude_id' => $excludeId]);
        } else {
            $stmt = $db->prepare("SELECT * FROM laundry_items WHERE LOWER(name) = LOWER(:name) AND deleted_at IS NULL LIMIT 1");
            $stmt->execute(['name' => trim($name)]);
        }
        $item = $stmt->fetch();
        return $item ?: null;
    }

    public static function create(string $name): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO laundry_items (name) VALUES (:name)");
        $stmt->execute(['name' => trim($name)]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, string $name): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE laundry_items SET name = :name WHERE id = :id AND deleted_at IS NULL");
        return $stmt->execute(['name' => trim($name), 'id' => $id]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            // Soft-delete item
            $stmt = $db->prepare("UPDATE laundry_items SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute(['id' => $id]);

            // Also clean up pricing entries linked to this item
            $stmtPricing = $db->prepare("DELETE FROM pricing WHERE item_id = :id");
            $stmtPricing->execute(['id' => $id]);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
