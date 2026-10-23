<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Models;

use Faizal\LaundryLms\Core\Database;
use Faizal\LaundryLms\Helpers\OrderMetadata;
use Faizal\LaundryLms\Models\Settings;
use Faizal\LaundryLms\Models\LaundryItem;
use Faizal\LaundryLms\Models\WorkType;
use Faizal\LaundryLms\Models\Pricing;
use Faizal\LaundryLms\Models\Branch;
use Faizal\LaundryLms\Services\Notification\NotificationService;
use PDO;

class Order {
    public static function generateJobId(): string {
        $db = Database::getConnection();
        $prefix = 'LND-' . date('ym') . '-';
        
        $sql = "SELECT job_id FROM orders WHERE job_id LIKE :prefix ORDER BY id DESC LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['prefix' => $prefix . '%']);
        $lastJobId = $stmt->fetchColumn();

        if ($lastJobId && preg_match('/' . preg_quote($prefix, '/') . '(\d{4})$/', $lastJobId, $matches)) {
            $nextSeq = (int)$matches[1] + 1;
        } else {
            $nextSeq = 1;
        }

        do {
            $jobId = $prefix . sprintf('%04d', $nextSeq);
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE job_id = :job_id");
            $checkStmt->execute(['job_id' => $jobId]);
            $exists = (int)$checkStmt->fetchColumn() > 0;
            if ($exists) {
                $nextSeq++;
            }
        } while ($exists);

        return $jobId;
    }

    public static function create(array $orderData, ?int $staffUserId = null): int {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $jobId = !empty($orderData['job_id']) ? $orderData['job_id'] : self::generateJobId();

            $branchId = !empty($orderData['branch_id']) ? (int)$orderData['branch_id'] : Branch::getDefaultId();
            if ($branchId <= 0 || !Branch::findActive($branchId)) {
                throw new \InvalidArgumentException("A valid active branch is required for this order.");
            }

            $sql = "INSERT INTO orders 
                    (job_id, branch_id, customer_id, staff_id, status, total_amount, amount_paid, payment_status, pickup_date, delivery_date, special_instructions)
                    VALUES 
                    (:job_id, :branch_id, :customer_id, :staff_id, :status, :total_amount, :amount_paid, :payment_status, :pickup_date, :delivery_date, :special_instructions)";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':job_id', $jobId);
            $stmt->bindValue(':branch_id', $branchId, PDO::PARAM_INT);
            $stmt->bindValue(':customer_id', $orderData['customer_id'], PDO::PARAM_INT);
            if ($staffUserId !== null && $staffUserId > 0) {
                $stmt->bindValue(':staff_id', $staffUserId, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':staff_id', null, PDO::PARAM_NULL);
            }
            $stmt->bindValue(':status', $orderData['status'] ?? 'pickup_request');
            $stmt->bindValue(':total_amount', $orderData['total_amount']);
            $stmt->bindValue(':amount_paid', $orderData['amount_paid'] ?? 0.00);
            $stmt->bindValue(':payment_status', $orderData['payment_status'] ?? 'unpaid');
            $stmt->bindValue(':pickup_date', $orderData['pickup_date']);
            if (!empty($orderData['delivery_date'])) {
                $stmt->bindValue(':delivery_date', $orderData['delivery_date']);
            } else {
                $stmt->bindValue(':delivery_date', null, PDO::PARAM_NULL);
            }
            if (!empty($orderData['special_instructions'])) {
                $stmt->bindValue(':special_instructions', $orderData['special_instructions']);
            } else {
                $stmt->bindValue(':special_instructions', null, PDO::PARAM_NULL);
            }
            $stmt->execute();

            $orderId = (int)$db->lastInsertId();

            // Insert line items if provided
            if (!empty($orderData['items']) && is_array($orderData['items'])) {
                OrderItem::insertLines($orderId, $orderData['items'], $db);
            }

            // Insert initial entry in order_status_history
            $histSql = "INSERT INTO order_status_history (order_id, old_status, new_status, updated_by)
                        VALUES (:order_id, NULL, :new_status, :updated_by)";
            $histStmt = $db->prepare($histSql);
            $histStmt->execute([
                'order_id'   => $orderId,
                'new_status' => $orderData['status'] ?? 'pickup_request',
                'updated_by' => $staffUserId ?? $orderData['customer_id']
            ]);

            $db->commit();

            // Phase 14A: Automated customer notification for new order
            try {
                NotificationService::notifyOrderCreated($orderId, (int)$orderData['customer_id'], (string)$jobId);
            } catch (\Throwable $ne) {
                error_log("Order created notification failed for order #{$orderId}: " . $ne->getMessage());
            }

            return $orderId;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function getItems(int $orderId): array {
        return OrderItem::getByOrderId($orderId);
    }

    /**
     * Compute pickup and delivery fee breakdown based on items subtotal and business settings.
     *
     * @param float $itemsSubtotal Items aggregate subtotal
     * @param array|null $settings Business settings array (optional, fetches if null)
     * @return array{pickup_charge: float, delivery_charge: float, free_pickup_limit: float, pickup_fee: float, delivery_fee: float, total_charges: float}
     */
    public static function getDeliveryFeeBreakdown(float $itemsSubtotal, ?array $settings = null): array {
        $settings = $settings ?? Settings::get();
        $pickupCharge    = (float)($settings['pickup_charge'] ?? 0);
        $deliveryCharge  = (float)($settings['delivery_charge'] ?? 0);
        $freePickupLimit = (float)($settings['free_pickup_limit'] ?? 0);

        $isChargeable = ($freePickupLimit <= 0 || $itemsSubtotal < $freePickupLimit);
        $pickupFee    = $isChargeable ? $pickupCharge : 0.00;
        $deliveryFee  = $isChargeable ? $deliveryCharge : 0.00;
        $totalCharges = round($pickupFee + $deliveryFee, 2);

        return [
            'pickup_charge'     => $pickupCharge,
            'delivery_charge'   => $deliveryCharge,
            'free_pickup_limit' => $freePickupLimit,
            'pickup_fee'        => $pickupFee,
            'delivery_fee'      => $deliveryFee,
            'total_charges'     => $totalCharges,
        ];
    }

    /**
     * Compute total delivery/pickup charges based on items subtotal and business settings.
     *
     * @param float $itemsSubtotal Items aggregate subtotal
     * @param array|null $settings Business settings array (optional, fetches if null)
     * @return float Total delivery/pickup charges (0.00 if threshold reached)
     */
    public static function calculateDeliveryCharges(float $itemsSubtotal, ?array $settings = null): float {
        return self::getDeliveryFeeBreakdown($itemsSubtotal, $settings)['total_charges'];
    }

    public static function all(array $filters = []): array {
        $db = Database::getConnection();
        $conditions = ["1=1"];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = "o.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $conditions[] = "(o.job_id LIKE :term1 OR c.name LIKE :term2 OR c.mobile LIKE :term3)";
            $params['term1'] = $term;
            $params['term2'] = $term;
            $params['term3'] = $term;
        }

        $whereClause = implode(" AND ", $conditions);
        $sql = "SELECT o.*, c.name AS customer_name, c.mobile AS customer_mobile
                FROM orders o
                JOIN users c ON o.customer_id = c.id
                WHERE {$whereClause}
                ORDER BY o.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $db = Database::getConnection();
        $sql = "SELECT o.*, c.name AS customer_name, c.mobile AS customer_mobile, c.address AS customer_address, s.name AS staff_name,
                       b.name AS branch_name, b.code AS branch_code, b.phone AS branch_phone, b.address AS branch_address,
                       b.working_hours AS branch_working_hours
                FROM orders o
                JOIN users c ON o.customer_id = c.id
                LEFT JOIN users s ON o.staff_id = s.id
                LEFT JOIN branches b ON o.branch_id = b.id
                WHERE o.id = :id
                LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $order = $stmt->fetch();
        return $order ?: null;
    }

    public static function getStatusHistory(int $orderId): array {
        $db = Database::getConnection();
        $sql = "SELECT h.*, u.name AS updated_by_name, u.role AS updated_by_role
                FROM order_status_history h
                LEFT JOIN users u ON h.updated_by = u.id
                WHERE h.order_id = :order_id
                ORDER BY h.id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetchAll();
    }

    public const VALID_STATUSES = [
        'pickup_request',
        'accepted',
        'picked_up',
        'inspection_completed',
        'price_confirmed',
        'processing',
        'ready',
        'out_for_delivery',
        'delivered',
        'completed',
        'cancelled'
    ];

    public const STATUS_SEQUENCE = [
        'pickup_request'       => 'accepted',
        'accepted'             => 'picked_up',
        'price_confirmed'      => 'processing',
        'processing'           => 'ready',
        'ready'                => 'out_for_delivery',
        'out_for_delivery'     => 'delivered',
        'delivered'            => 'completed',
    ];

    public static function getNextStatus(string $currentStatus): ?string {
        return self::STATUS_SEQUENCE[$currentStatus] ?? null;
    }

    public static function isValidTransition(string $currentStatus, string $newStatus): bool {
        return isset(self::STATUS_SEQUENCE[$currentStatus]) && self::STATUS_SEQUENCE[$currentStatus] === $newStatus;
    }

    public static function updateStatus(int $orderId, string $requestedNewStatus, int $userId, string $userRole): bool {
        if (!in_array($userRole, ['admin', 'staff'], true)) {
            throw new \DomainException("Unauthorized: Role '{$userRole}' is not permitted to change order status.");
        }

        if ($requestedNewStatus === 'inspection_completed') {
            throw new \DomainException("Transition to 'inspection_completed' must be performed via submitInspection().");
        }

        if ($requestedNewStatus === 'price_confirmed') {
            throw new \DomainException("Transition to 'price_confirmed' must be performed via confirmPriceForCustomer() or overridePriceApprovalByAdmin().");
        }

        if ($requestedNewStatus === 'cancelled') {
            throw new \DomainException("Transition to 'cancelled' must be performed via cancel() or rejectInspectedPrice().");
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            // Lock target row during status update
            $stmt = $db->prepare("SELECT id, job_id, customer_id, status, total_amount, amount_paid, payment_status FROM orders WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $db->rollBack();
                throw new \InvalidArgumentException("Order #{$orderId} not found.");
            }

            $currentStatus = $order['status'];

            if ($currentStatus === 'cancelled') {
                $db->rollBack();
                throw new \DomainException("Order #{$orderId} is cancelled and cannot be transitioned.");
            }

            if (!self::isValidTransition($currentStatus, $requestedNewStatus)) {
                $expectedNext = self::getNextStatus($currentStatus);
                $msg = $expectedNext 
                    ? "Invalid transition from '{$currentStatus}' to '{$requestedNewStatus}'. Next allowed status is '{$expectedNext}'."
                    : "Order #{$orderId} is already in final status '{$currentStatus}' and cannot be transitioned further.";
                $db->rollBack();
                throw new \DomainException($msg);
            }

            // Enforce Payment Settlement Rule for delivered -> completed transition
            if ($currentStatus === 'delivered' && $requestedNewStatus === 'completed') {
                $paid  = round((float)$order['amount_paid'], 2);
                $total = round((float)$order['total_amount'], 2);
                if ($order['payment_status'] !== 'paid' || $paid < $total - 0.001) {
                    $remaining = round($total - $paid, 2);
                    $db->rollBack();
                    throw new \DomainException("Cannot complete order #{$orderId}: Payment is unsettled. Remaining balance: ₹" . number_format($remaining, 2) . ". Payment status must be 'paid'.");
                }
            }

            // Update orders.status
            $updateStmt = $db->prepare("UPDATE orders SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $updateStmt->execute(['status' => $requestedNewStatus, 'id' => $orderId]);

            // Insert order_status_history entry
            $histStmt = $db->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, updated_by) VALUES (:order_id, :old_status, :new_status, :updated_by)");
            $histStmt->execute([
                'order_id'   => $orderId,
                'old_status' => $currentStatus,
                'new_status' => $requestedNewStatus,
                'updated_by' => $userId
            ]);

            $db->commit();

            // Phase 14A: Automated customer notification on status transition
            try {
                $orderNum = !empty($order['job_id']) ? (string)$order['job_id'] : ('#' . $orderId);
                NotificationService::notifyOrderStatusUpdated($orderId, (int)$order['customer_id'], $orderNum, $requestedNewStatus);
            } catch (\Throwable $ne) {
                error_log("Order status notification failed for order #{$orderId}: " . $ne->getMessage());
            }

            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function submitInspection(int $orderId, array $data, int $userId, string $role): bool {
        if (!in_array($role, ['admin', 'staff'], true)) {
            throw new \DomainException("Unauthorized: Role '{$role}' is not permitted to perform inspection.");
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            // Lock order row
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $db->rollBack();
                throw new \InvalidArgumentException("Order #{$orderId} not found.");
            }

            $currentStatus = $order['status'];

            // Price freeze check: once price_confirmed or later, total_amount cannot be changed
            if (in_array($currentStatus, ['price_confirmed', 'processing', 'ready', 'out_for_delivery', 'delivered', 'completed'], true)) {
                $db->rollBack();
                throw new \DomainException("Order price is already confirmed and permanently locked. Cannot submit inspection or modify price.");
            }

            // Must be picked_up or inspection_completed
            if (!in_array($currentStatus, ['picked_up', 'inspection_completed'], true)) {
                $db->rollBack();
                throw new \DomainException("Inspection can only be performed when order is in 'picked_up' or 'inspection_completed' status (current: '{$currentStatus}').");
            }

            // Capture pre-replacement snapshot of existing active order_items
            $oldLines = OrderItem::getByOrderId($orderId);

            $garmentNotes     = trim((string)($data['garment_notes'] ?? ''));
            $adjustmentReason = trim((string)($data['adjustment_reason'] ?? ''));

            $rawLinesInput = (!empty($data['items']) && is_array($data['items'])) 
                ? $data['items'] 
                : ((!empty($data['lines']) && is_array($data['lines'])) ? $data['lines'] : null);
            $isMultiItem = ($rawLinesInput !== null);
            $validatedLines = [];
            $itemsSubtotal = 0.00;

            if ($isMultiItem) {
                $validated = OrderItem::validateLines($rawLinesInput);
                $validatedLines = $validated['lines'];
                $itemsSubtotal  = $validated['subtotal'];
            } else {
                // Single-item fallback (Phase 10B legacy parameters)
                $itemId     = (int)($data['item_id'] ?? 0);
                $workTypeId = (int)($data['work_type_id'] ?? 0);
                $rawQty     = $data['quantity'] ?? null;

                if (!is_numeric($rawQty) || (float)$rawQty <= 0) {
                    $db->rollBack();
                    throw new \InvalidArgumentException("Quantity or weight must be a valid number greater than zero.");
                }
                if ((float)$rawQty > 1000) {
                    $db->rollBack();
                    throw new \InvalidArgumentException("Quantity or weight cannot exceed 1,000.");
                }

                $itemObj = LaundryItem::find($itemId);
                if (!$itemObj) {
                    $db->rollBack();
                    throw new \InvalidArgumentException("Selected laundry item is invalid or not found.");
                }

                $workTypeObj = WorkType::find($workTypeId);
                if (!$workTypeObj) {
                    $db->rollBack();
                    throw new \InvalidArgumentException("Selected work type is invalid or not found.");
                }

                $pricing = Pricing::find($itemId, $workTypeId);
                if (!$pricing) {
                    $db->rollBack();
                    throw new \InvalidArgumentException("No valid pricing configuration exists for the selected item and work type.");
                }

                $pricingType = $pricing['pricing_type'];
                if ($pricingType === 'per_item') {
                    if (floor((float)$rawQty) != (float)$rawQty) {
                        $db->rollBack();
                        throw new \InvalidArgumentException("Quantity for per-item service must be a whole positive integer.");
                    }
                    $quantity = (float)floor((float)$rawQty);
                } else {
                    $quantity = round((float)$rawQty, 2);
                }

                $unitPrice = (float)$pricing['price'];
                $itemsSubtotal = round($unitPrice * $quantity, 2);

                $validatedLines[] = [
                    'item_id'        => $itemId,
                    'item_name'      => $itemObj['name'],
                    'work_type_id'   => $workTypeId,
                    'work_type_name' => $workTypeObj['name'],
                    'quantity'       => $quantity,
                    'unit_price'     => $unitPrice,
                    'pricing_type'   => $pricingType,
                    'subtotal'       => $itemsSubtotal,
                    'item_notes'     => !empty($garmentNotes) ? $garmentNotes : null,
                    'display_order'  => 1
                ];
            }

            $itemsSubtotal = round($itemsSubtotal, 2);

            // Business settings fee logic
            $settings     = Settings::get();
            $feeBreakdown = self::getDeliveryFeeBreakdown($itemsSubtotal, $settings);
            $addedCharges = $feeBreakdown['total_charges'];
            $newTotal     = round($itemsSubtotal + $addedCharges, 2);

            // Strict Price Reduction Rule: new_total < amount_paid is REJECTED
            $amountPaid = round((float)$order['amount_paid'], 2);
            if ($newTotal < $amountPaid - 0.001) {
                $db->rollBack();
                throw new \DomainException(sprintf(
                    "Cannot revise order total to ₹%.2f: The proposed final price is lower than the amount already collected (₹%.2f) and refunds/reversals are not supported in V1.",
                    $newTotal,
                    $amountPaid
                ));
            }

            // Payment status recalculation
            if ($amountPaid <= 0.001) {
                $newPaymentStatus = 'unpaid';
            } elseif (abs($newTotal - $amountPaid) < 0.001) {
                $newPaymentStatus = 'paid';
            } else {
                $newPaymentStatus = 'partial';
            }

            // Get inspector name
            $userStmt = $db->prepare("SELECT name FROM users WHERE id = :uid");
            $userStmt->execute(['uid' => $userId]);
            $inspectorName = $userStmt->fetchColumn() ?: "User #{$userId}";

            // Extract existing metadata
            $extracted = OrderMetadata::extract($order['special_instructions']);
            $customerNote = $extracted['customer_note'];
            $meta = $extracted['metadata'];

            // Build inspection payload
            $inspectionPayload = [
                'inspected_by'      => $userId,
                'inspected_by_name' => (string)$inspectorName,
                'inspected_at'      => date('Y-m-d H:i:s'),
                'items'             => $validatedLines,
                'items_subtotal'    => $itemsSubtotal,
                'pickup_fee'        => $feeBreakdown['pickup_fee'],
                'delivery_fee'      => $feeBreakdown['delivery_fee'],
                'free_pickup_limit' => $feeBreakdown['free_pickup_limit'],
                'calculated_total'  => $newTotal,
                'garment_notes'     => $garmentNotes
            ];

            // If single-item, also include top-level fields for backwards compatibility
            if (!$isMultiItem && count($validatedLines) === 1) {
                $first = $validatedLines[0];
                $inspectionPayload['item_id'] = $first['item_id'];
                $inspectionPayload['item_name'] = $first['item_name'];
                $inspectionPayload['work_type_id'] = $first['work_type_id'];
                $inspectionPayload['work_type_name'] = $first['work_type_name'];
                $inspectionPayload['quantity'] = $first['quantity'];
                $inspectionPayload['unit_label'] = ($first['pricing_type'] === 'per_kg') ? 'kg' : 'item(s)';
                $inspectionPayload['unit_price'] = $first['unit_price'];
                $inspectionPayload['item_subtotal'] = $first['subtotal'];
            }

            $revisionHash = OrderMetadata::generateRevisionHash($inspectionPayload);

            $prevTotal = round((float)$order['total_amount'], 2);
            $existingRevisions = $meta['revisions'] ?? [];
            $existingRevisions[] = [
                'revised_by'     => $userId,
                'revised_at'     => date('Y-m-d H:i:s'),
                'previous_total' => $prevTotal,
                'new_total'      => $newTotal,
                'previous_lines' => $oldLines,
                'new_lines'      => $validatedLines,
                'lines_count'    => count($validatedLines),
                'reason'         => !empty($adjustmentReason) ? $adjustmentReason : ($currentStatus === 'picked_up' ? 'Initial inspection completed' : 'Inspection revised'),
                'revision_id'    => $revisionHash
            ];

            $meta['version']             = 1;
            $meta['customer_note']       = $customerNote;
            $meta['inspection_revision'] = $revisionHash;
            $meta['inspection']          = $inspectionPayload;
            $meta['revisions']           = $existingRevisions;
            if (!isset($meta['approval'])) {
                $meta['approval'] = [
                    'status'           => 'pending',
                    'approved_by'      => null,
                    'approved_by_role' => null,
                    'approved_at'      => null,
                    'method'           => null,
                    'override_reason'  => null
                ];
            }

            $newInstructions = OrderMetadata::compose($customerNote, $meta);

            // Replace active lines in order_items (within transaction)
            OrderItem::replaceForOrder($orderId, $validatedLines, $db);

            // Update order record
            $upd = $db->prepare("UPDATE orders SET 
                total_amount = :total, 
                payment_status = :pay_stat, 
                status = 'inspection_completed', 
                special_instructions = :inst, 
                updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id");
            $upd->execute([
                'total'    => $newTotal,
                'pay_stat' => $newPaymentStatus,
                'inst'     => $newInstructions,
                'id'       => $orderId
            ]);

            // If advancing from picked_up to inspection_completed, log status history
            if ($currentStatus === 'picked_up') {
                $hist = $db->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, updated_by) VALUES (:oid, 'picked_up', 'inspection_completed', :uid)");
                $hist->execute([
                    'oid' => $orderId,
                    'uid' => $userId
                ]);
            }

            $db->commit();

            // Phase 14A: Automated customer notification for inspection completed
            try {
                $orderNum = !empty($order['job_id']) ? (string)$order['job_id'] : ('#' . $orderId);
                NotificationService::notifyOrderStatusUpdated($orderId, (int)$order['customer_id'], $orderNum, 'inspection_completed');
            } catch (\Throwable $ne) {
                error_log("Inspection completed notification failed for order #{$orderId}: " . $ne->getMessage());
            }

            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function confirmPriceForCustomer(int $orderId, int $customerId, string $expectedRevision): bool {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $db->rollBack();
                throw new \InvalidArgumentException("Order #{$orderId} not found.");
            }

            if ((int)$order['customer_id'] !== $customerId) {
                $db->rollBack();
                throw new \DomainException("Access denied: You do not own order #{$orderId}.");
            }

            if ($order['status'] !== 'inspection_completed') {
                $db->rollBack();
                throw new \DomainException("Order #{$orderId} is not awaiting customer price approval (current status: '{$order['status']}').");
            }

            $extracted = OrderMetadata::extract($order['special_instructions']);
            $meta = $extracted['metadata'];
            $currentRevision = (string)($meta['inspection_revision'] ?? '');

            if (empty($expectedRevision) || $expectedRevision !== $currentRevision) {
                $db->rollBack();
                throw new \DomainException("The order price was updated. Please review the latest price before approving.");
            }

            $meta['approval'] = [
                'status'           => 'approved',
                'approved_by'      => $customerId,
                'approved_by_role' => 'customer',
                'approved_at'      => date('Y-m-d H:i:s'),
                'method'           => 'customer_portal',
                'override_reason'  => null
            ];

            $newInstructions = OrderMetadata::compose($extracted['customer_note'], $meta);

            $upd = $db->prepare("UPDATE orders SET status = 'price_confirmed', special_instructions = :inst, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $upd->execute([
                'inst' => $newInstructions,
                'id'   => $orderId
            ]);

            $hist = $db->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, updated_by) VALUES (:oid, 'inspection_completed', 'price_confirmed', :uid)");
            $hist->execute([
                'oid' => $orderId,
                'uid' => $customerId
            ]);

            $db->commit();

            // Phase 14A: Automated customer notification for price confirmed
            try {
                $orderNum = !empty($order['job_id']) ? (string)$order['job_id'] : ('#' . $orderId);
                NotificationService::notifyOrderStatusUpdated($orderId, $customerId, $orderNum, 'price_confirmed');
            } catch (\Throwable $ne) {
                error_log("Price confirmed notification failed for order #{$orderId}: " . $ne->getMessage());
            }

            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function overridePriceApprovalByAdmin(int $orderId, int $adminId, string $reason): bool {
        $reason = trim($reason);
        if (strlen($reason) < 10) {
            throw new \InvalidArgumentException("A valid emergency override reason (minimum 10 characters) is mandatory.");
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $db->rollBack();
                throw new \InvalidArgumentException("Order #{$orderId} not found.");
            }

            if ($order['status'] !== 'inspection_completed') {
                $db->rollBack();
                throw new \DomainException("Order #{$orderId} is not in inspection_completed status (current status: '{$order['status']}').");
            }

            $extracted = OrderMetadata::extract($order['special_instructions']);
            $meta = $extracted['metadata'];

            $meta['approval'] = [
                'status'           => 'approved',
                'approved_by'      => $adminId,
                'approved_by_role' => 'admin',
                'approved_at'      => date('Y-m-d H:i:s'),
                'method'           => 'admin_emergency_override',
                'override_reason'  => $reason
            ];

            $newInstructions = OrderMetadata::compose($extracted['customer_note'], $meta);

            $upd = $db->prepare("UPDATE orders SET status = 'price_confirmed', special_instructions = :inst, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $upd->execute([
                'inst' => $newInstructions,
                'id'   => $orderId
            ]);

            $hist = $db->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, updated_by) VALUES (:oid, 'inspection_completed', 'price_confirmed', :uid)");
            $hist->execute([
                'oid' => $orderId,
                'uid' => $adminId
            ]);

            $db->commit();

            // Phase 14A: Automated customer notification for price confirmed (admin override)
            try {
                $orderNum = !empty($order['job_id']) ? (string)$order['job_id'] : ('#' . $orderId);
                NotificationService::notifyOrderStatusUpdated($orderId, (int)$order['customer_id'], $orderNum, 'price_confirmed');
            } catch (\Throwable $ne) {
                error_log("Price confirmed (override) notification failed for order #{$orderId}: " . $ne->getMessage());
            }

            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cancel an unpaid order from pickup_request, accepted, or inspection_completed status.
     */
    public static function cancel(int $orderId, int $userId, string $role, string $reason): bool {
        if (!in_array($role, ['customer', 'admin', 'staff'], true)) {
            throw new \DomainException("Unauthorized: Role '{$role}' is not permitted to cancel orders.");
        }

        $trimmedReason = trim($reason);
        if (strlen($trimmedReason) < 5) {
            throw new \InvalidArgumentException("Cancellation reason must be at least 5 characters.");
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $db->rollBack();
                throw new \InvalidArgumentException("Order #{$orderId} not found.");
            }

            if ($role === 'customer' && (int)$order['customer_id'] !== $userId) {
                $db->rollBack();
                throw new \DomainException("Access denied: You do not own order #{$orderId}.");
            }

            $currentStatus = $order['status'];
            if ($role === 'customer') {
                $allowedOriginStatuses = ['pickup_request'];
            } else {
                $allowedOriginStatuses = ['pickup_request', 'accepted', 'inspection_completed'];
            }

            if (!in_array($currentStatus, $allowedOriginStatuses, true)) {
                $db->rollBack();
                if ($role === 'customer' && in_array($currentStatus, ['accepted', 'inspection_completed'], true)) {
                    throw new \DomainException("Customer cancellation is not permitted in status '{$currentStatus}'.");
                }
                throw new \DomainException("Order #{$orderId} cannot be cancelled in status '{$currentStatus}'.");
            }

            $paid = round((float)$order['amount_paid'], 2);
            if ($paid > 0.00 || $order['payment_status'] === 'paid' || $order['payment_status'] === 'partial') {
                $db->rollBack();
                throw new \DomainException("Cannot cancel order #{$orderId}: Payment has been made. Refund processing is not supported.");
            }

            $cancellationType = ($role === 'customer')
                ? 'customer_cancellation'
                : ($role === 'admin' ? 'admin_cancellation' : 'staff_cancellation');

            $extracted = OrderMetadata::extract($order['special_instructions']);
            $meta = $extracted['metadata'];
            $meta['cancellation'] = [
                'type'              => $cancellationType,
                'cancelled_by'      => $userId,
                'cancelled_by_role' => $role,
                'cancelled_at'      => date('Y-m-d H:i:s'),
                'reason'            => $trimmedReason,
                'origin_status'     => $currentStatus
            ];

            $newInstructions = OrderMetadata::compose($extracted['customer_note'], $meta);

            $upd = $db->prepare("UPDATE orders SET status = 'cancelled', special_instructions = :inst, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $upd->execute([
                'inst' => $newInstructions,
                'id'   => $orderId
            ]);

            $hist = $db->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, updated_by) VALUES (:oid, :old_status, 'cancelled', :uid)");
            $hist->execute([
                'oid'        => $orderId,
                'old_status' => $currentStatus,
                'uid'        => $userId
            ]);

            $db->commit();

            // Phase 14A: Automated customer notification for order cancelled
            try {
                $orderNum = !empty($order['job_id']) ? (string)$order['job_id'] : ('#' . $orderId);
                NotificationService::notifyOrderStatusUpdated($orderId, (int)$order['customer_id'], $orderNum, 'cancelled', $trimmedReason);
            } catch (\Throwable $ne) {
                error_log("Order cancelled notification failed for order #{$orderId}: " . $ne->getMessage());
            }

            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Customer rejects inspected price during inspection_completed status.
     * Transitions order to 'cancelled' with structured rejection & cancellation audit.
     */
    public static function rejectInspectedPrice(int $orderId, int $customerId, string $expectedRevision, string $reason): bool {
        $trimmedReason = trim($reason);
        if (strlen($trimmedReason) < 5) {
            throw new \InvalidArgumentException("Rejection reason must be at least 5 characters.");
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $db->rollBack();
                throw new \InvalidArgumentException("Order #{$orderId} not found.");
            }

            if ((int)$order['customer_id'] !== $customerId) {
                $db->rollBack();
                throw new \DomainException("Access denied: You do not own order #{$orderId}.");
            }

            if ($order['status'] !== 'inspection_completed') {
                $db->rollBack();
                throw new \DomainException("Order #{$orderId} is not awaiting customer price approval (current status: '{$order['status']}').");
            }

            $paid = round((float)$order['amount_paid'], 2);
            if ($paid > 0.00 || $order['payment_status'] === 'paid' || $order['payment_status'] === 'partial') {
                $db->rollBack();
                throw new \DomainException("Cannot reject price: Payment has already been recorded on order #{$orderId}.");
            }

            $extracted = OrderMetadata::extract($order['special_instructions']);
            $meta = $extracted['metadata'];
            $currentRevision = (string)($meta['inspection_revision'] ?? '');

            if (empty($expectedRevision) || $expectedRevision !== $currentRevision) {
                $db->rollBack();
                throw new \DomainException("The order price was updated. Please review the latest price before rejecting.");
            }

            $now = date('Y-m-d H:i:s');
            $meta['approval'] = [
                'status'           => 'rejected',
                'approved_by'      => $customerId,
                'approved_by_role' => 'customer',
                'approved_at'      => $now,
                'method'           => 'customer_portal',
                'override_reason'  => null
            ];
            $meta['rejection'] = [
                'rejected_by' => $customerId,
                'rejected_at' => $now,
                'reason'      => $trimmedReason,
                'revision_id' => $expectedRevision
            ];
            $meta['cancellation'] = [
                'type'              => 'customer_price_rejection',
                'cancelled_by'      => $customerId,
                'cancelled_by_role' => 'customer',
                'cancelled_at'      => $now,
                'reason'            => "Customer rejected inspected price: " . $trimmedReason,
                'origin_status'     => 'inspection_completed'
            ];

            $newInstructions = OrderMetadata::compose($extracted['customer_note'], $meta);

            $upd = $db->prepare("UPDATE orders SET status = 'cancelled', special_instructions = :inst, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $upd->execute([
                'inst' => $newInstructions,
                'id'   => $orderId
            ]);

            $hist = $db->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, updated_by) VALUES (:oid, 'inspection_completed', 'cancelled', :uid)");
            $hist->execute([
                'oid' => $orderId,
                'uid' => $customerId
            ]);

            $db->commit();

            // Phase 14A: Automated customer notification for price rejection cancellation
            try {
                $orderNum = !empty($order['job_id']) ? (string)$order['job_id'] : ('#' . $orderId);
                NotificationService::notifyOrderStatusUpdated($orderId, $customerId, $orderNum, 'cancelled', $trimmedReason);
            } catch (\Throwable $ne) {
                error_log("Order rejected/cancelled notification failed for order #{$orderId}: " . $ne->getMessage());
            }

            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Reschedule pickup and/or delivery date with metadata audit log.
     * Does not alter order status or create an order_status_history entry.
     */
    public static function reschedule(
        int $orderId,
        int $userId,
        string $role,
        ?string $newPickupDate,
        ?string $newDeliveryDate,
        string $reason
    ): bool {
        if (!in_array($role, ['customer', 'admin', 'staff'], true)) {
            throw new \DomainException("Unauthorized: Role '{$role}' is not permitted to reschedule orders.");
        }

        $trimmedReason = trim($reason);
        if (strlen($trimmedReason) < 5) {
            throw new \InvalidArgumentException("Reschedule reason must be at least 5 characters.");
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $db->rollBack();
                throw new \InvalidArgumentException("Order #{$orderId} not found.");
            }

            $currentStatus = $order['status'];

            // Customer checks
            if ($role === 'customer') {
                if ((int)$order['customer_id'] !== $userId) {
                    $db->rollBack();
                    throw new \DomainException("Access denied: You do not own order #{$orderId}.");
                }
                if (!in_array($currentStatus, ['pickup_request', 'accepted'], true)) {
                    $db->rollBack();
                    throw new \DomainException("Pickup date cannot be rescheduled once order has been picked up (current status: '{$currentStatus}').");
                }
                if ($newDeliveryDate !== null && trim($newDeliveryDate) !== '' && trim($newDeliveryDate) !== $order['delivery_date']) {
                    $db->rollBack();
                    throw new \DomainException("Customers cannot reschedule delivery date directly.");
                }
            } else {
                // Admin / Staff checks: cannot reschedule once out for delivery, delivered, completed, or cancelled
                if (in_array($currentStatus, ['out_for_delivery', 'delivered', 'completed', 'cancelled'], true)) {
                    $db->rollBack();
                    throw new \DomainException("Order #{$orderId} cannot be rescheduled in status '{$currentStatus}'.");
                }
            }

            // Date validation helper
            $validateDate = function(?string $dateStr): ?string {
                if ($dateStr === null) {
                    return null;
                }
                $d = trim($dateStr);
                if ($d === '') {
                    return null;
                }
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                    throw new \InvalidArgumentException("Invalid date format. Expected YYYY-MM-DD.");
                }
                [$y, $m, $day] = explode('-', $d);
                if (!checkdate((int)$m, (int)$day, (int)$y)) {
                    throw new \InvalidArgumentException("Invalid calendar date '{$d}'.");
                }
                return $d;
            };

            $pDate = $validateDate($newPickupDate);
            $dDate = $validateDate($newDeliveryDate);

            $today = date('Y-m-d');
            if ($pDate !== null && $pDate < $today) {
                $db->rollBack();
                throw new \InvalidArgumentException("Pickup date cannot be in the past.");
            }
            if ($dDate !== null && $dDate < $today) {
                $db->rollBack();
                throw new \InvalidArgumentException("Delivery date cannot be in the past.");
            }

            $effectivePickupDate   = $pDate ?? $order['pickup_date'];
            $effectiveDeliveryDate = $dDate ?? $order['delivery_date'];

            if (!empty($effectiveDeliveryDate) && !empty($effectivePickupDate) && $effectiveDeliveryDate < $effectivePickupDate) {
                $db->rollBack();
                throw new \InvalidArgumentException("Delivery date cannot be earlier than pickup date.");
            }

            $pickupChanged   = ($pDate !== null && $pDate !== $order['pickup_date']);
            $deliveryChanged = ($dDate !== null && $dDate !== $order['delivery_date']);

            if (!$pickupChanged && !$deliveryChanged) {
                $db->rollBack();
                throw new \InvalidArgumentException("No schedule changes detected.");
            }

            $extracted = OrderMetadata::extract($order['special_instructions']);
            $meta = $extracted['metadata'];
            $reschedules = $meta['reschedules'] ?? [];
            $reschedules[] = [
                'rescheduled_by'      => $userId,
                'rescheduled_by_role' => $role,
                'rescheduled_at'      => date('Y-m-d H:i:s'),
                'old_pickup_date'     => $order['pickup_date'],
                'new_pickup_date'     => $effectivePickupDate,
                'old_delivery_date'   => $order['delivery_date'],
                'new_delivery_date'   => $effectiveDeliveryDate,
                'reason'              => $trimmedReason
            ];
            $meta['reschedules'] = $reschedules;

            $newInstructions = OrderMetadata::compose($extracted['customer_note'], $meta);

            $upd = $db->prepare("UPDATE orders SET pickup_date = :pdate, delivery_date = :ddate, special_instructions = :inst, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $upd->execute([
                'pdate' => $effectivePickupDate,
                'ddate' => $effectiveDeliveryDate,
                'inst'  => $newInstructions,
                'id'    => $orderId
            ]);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function getCustomerKpis(int $customerId): array {
        $db = Database::getConnection();

        $stmtTotal = $db->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = :cid");
        $stmtTotal->execute(['cid' => $customerId]);
        $total = (int)$stmtTotal->fetchColumn();

        $stmtActive = $db->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = :cid AND status NOT IN ('completed', 'cancelled')");
        $stmtActive->execute(['cid' => $customerId]);
        $active = (int)$stmtActive->fetchColumn();

        $stmtCompleted = $db->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = :cid AND status = 'completed'");
        $stmtCompleted->execute(['cid' => $customerId]);
        $completed = (int)$stmtCompleted->fetchColumn();

        $stmtBalance = $db->prepare("SELECT COALESCE(SUM(total_amount - amount_paid), 0) FROM orders WHERE customer_id = :cid AND status != 'cancelled'");
        $stmtBalance->execute(['cid' => $customerId]);
        $outstanding = round((float)$stmtBalance->fetchColumn(), 2);

        return [
            'total'       => $total,
            'active'      => $active,
            'completed'   => $completed,
            'outstanding' => $outstanding
        ];
    }

    public static function getCustomerRecentOrders(int $customerId, int $limit = 5): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM orders WHERE customer_id = :cid ORDER BY id DESC LIMIT " . (int)$limit);
        $stmt->execute(['cid' => $customerId]);
        return $stmt->fetchAll();
    }

    public static function getCustomerOrders(int $customerId, string $filter = 'all'): array {
        $db = Database::getConnection();
        $conditions = ["o.customer_id = :cid"];
        $params = ['cid' => $customerId];

        if ($filter === 'active') {
            $conditions[] = "o.status NOT IN ('completed', 'cancelled')";
        } elseif ($filter === 'completed') {
            $conditions[] = "o.status = 'completed'";
        } elseif ($filter === 'cancelled') {
            $conditions[] = "o.status = 'cancelled'";
        } elseif ($filter === 'pending') {
            $conditions[] = "(o.payment_status != 'paid' OR o.amount_paid < o.total_amount) AND o.status != 'cancelled'";
        }

        $where = implode(" AND ", $conditions);
        $sql = "SELECT o.*, b.name AS branch_name, b.code AS branch_code
                FROM orders o
                LEFT JOIN branches b ON o.branch_id = b.id
                WHERE {$where}
                ORDER BY o.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function findForCustomer(int $orderId, int $customerId): ?array {
        $db = Database::getConnection();
        $sql = "SELECT o.*, c.name AS customer_name, c.mobile AS customer_mobile, c.address AS customer_address, s.name AS staff_name,
                       b.name AS branch_name, b.code AS branch_code, b.phone AS branch_phone, b.address AS branch_address,
                       b.working_hours AS branch_working_hours
                FROM orders o
                JOIN users c ON o.customer_id = c.id
                LEFT JOIN users s ON o.staff_id = s.id
                LEFT JOIN branches b ON o.branch_id = b.id
                WHERE o.id = :id AND o.customer_id = :customer_id
                LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $orderId, 'customer_id' => $customerId]);
        $order = $stmt->fetch();
        return $order ?: null;
    }

    public static function getStaffOperationalQueues(?int $branchId = null): array {
        $db = Database::getConnection();
        $branchClause = "";
        $params = [];
        if ($branchId !== null) {
            $branchClause = " AND o.branch_id = :branch_id ";
            $params['branch_id'] = $branchId;
        }

        // 1. Pickup Requests Queue
        $sqlPickup = "SELECT o.*, c.name AS customer_name, c.mobile AS customer_mobile, c.address AS customer_address, s.name AS staff_name,
                             b.name AS branch_name, b.code AS branch_code
                      FROM orders o
                      JOIN users c ON o.customer_id = c.id
                      LEFT JOIN users s ON o.staff_id = s.id
                      LEFT JOIN branches b ON o.branch_id = b.id
                      WHERE o.status = 'pickup_request' {$branchClause}
                      ORDER BY o.id DESC";
        $stmtPickup = $db->prepare($sqlPickup);
        $stmtPickup->execute($params);
        $pickups = $stmtPickup->fetchAll();

        // 2. In-Shop Processing Queue
        $sqlProcessing = "SELECT o.*, c.name AS customer_name, c.mobile AS customer_mobile, s.name AS staff_name,
                                 b.name AS branch_name, b.code AS branch_code
                          FROM orders o
                          JOIN users c ON o.customer_id = c.id
                          LEFT JOIN users s ON o.staff_id = s.id
                          LEFT JOIN branches b ON o.branch_id = b.id
                          WHERE o.status IN ('accepted', 'picked_up', 'inspection_completed', 'price_confirmed', 'processing') {$branchClause}
                          ORDER BY o.id DESC";
        $stmtProcessing = $db->prepare($sqlProcessing);
        $stmtProcessing->execute($params);
        $processing = $stmtProcessing->fetchAll();

        // 3. Ready / Delivery Queue
        $sqlDelivery = "SELECT o.*, c.name AS customer_name, c.mobile AS customer_mobile, c.address AS customer_address, s.name AS staff_name,
                               b.name AS branch_name, b.code AS branch_code
                        FROM orders o
                        JOIN users c ON o.customer_id = c.id
                        LEFT JOIN users s ON o.staff_id = s.id
                        LEFT JOIN branches b ON o.branch_id = b.id
                        WHERE o.status IN ('ready', 'out_for_delivery') {$branchClause}
                        ORDER BY o.id DESC";
        $stmtDelivery = $db->prepare($sqlDelivery);
        $stmtDelivery->execute($params);
        $delivery = $stmtDelivery->fetchAll();

        // 4. Payment Due Queue (Delivered & Unsettled)
        $sqlPaymentDue = "SELECT o.*, c.name AS customer_name, c.mobile AS customer_mobile, s.name AS staff_name,
                                 b.name AS branch_name, b.code AS branch_code
                          FROM orders o
                          JOIN users c ON o.customer_id = c.id
                          LEFT JOIN users s ON o.staff_id = s.id
                          LEFT JOIN branches b ON o.branch_id = b.id
                          WHERE o.status = 'delivered' AND (o.payment_status != 'paid' OR o.amount_paid < o.total_amount) {$branchClause}
                          ORDER BY o.id DESC";
        $stmtPaymentDue = $db->prepare($sqlPaymentDue);
        $stmtPaymentDue->execute($params);
        $paymentDue = $stmtPaymentDue->fetchAll();

        return [
            'pickup_requests' => $pickups,
            'processing'      => $processing,
            'delivery'        => $delivery,
            'payment_due'     => $paymentDue
        ];
    }

    public static function allPaginated(array $filters = [], int $page = 1, int $limit = 20): array {
        $db = Database::getConnection();

        // Validate and bound page & limit
        $limit = max(1, min(50, $limit));
        $page  = max(1, $page);
        $offset = ($page - 1) * $limit;

        $conditions = ["1=1"];
        $params = [];

        // Valid statuses state machine whitelist
        $validStatuses = self::VALID_STATUSES;

        if (!empty($filters['status']) && in_array($filters['status'], $validStatuses, true)) {
            $conditions[] = "o.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $conditions[] = "(o.job_id LIKE :term1 OR c.name LIKE :term2 OR c.mobile LIKE :term3)";
            $params['term1'] = $term;
            $params['term2'] = $term;
            $params['term3'] = $term;
        }

        if (!empty($filters['pickup_today'])) {
            $conditions[] = "DATE(o.pickup_date) = CURRENT_DATE()";
        }

        if (!empty($filters['delivery_today'])) {
            $conditions[] = "DATE(o.delivery_date) = CURRENT_DATE()";
        }

        if (!empty($filters['payment_due'])) {
            $conditions[] = "(o.payment_status != 'paid' OR o.amount_paid < o.total_amount)";
        }

        if (!empty($filters['branch_id'])) {
            $conditions[] = "o.branch_id = :branch_id";
            $params['branch_id'] = (int)$filters['branch_id'];
        }

        if (!empty($filters['unassigned'])) {
            $conditions[] = "o.staff_id IS NULL AND o.status != 'cancelled'";
        }

        $whereClause = implode(" AND ", $conditions);

        // Total count query
        $countSql = "SELECT COUNT(*) FROM orders o JOIN users c ON o.customer_id = c.id LEFT JOIN branches b ON o.branch_id = b.id WHERE {$whereClause}";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $totalOrders = (int)$countStmt->fetchColumn();

        $totalPages = max(1, (int)ceil($totalOrders / $limit));

        // Paginated rows query
        $sql = "SELECT o.*, c.name AS customer_name, c.mobile AS customer_mobile, s.name AS staff_name,
                       b.name AS branch_name, b.code AS branch_code
                FROM orders o
                JOIN users c ON o.customer_id = c.id
                LEFT JOIN users s ON o.staff_id = s.id
                LEFT JOIN branches b ON o.branch_id = b.id
                WHERE {$whereClause}
                ORDER BY o.id DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        return [
            'orders'       => $orders,
            'total_orders' => $totalOrders,
            'page'         => $page,
            'limit'        => $limit,
            'total_pages'  => $totalPages
        ];
    }

    public static function claimOrder(int $orderId, int $staffUserId): bool {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            // Lock order row
            $stmt = $db->prepare("SELECT id, branch_id, staff_id, status, special_instructions FROM orders WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $db->rollBack();
                throw new \InvalidArgumentException("Order #{$orderId} not found.");
            }

            if ($order['status'] === 'cancelled') {
                $db->rollBack();
                throw new \DomainException("Cannot claim a cancelled order.");
            }

            // Staff branch isolation check
            $staff = Staff::find($staffUserId);
            if ($staff && !empty($staff['branch_id'])) {
                if ((int)$order['branch_id'] !== (int)$staff['branch_id']) {
                    $db->rollBack();
                    throw new \DomainException("Staff cannot claim orders belonging to another branch.");
                }
            }

            if (!empty($order['staff_id'])) {
                $db->rollBack();
                throw new \DomainException("Order #{$orderId} is already assigned to a staff member.");
            }

            $staffName = $staff ? $staff['name'] : 'Staff Member';

            // Record assignment in metadata
            $extracted = OrderMetadata::extract($order['special_instructions'] ?? '');
            $meta = $extracted['metadata'];
            $assignments = $meta['assignments'] ?? [];
            $assignments[] = [
                'type'        => 'claimed',
                'staff_id'    => $staffUserId,
                'staff_name'  => $staffName,
                'assigned_by' => $staffUserId,
                'timestamp'   => date('Y-m-d H:i:s')
            ];
            $meta['assignments'] = $assignments;
            $newInstructions = OrderMetadata::compose($extracted['customer_note'], $meta);

            $updateStmt = $db->prepare("UPDATE orders SET staff_id = :staff_id, special_instructions = :inst, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND staff_id IS NULL");
            $updateStmt->execute([
                'staff_id' => $staffUserId,
                'inst'     => $newInstructions,
                'id'       => $orderId
            ]);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function assignStaff(int $orderId, ?int $staffUserId, int $assignedByUserId): bool {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT id, job_id, branch_id, staff_id, status, special_instructions FROM orders WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $db->rollBack();
                throw new \InvalidArgumentException("Order #{$orderId} not found.");
            }

            if ($order['status'] === 'cancelled') {
                $db->rollBack();
                throw new \DomainException("Cannot assign staff to a cancelled order.");
            }

            $staffName = 'Unassigned';
            if ($staffUserId !== null && $staffUserId > 0) {
                $staff = Staff::find($staffUserId);
                if (!$staff || empty($staff['is_active'])) {
                    $db->rollBack();
                    throw new \InvalidArgumentException("Selected staff member is invalid or inactive.");
                }
                $staffName = $staff['name'];
            } else {
                $staffUserId = null;
            }

            // Record assignment in metadata
            $extracted = OrderMetadata::extract($order['special_instructions'] ?? '');
            $meta = $extracted['metadata'];
            $assignments = $meta['assignments'] ?? [];
            $assignments[] = [
                'type'        => ($staffUserId !== null) ? 'assigned' : 'unassigned',
                'staff_id'    => $staffUserId,
                'staff_name'  => $staffName,
                'assigned_by' => $assignedByUserId,
                'timestamp'   => date('Y-m-d H:i:s')
            ];
            $meta['assignments'] = $assignments;
            $newInstructions = OrderMetadata::compose($extracted['customer_note'], $meta);

            $updateStmt = $db->prepare("UPDATE orders SET staff_id = :staff_id, special_instructions = :inst, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $updateStmt->bindValue(':staff_id', $staffUserId, $staffUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $updateStmt->bindValue(':inst', $newInstructions, PDO::PARAM_STR);
            $updateStmt->bindValue(':id', $orderId, PDO::PARAM_INT);
            $updateStmt->execute();

            if ($staffUserId !== null) {
                NotificationService::dispatch(
                    $staffUserId,
                    $orderId,
                    'order_assigned',
                    'Order Assigned to You',
                    "You have been assigned to order #{$order['job_id']}.",
                    'order_assign_' . $orderId . '_' . $staffUserId . '_' . time()
                );
            }

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
