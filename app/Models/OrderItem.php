<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Models;

use Faizal\LaundryLms\Core\Database;
use PDO;

class OrderItem {
    public const FROZEN_STATUSES = [
        'price_confirmed',
        'processing',
        'ready',
        'out_for_delivery',
        'delivered',
        'completed'
    ];

    /**
     * Check whether an order's items are frozen against mutation.
     */
    public static function assertNotFrozen(int $orderId, ?PDO $db = null): void {
        $db = $db ?? Database::getConnection();
        $stmt = $db->prepare("SELECT status FROM orders WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $orderId]);
        $status = $stmt->fetchColumn();

        if ($status && in_array((string)$status, self::FROZEN_STATUSES, true)) {
            throw new \DomainException("Order #{$orderId} price is permanently locked (status: '{$status}'). Order items cannot be modified.");
        }
    }

    /**
     * Get all structured items for an order, ordered deterministically by display_order ASC, id ASC.
     * Joins laundry_items and work_types for names.
     */
    public static function getByOrderId(int $orderId): array {
        $db = Database::getConnection();
        $sql = "SELECT oi.*, li.name AS item_name, wt.name AS work_type_name
                FROM order_items oi
                JOIN laundry_items li ON oi.item_id = li.id
                JOIN work_types wt ON oi.work_type_id = wt.id
                WHERE oi.order_id = :order_id
                ORDER BY oi.display_order ASC, oi.id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetchAll();
    }

    /**
     * Insert an array of order lines for an order.
     * Enforces model-level price freeze check.
     */
    public static function insertLines(int $orderId, array $items, ?PDO $db = null): bool {
        $db = $db ?? Database::getConnection();
        self::assertNotFrozen($orderId, $db);

        $sql = "INSERT INTO order_items 
                (order_id, item_id, work_type_id, quantity, unit_price, pricing_type, subtotal, item_notes, display_order)
                VALUES 
                (:order_id, :item_id, :work_type_id, :quantity, :unit_price, :pricing_type, :subtotal, :item_notes, :display_order)";
        $stmt = $db->prepare($sql);

        $displayOrder = 1;
        foreach ($items as $item) {
            $stmt->execute([
                'order_id'      => $orderId,
                'item_id'       => (int)$item['item_id'],
                'work_type_id'  => (int)$item['work_type_id'],
                'quantity'      => round((float)($item['quantity'] ?? 1), 2),
                'unit_price'    => round((float)($item['unit_price'] ?? 0), 2),
                'pricing_type'  => (($item['pricing_type'] ?? '') === 'per_kg') ? 'per_kg' : 'per_item',
                'subtotal'      => round((float)($item['subtotal'] ?? 0), 2),
                'item_notes'    => !empty($item['item_notes']) ? trim((string)$item['item_notes']) : null,
                'display_order' => isset($item['display_order']) ? (int)$item['display_order'] : $displayOrder++
            ]);
        }

        return true;
    }

    /**
     * Delete all items for an order.
     * Enforces model-level price freeze check.
     */
    public static function deleteByOrderId(int $orderId, ?PDO $db = null): bool {
        $db = $db ?? Database::getConnection();
        self::assertNotFrozen($orderId, $db);

        $stmt = $db->prepare("DELETE FROM order_items WHERE order_id = :order_id");
        return $stmt->execute(['order_id' => $orderId]);
    }

    /**
     * Replace all items for an order with a new validated set.
     * Enforces model-level price freeze check.
     */
    public static function replaceForOrder(int $orderId, array $items, ?PDO $db = null): bool {
        $db = $db ?? Database::getConnection();
        self::assertNotFrozen($orderId, $db);

        // Delete existing items
        $delStmt = $db->prepare("DELETE FROM order_items WHERE order_id = :order_id");
        $delStmt->execute(['order_id' => $orderId]);

        // Insert new items
        return self::insertLines($orderId, $items, $db);
    }
}
