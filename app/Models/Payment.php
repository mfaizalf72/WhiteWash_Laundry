<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Models;

use Faizal\LaundryLms\Core\Database;
use PDO;

class Payment {
    public static function create(array $paymentData, int $userId): array {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $orderId   = (int)($paymentData['order_id'] ?? 0);
            $method    = trim($paymentData['payment_method'] ?? '');
            $rawAmount = trim((string)($paymentData['amount'] ?? ''));
            $upiRef    = trim($paymentData['upi_reference'] ?? '');

            // 1. Lock and read target order row
            $stmt = $db->prepare("SELECT id, total_amount, amount_paid, payment_status, status FROM orders WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $db->rollBack();
                throw new \InvalidArgumentException("Order not found.");
            }

            // 2. Validate payment method
            if (!in_array($method, ['cash', 'upi'], true)) {
                $db->rollBack();
                throw new \InvalidArgumentException("Invalid payment method. Only 'cash' and 'upi' are allowed.");
            }

            // 3. Validate UPI reference
            if ($method === 'upi' && empty($upiRef)) {
                $db->rollBack();
                throw new \InvalidArgumentException("UPI Reference is required for UPI payments.");
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
            $payStmt = $db->prepare("INSERT INTO payments (order_id, recorded_by, amount, payment_method, upi_reference) VALUES (:order_id, :recorded_by, :amount, :payment_method, :upi_reference)");
            $payStmt->execute([
                'order_id'       => $orderId,
                'recorded_by'    => $userId,
                'amount'         => $amount,
                'payment_method' => $method,
                'upi_reference'  => ($method === 'upi') ? $upiRef : null
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

            return [
                'payment_id'     => $paymentId,
                'amount_paid'    => $newPaid,
                'payment_status' => $newStatus,
                'remaining'      => round($totalAmount - $newPaid, 2)
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
}
