<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Models;

use Faizal\LaundryLms\Core\Database;
use Faizal\LaundryLms\Services\Notification\NotificationService;
use PDO;

class Refund {
    /**
     * Phase 1: Atomically validate and create a PENDING refund intent.
     * Uses short-lived row locks and commits immediately before any gateway call.
     *
     * @param array $data ['order_id', 'payment_id', 'amount', 'reason', 'idempotency_key']
     * @param int $staffUserId Authenticated user ID executing the refund
     * @return array ['refund' => array, 'already_exists' => bool]
     */
    public static function createIntent(array $data, int $staffUserId): array {
        $orderId        = (int)($data['order_id'] ?? 0);
        $paymentId      = (int)($data['payment_id'] ?? 0);
        $rawAmount      = trim((string)($data['amount'] ?? ''));
        $reason         = trim((string)($data['reason'] ?? ''));
        $idempotencyKey = trim((string)($data['idempotency_key'] ?? ''));

        if ($orderId <= 0) {
            throw new \InvalidArgumentException("Valid order ID is required.");
        }
        if ($paymentId <= 0) {
            throw new \InvalidArgumentException("Valid payment ID is required.");
        }
        if (empty($reason)) {
            throw new \InvalidArgumentException("Refund reason is required.");
        }
        if (empty($idempotencyKey)) {
            throw new \InvalidArgumentException("Refund idempotency key is required.");
        }
        if (!is_numeric($rawAmount) || (float)$rawAmount <= 0) {
            throw new \InvalidArgumentException("Refund amount must be a positive number greater than zero.");
        }
        $amount = round((float)$rawAmount, 2);

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            // 1. Check idempotency: if this exact request key already exists, return it
            $chkKey = $db->prepare("SELECT * FROM refunds WHERE idempotency_key = :key LIMIT 1 FOR UPDATE");
            $chkKey->execute(['key' => $idempotencyKey]);
            $existing = $chkKey->fetch();
            if ($existing) {
                $db->commit();
                return [
                    'refund'         => $existing,
                    'already_exists' => true
                ];
            }

            // 2. Lock and validate target order row
            $ordStmt = $db->prepare("SELECT id, total_amount, amount_paid, payment_status, status FROM orders WHERE id = :id FOR UPDATE");
            $ordStmt->execute(['id' => $orderId]);
            $order = $ordStmt->fetch();
            if (!$order) {
                $db->rollBack();
                throw new \InvalidArgumentException("Order not found.");
            }

            // 3. Lock and validate target payment row
            $payStmt = $db->prepare("SELECT id, order_id, amount, payment_method, gateway, gateway_payment_id FROM payments WHERE id = :id FOR UPDATE");
            $payStmt->execute(['id' => $paymentId]);
            $payment = $payStmt->fetch();
            if (!$payment) {
                $db->rollBack();
                throw new \InvalidArgumentException("Payment record not found.");
            }

            if ((int)$payment['order_id'] !== $orderId) {
                $db->rollBack();
                throw new \DomainException("Payment does not belong to the specified order.");
            }

            if ($payment['payment_method'] !== 'online') {
                $db->rollBack();
                throw new \DomainException("Only online payments can be refunded automatically via payment gateway.");
            }

            if (empty($payment['gateway_payment_id'])) {
                $db->rollBack();
                throw new \DomainException("Payment does not have a valid gateway payment transaction ID.");
            }

            // 4. Calculate currently reserved and processed refunds on this payment
            $refSumStmt = $db->prepare("
                SELECT COALESCE(SUM(amount), 0) AS reserved_sum
                FROM refunds
                WHERE payment_id = :pid AND status IN ('pending', 'processed')
                FOR UPDATE
            ");
            $refSumStmt->execute(['pid' => $paymentId]);
            $reservedAmount = (float)$refSumStmt->fetchColumn();

            $paymentAmount = (float)$payment['amount'];
            $refundable = round($paymentAmount - $reservedAmount, 2);

            if ($refundable <= 0) {
                $db->rollBack();
                throw new \DomainException("This payment has already been fully refunded or has pending refunds reserving the full balance.");
            }

            if ($amount > $refundable + 0.001) {
                $db->rollBack();
                throw new \DomainException("Refund amount (₹" . number_format($amount, 2) . ") exceeds remaining refundable balance (₹" . number_format($refundable, 2) . ").");
            }

            // Ensure refund amount does not exceed order's current total amount_paid
            $currentOrderPaid = (float)$order['amount_paid'];
            if ($amount > $currentOrderPaid + 0.001) {
                $db->rollBack();
                throw new \DomainException("Refund amount (₹" . number_format($amount, 2) . ") exceeds total amount paid on order (₹" . number_format($currentOrderPaid, 2) . ").");
            }

            $gateway = !empty($payment['gateway']) ? (string)$payment['gateway'] : 'mock';

            // 5. Insert PENDING refund intent
            $insStmt = $db->prepare("
                INSERT INTO refunds (
                    order_id, payment_id, recorded_by, amount, reason,
                    gateway, gateway_payment_id, idempotency_key, status
                ) VALUES (
                    :order_id, :payment_id, :recorded_by, :amount, :reason,
                    :gateway, :gateway_payment_id, :idempotency_key, 'pending'
                )
            ");
            $insStmt->execute([
                'order_id'           => $orderId,
                'payment_id'         => $paymentId,
                'recorded_by'        => $staffUserId,
                'amount'             => $amount,
                'reason'             => $reason,
                'gateway'            => $gateway,
                'gateway_payment_id' => $payment['gateway_payment_id'],
                'idempotency_key'    => $idempotencyKey
            ]);

            $refundId = (int)$db->lastInsertId();
            $db->commit();

            return [
                'refund' => [
                    'id'                 => $refundId,
                    'order_id'           => $orderId,
                    'payment_id'         => $paymentId,
                    'recorded_by'        => $staffUserId,
                    'amount'             => $amount,
                    'reason'             => $reason,
                    'gateway'            => $gateway,
                    'gateway_payment_id' => $payment['gateway_payment_id'],
                    'gateway_refund_id'  => null,
                    'gateway_error'      => null,
                    'idempotency_key'    => $idempotencyKey,
                    'status'             => 'pending',
                    'created_at'         => date('Y-m-d H:i:s'),
                    'processed_at'       => null
                ],
                'already_exists' => false
            ];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Phase 3A: Atomically finalize settlement of a refund upon gateway confirmation.
     * Decrements orders.amount_paid exactly once, updates payment_status, marks refund 'processed'.
     */
    public static function settleSuccess(int $refundId, string $gatewayRefundId): array {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $refStmt = $db->prepare("SELECT * FROM refunds WHERE id = :id FOR UPDATE");
            $refStmt->execute(['id' => $refundId]);
            $refund = $refStmt->fetch();

            if (!$refund) {
                $db->rollBack();
                throw new \InvalidArgumentException("Refund intent not found.");
            }

            if ($refund['status'] === 'processed') {
                // Idempotent: already settled by concurrent webhook or callback
                $orderId = (int)$refund['order_id'];
                $ordStmt = $db->prepare("SELECT amount_paid, payment_status FROM orders WHERE id = :id");
                $ordStmt->execute(['id' => $orderId]);
                $ord = $ordStmt->fetch();
                $db->commit();
                return [
                    'refund'          => $refund,
                    'already_settled' => true,
                    'new_amount_paid' => (float)($ord['amount_paid'] ?? 0.00),
                    'new_status'      => (string)($ord['payment_status'] ?? 'unpaid')
                ];
            }

            if ($refund['status'] === 'failed') {
                $db->rollBack();
                throw new \DomainException("Cannot settle a failed refund intent.");
            }

            $orderId = (int)$refund['order_id'];
            $ordStmt = $db->prepare("SELECT id, job_id, customer_id, total_amount, amount_paid, payment_status FROM orders WHERE id = :id FOR UPDATE");
            $ordStmt->execute(['id' => $orderId]);
            $order = $ordStmt->fetch();

            if (!$order) {
                $db->rollBack();
                throw new \InvalidArgumentException("Order associated with refund not found.");
            }

            // 1. Update refund status to processed
            $updRef = $db->prepare("
                UPDATE refunds
                SET status = 'processed', gateway_refund_id = :grid, processed_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $updRef->execute([
                'grid' => $gatewayRefundId,
                'id'   => $refundId
            ]);

            // 2. Decrement orders.amount_paid
            $refundAmount = (float)$refund['amount'];
            $currentPaid  = (float)$order['amount_paid'];
            $totalAmount  = round((float)$order['total_amount'], 2);
            $newPaid      = max(0.00, round($currentPaid - $refundAmount, 2));

            // 3. Recalculate order payment_status
            if ($newPaid >= $totalAmount - 0.001 && $totalAmount > 0) {
                $newStatus = 'paid';
            } elseif ($newPaid > 0) {
                $newStatus = 'partial';
            } else {
                $newStatus = 'unpaid';
            }

            $updOrd = $db->prepare("
                UPDATE orders
                SET amount_paid = :paid, payment_status = :status, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $updOrd->execute([
                'paid'   => $newPaid,
                'status' => $newStatus,
                'id'     => $orderId
            ]);

            $db->commit();

            // Phase 14A: Automated customer notification for refund processed
            try {
                $orderNum = !empty($order['job_id']) ? (string)$order['job_id'] : ('#' . $orderId);
                NotificationService::notifyRefundProcessed(
                    $refundId,
                    $orderId,
                    (int)$order['customer_id'],
                    $orderNum,
                    (float)$refund['amount']
                );
            } catch (\Throwable $ne) {
                error_log("Refund processed notification failed for refund #{$refundId}: " . $ne->getMessage());
            }

            $refund['status']            = 'processed';
            $refund['gateway_refund_id'] = $gatewayRefundId;
            $refund['processed_at']      = date('Y-m-d H:i:s');

            return [
                'refund'          => $refund,
                'already_settled' => false,
                'new_amount_paid' => $newPaid,
                'new_status'      => $newStatus
            ];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Phase 3B: Mark a refund intent as failed when gateway definitively rejects the reversal.
     * Drops the reservation so the funds become refundable again.
     */
    public static function settleFailure(int $refundId, string $errorMessage = ''): array {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $refStmt = $db->prepare("SELECT * FROM refunds WHERE id = :id FOR UPDATE");
            $refStmt->execute(['id' => $refundId]);
            $refund = $refStmt->fetch();

            if (!$refund) {
                $db->rollBack();
                throw new \InvalidArgumentException("Refund intent not found.");
            }

            if ($refund['status'] === 'processed') {
                // Cannot fail an already processed refund
                $db->commit();
                return [
                    'refund'            => $refund,
                    'already_processed' => true
                ];
            }

            $cleanError = !empty($errorMessage) ? mb_substr(trim($errorMessage), 0, 255) : null;

            $updRef = $db->prepare("
                UPDATE refunds
                SET status = 'failed', gateway_error = :gerr, processed_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $updRef->execute([
                'gerr' => $cleanError,
                'id'   => $refundId
            ]);

            $db->commit();

            // Phase 14A: Automated customer notification for refund failed
            try {
                $orderId = (int)$refund['order_id'];
                $ordStmt = $db->prepare("SELECT customer_id, job_id FROM orders WHERE id = :id LIMIT 1");
                $ordStmt->execute(['id' => $orderId]);
                $orderRow = $ordStmt->fetch(PDO::FETCH_ASSOC);
                if ($orderRow) {
                    $orderNum = !empty($orderRow['job_id']) ? (string)$orderRow['job_id'] : ('#' . $orderId);
                    NotificationService::notifyRefundFailed(
                        $refundId,
                        $orderId,
                        (int)$orderRow['customer_id'],
                        $orderNum,
                        (float)$refund['amount'],
                        $cleanError
                    );
                }
            } catch (\Throwable $ne) {
                error_log("Refund failed notification failed for refund #{$refundId}: " . $ne->getMessage());
            }

            $refund['status']        = 'failed';
            $refund['gateway_error'] = $cleanError;
            $refund['processed_at']  = date('Y-m-d H:i:s');

            return [
                'refund'            => $refund,
                'already_processed' => false
            ];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Calculate remaining refundable balance for an online payment.
     */
    public static function getRefundableAmount(int $paymentId): float {
        $db = Database::getConnection();

        $payStmt = $db->prepare("SELECT amount, payment_method FROM payments WHERE id = :id");
        $payStmt->execute(['id' => $paymentId]);
        $pay = $payStmt->fetch();

        if (!$pay || $pay['payment_method'] !== 'online') {
            return 0.00;
        }

        $sumStmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0)
            FROM refunds
            WHERE payment_id = :pid AND status IN ('pending', 'processed')
        ");
        $sumStmt->execute(['pid' => $paymentId]);
        $reserved = (float)$sumStmt->fetchColumn();

        return max(0.00, round((float)$pay['amount'] - $reserved, 2));
    }

    /**
     * Get all refunds for an order with recorded_by user's name.
     */
    public static function getByOrderId(int $orderId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT r.*, u.name AS recorded_by_name
            FROM refunds r
            LEFT JOIN users u ON r.recorded_by = u.id
            WHERE r.order_id = :oid
            ORDER BY r.id ASC
        ");
        $stmt->execute(['oid' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all refunds for a specific payment.
     */
    public static function getByPaymentId(int $paymentId): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT r.*, u.name AS recorded_by_name
            FROM refunds r
            LEFT JOIN users u ON r.recorded_by = u.id
            WHERE r.payment_id = :pid
            ORDER BY r.id ASC
        ");
        $stmt->execute(['pid' => $paymentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Total processed refunded amount for an order.
     */
    public static function getTotalRefundedByOrderId(int $orderId): float {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0)
            FROM refunds
            WHERE order_id = :oid AND status = 'processed'
        ");
        $stmt->execute(['oid' => $orderId]);
        return round((float)$stmt->fetchColumn(), 2);
    }

    public static function find(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM refunds WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByGatewayRefundId(string $gatewayRefundId): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM refunds WHERE gateway_refund_id = :grid LIMIT 1");
        $stmt->execute(['grid' => $gatewayRefundId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByIdempotencyKey(string $key): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM refunds WHERE idempotency_key = :k LIMIT 1");
        $stmt->execute(['k' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
