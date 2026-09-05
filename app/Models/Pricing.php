<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Models;

use Faizal\LaundryLms\Core\Database;
use PDO;

class Pricing {
    public static function all(): array {
        $db = Database::getConnection();
        $sql = "SELECT p.*, i.name AS item_name, w.name AS work_type_name
                FROM pricing p
                JOIN laundry_items i ON p.item_id = i.id
                JOIN work_types w ON p.work_type_id = w.id
                WHERE i.deleted_at IS NULL AND w.deleted_at IS NULL
                ORDER BY i.name ASC, w.name ASC";
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }

    public static function find(int $itemId, int $workTypeId): ?array {
        $db = Database::getConnection();
        $sql = "SELECT p.*, i.name AS item_name, w.name AS work_type_name
                FROM pricing p
                JOIN laundry_items i ON p.item_id = i.id
                JOIN work_types w ON p.work_type_id = w.id
                WHERE p.item_id = :item_id AND p.work_type_id = :work_type_id
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['item_id' => $itemId, 'work_type_id' => $workTypeId]);
        $pricing = $stmt->fetch();
        return $pricing ?: null;
    }

    public static function exists(int $itemId, int $workTypeId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM pricing WHERE item_id = :item_id AND work_type_id = :work_type_id");
        $stmt->execute(['item_id' => $itemId, 'work_type_id' => $workTypeId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function create(int $itemId, int $workTypeId, string $pricingType, float $price): bool {
        $db = Database::getConnection();
        $sql = "INSERT INTO pricing (item_id, work_type_id, pricing_type, price)
                VALUES (:item_id, :work_type_id, :pricing_type, :price)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'item_id'      => $itemId,
            'work_type_id' => $workTypeId,
            'pricing_type' => $pricingType,
            'price'        => $price
        ]);
    }

    public static function update(int $itemId, int $workTypeId, string $pricingType, float $price): bool {
        $db = Database::getConnection();
        $sql = "UPDATE pricing
                SET pricing_type = :pricing_type, price = :price
                WHERE item_id = :item_id AND work_type_id = :work_type_id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'pricing_type' => $pricingType,
            'price'        => $price,
            'item_id'      => $itemId,
            'work_type_id' => $workTypeId
        ]);
    }

    public static function delete(int $itemId, int $workTypeId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM pricing WHERE item_id = :item_id AND work_type_id = :work_type_id");
        return $stmt->execute(['item_id' => $itemId, 'work_type_id' => $workTypeId]);
    }
}
