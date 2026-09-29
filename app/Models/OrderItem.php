<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Models;

use Faizal\LaundryLms\Core\Database;
use Faizal\LaundryLms\Models\LaundryItem;
use Faizal\LaundryLms\Models\WorkType;
use Faizal\LaundryLms\Models\Pricing;
use PDO;

class OrderItem {
    public const FROZEN_STATUSES = [
        'price_confirmed',
        'processing',
        'ready',
        'out_for_delivery',
        'delivered',
        'completed',
        'cancelled'
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

    /**
     * Validate and normalize raw multi-item line inputs.
     *
     * Validates item existence, work type existence, active pricing,
     * duplicate item/work pairs, quantity constraints, and computes authoritative subtotals.
     *
     * @param array $rawLines List of raw item line inputs
     * @return array{lines: array, subtotal: float} Validated lines with calculated subtotals and aggregate subtotal
     * @throws \InvalidArgumentException On any validation error or empty line list
     */
    public static function validateLines(array $rawLines): array {
        $validatedLines = [];
        $itemsSubtotal  = 0.00;
        $seenKeys       = [];
        $displayOrder   = 1;

        foreach ($rawLines as $line) {
            if (!is_array($line)) {
                continue;
            }

            $itemId     = (int)($line['item_id'] ?? 0);
            $workTypeId = (int)($line['work_type_id'] ?? 0);
            $rawQty     = $line['quantity'] ?? null;

            // Skip completely empty form lines
            if ($itemId <= 0 && $workTypeId <= 0 && ($rawQty === null || $rawQty === '')) {
                continue;
            }

            // 1. Check duplicate item and work type combination
            $pairKey = "{$itemId}_{$workTypeId}";
            if (isset($seenKeys[$pairKey])) {
                throw new \InvalidArgumentException("Duplicate item and work type combination detected. Each item and service treatment must be unique per order.");
            }
            $seenKeys[$pairKey] = true;

            // 2. Validate laundry item
            $itemObj = LaundryItem::find($itemId);
            if (!$itemObj) {
                throw new \InvalidArgumentException("Selected laundry item is invalid or soft-deleted.");
            }

            // 3. Validate work type
            $workTypeObj = WorkType::find($workTypeId);
            if (!$workTypeObj) {
                throw new \InvalidArgumentException("Selected work type is invalid or soft-deleted.");
            }

            // 4. Validate pricing configuration
            $pricing = Pricing::find($itemId, $workTypeId);
            if (!$pricing) {
                throw new \InvalidArgumentException("No valid pricing configuration exists for '{$itemObj['name']}' and '{$workTypeObj['name']}'.");
            }

            // 5. Validate quantity / weight
            if (!is_numeric($rawQty) || (float)$rawQty <= 0) {
                throw new \InvalidArgumentException("Quantity or weight must be a valid number greater than zero.");
            }
            if ((float)$rawQty > 1000) {
                throw new \InvalidArgumentException("Quantity or weight cannot exceed 1,000.");
            }

            $pricingType = $pricing['pricing_type'];
            if ($pricingType === 'per_item') {
                if (floor((float)$rawQty) != (float)$rawQty) {
                    throw new \InvalidArgumentException("Quantity for per-item services must be a whole positive integer.");
                }
                $quantity = (float)floor((float)$rawQty);
            } else {
                $quantity = round((float)$rawQty, 2);
            }

            // 6. Authoritative server-side subtotal calculation (browser-supplied unit_price or subtotal NEVER trusted)
            $unitPrice    = (float)$pricing['price'];
            $lineSubtotal = round($unitPrice * $quantity, 2);
            $itemsSubtotal += $lineSubtotal;

            $lineNote = !empty($line['item_notes']) ? trim((string)$line['item_notes']) : (!empty($line['notes']) ? trim((string)$line['notes']) : null);

            $validatedLines[] = [
                'item_id'        => $itemId,
                'item_name'      => $itemObj['name'],
                'work_type_id'   => $workTypeId,
                'work_type_name' => $workTypeObj['name'],
                'quantity'       => $quantity,
                'unit_price'     => $unitPrice,
                'pricing_type'   => $pricingType,
                'subtotal'       => $lineSubtotal,
                'item_notes'     => $lineNote,
                'display_order'  => $displayOrder++
            ];
        }

        if (empty($validatedLines)) {
            throw new \InvalidArgumentException("Please add at least one valid laundry item and service.");
        }

        return [
            'lines'    => $validatedLines,
            'subtotal' => round($itemsSubtotal, 2)
        ];
    }
}
