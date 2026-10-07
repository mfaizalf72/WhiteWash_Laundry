<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Models;

use Faizal\LaundryLms\Core\Database;
use Faizal\LaundryLms\Services\Notification\NotificationService;
use PDO;

class Payment {
    public static function create(array $paymentData, int $userId): array {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $orderId          = (int)($paymentData['order_id'] ?? 0);
            $method           = trim($paymentData['payment_method'] ?? '');
            $rawAmount        = trim((string)($paymentData['amount'] ?? ''));
            $upiRef           = trim($paymentData['upi_reference'] ?? '');
            $gateway          = !empty($paymentData['gateway']) ? trim((string)$paymentData['gateway']) : null;
            $gatewayOrderId   = !empty($paymentData['gateway_order_id']) ? trim((string)$paymentData['gateway_order_id']) : null;
            $gatewayPaymentId = !empty($paymentData['gateway_payment_id']) ? trim((string)$paymentData['gateway_payment_id']) : null;
            $gatewaySignature = !empty($paymentData['gateway_signature']) ? trim((string)$paymentData['gateway_signature']) : null;

            // 1. Lock and read target order row
            $stmt = $db->prepare("SELECT id, job_id, customer_id, total_amount, amount_paid, payment_status, status FROM orders WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $db->rollBack();
                throw new \InvalidArgumentException("Order not found.");
            }

            if ($order['status'] === 'cancelled') {
                $db->rollBack();
                throw new \DomainException("Cannot record payment for a cancelled order.");
            }

            // 2. Validate payment method
            if (!in_array($method, ['cash', 'upi', 'online'], true)) {
                $db->rollBack();
                throw new \InvalidArgumentException("Invalid payment method. Only 'cash', 'upi', and 'online' are allowed.");
            }

            // 3. Validate method-specific requirements
            if ($method === 'upi' && empty($upiRef)) {
                $db->rollBack();
                throw new \InvalidArgumentException("UPI Reference is required for UPI payments.");
            }

            if ($method === 'online') {
                if (empty($gatewayPaymentId)) {
                    $db->rollBack();
                    throw new \InvalidArgumentException("Gateway Payment ID is required for online payments.");
                }

                // Check for duplicate gateway payment (Idempotency)
                $dupStmt = $db->prepare("SELECT id, order_id, amount FROM payments WHERE gateway_payment_id = :gpid");
                $dupStmt->execute(['gpid' => $gatewayPaymentId]);
                $existingPay = $dupStmt->fetch();
                if ($existingPay) {
                    $db->commit();
                    return [
                        'payment_id'       => (int)$existingPay['id'],
                        'amount_paid'      => (float)$order['amount_paid'],
                        'payment_status'   => $order['payment_status'],
                        'remaining'        => round((float)$order['total_amount'] - (float)$order['amount_paid'], 2),
                        'already_recorded' => true
                    ];
                }
            }

            // 4. Validate monetary amount
            if (!is_numeric($rawAmount) || (float)$rawAmount <= 0) {
                $db->rollBack();
                throw new \InvalidArgumentException("Payment amount must be a positive number greater than zero.");
            }
            $amount = round((float)$rawAmount, 2);

            $totalAmount  = round((float)$order['total_amount'], 2);
            $currentPaid  = round((float)$order['amount_paid'], 2);
            $remainingBal = round($totalAmount - $currentPaid, 2);

            if ($remainingBal <= 0) {
                $db->rollBack();
                throw new \DomainException("This order is already fully paid.");
            }

            if ($amount > $remainingBal + 0.001) {
                $db->rollBack();
                throw new \DomainException("Payment amount (₹" . number_format($amount, 2) . ") exceeds remaining balance (₹" . number_format($remainingBal, 2) . ").");
            }

            // 5. Insert payment record
            $payStmt = $db->prepare("INSERT INTO payments (
                order_id, recorded_by, amount, payment_method, upi_reference,
                gateway, gateway_order_id, gateway_payment_id, gateway_signature
            ) VALUES (
                :order_id, :recorded_by, :amount, :payment_method, :upi_reference,
                :gateway, :gateway_order_id, :gateway_payment_id, :gateway_signature
            )");
            $payStmt->execute([
                'order_id'           => $orderId,
                'recorded_by'        => $userId,
                'amount'             => $amount,
                'payment_method'     => $method,
                'upi_reference'      => ($method === 'upi') ? $upiRef : null,
                'gateway'            => $gateway,
                'gateway_order_id'   => $gatewayOrderId,
                'gateway_payment_id' => $gatewayPaymentId,
                'gateway_signature'  => $gatewaySignature
            ]);
            $paymentId = (int)$db->lastInsertId();

            // 6. Recalculate amount_paid and payment_status
            $newPaid = round($currentPaid + $amount, 2);
            if ($newPaid >= $totalAmount - 0.001) {
                $newStatus = 'paid';
                $newPaid   = $totalAmount; // Cap exact total_amount
            } elseif ($newPaid > 0) {
                $newStatus = 'partial';
            } else {
                $newStatus = 'unpaid';
            }

            // 7. Update orders table
            $updOrder = $db->prepare("UPDATE orders SET amount_paid = :amount_paid, payment_status = :payment_status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $updOrder->execute([
                'amount_paid'    => $newPaid,
                'payment_status' => $newStatus,
                'id'             => $orderId
            ]);

            $db->commit();

            // Phase 14A: Automated customer notification for payment recorded
            try {
                $orderNum = !empty($order['job_id']) ? (string)$order['job_id'] : ('#' . $orderId);
                NotificationService::notifyPaymentRecorded(
                    $paymentId,
                    $orderId,
                    (int)$order['customer_id'],
                    $orderNum,
                    $amount,
                    $method
                );
            } catch (\Throwable $ne) {
                error_log("Payment recorded notification failed for payment #{$paymentId}: " . $ne->getMessage());
            }

            return [
                'payment_id'       => $paymentId,
                'amount_paid'      => $newPaid,
                'payment_status'   => $newStatus,
                'remaining'        => round($totalAmount - $newPaid, 2),
                'already_recorded' => false
            ];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function getByOrderId(int $orderId): array {
        $db = Database::getConnection();
        $sql = "SELECT p.*, u.name AS recorded_by_name
                FROM payments p
                LEFT JOIN users u ON p.recorded_by = u.id
                WHERE p.order_id = :order_id
                ORDER BY p.id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetchAll();
    }

    public static function getByGatewayPaymentId(string $gatewayPaymentId): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM payments WHERE gateway_payment_id = :gpid LIMIT 1");
        $stmt->execute(['gpid' => $gatewayPaymentId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function isGatewayPaymentProcessed(string $gatewayPaymentId): bool {
        return self::getByGatewayPaymentId($gatewayPaymentId) !== null;
    }
}
