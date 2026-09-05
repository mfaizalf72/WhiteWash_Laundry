<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Models;

use Faizal\LaundryLms\Core\Database;
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

    public static function create(array $orderData, int $staffUserId): int {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $jobId = !empty($orderData['job_id']) ? $orderData['job_id'] : self::generateJobId();

            $sql = "INSERT INTO orders 
                    (job_id, customer_id, staff_id, status, total_amount, amount_paid, payment_status, pickup_date, delivery_date, special_instructions)
                    VALUES 
                    (:job_id, :customer_id, :staff_id, :status, :total_amount, :amount_paid, :payment_status, :pickup_date, :delivery_date, :special_instructions)";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                'job_id'               => $jobId,
                'customer_id'          => $orderData['customer_id'],
                'staff_id'             => $staffUserId,
                'status'               => $orderData['status'] ?? 'pickup_request',
                'total_amount'         => $orderData['total_amount'],
                'amount_paid'          => $orderData['amount_paid'] ?? 0.00,
                'payment_status'       => $orderData['payment_status'] ?? 'unpaid',
                'pickup_date'          => $orderData['pickup_date'],
                'delivery_date'        => !empty($orderData['delivery_date']) ? $orderData['delivery_date'] : null,
                'special_instructions' => !empty($orderData['special_instructions']) ? $orderData['special_instructions'] : null
            ]);

            $orderId = (int)$db->lastInsertId();

            // Insert initial entry in order_status_history
            $histSql = "INSERT INTO order_status_history (order_id, old_status, new_status, updated_by)
                        VALUES (:order_id, NULL, :new_status, :updated_by)";
            $histStmt = $db->prepare($histSql);
            $histStmt->execute([
                'order_id'   => $orderId,
                'new_status' => $orderData['status'] ?? 'pickup_request',
                'updated_by' => $staffUserId
            ]);

            $db->commit();
            return $orderId;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
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
        $sql = "SELECT o.*, c.name AS customer_name, c.mobile AS customer_mobile, c.address AS customer_address, s.name AS staff_name
                FROM orders o
                JOIN users c ON o.customer_id = c.id
                LEFT JOIN users s ON o.staff_id = s.id
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

    public const STATUS_SEQUENCE = [
        'pickup_request'       => 'accepted',
        'accepted'             => 'picked_up',
        'picked_up'            => 'inspection_completed',
        'inspection_completed' => 'price_confirmed',
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

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            // Lock target row during status update
            $stmt = $db->prepare("SELECT id, status, total_amount, amount_paid, payment_status FROM orders WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $db->rollBack();
                throw new \InvalidArgumentException("Order #{$orderId} not found.");
            }

            $currentStatus = $order['status'];

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
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
