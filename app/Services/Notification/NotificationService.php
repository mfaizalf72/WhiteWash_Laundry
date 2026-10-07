<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Services\Notification;

class NotificationService {
    /** @var NotificationChannelInterface[] */
    private static array $channels = [];

    /**
     * Get or register default channels.
     *
     * @return NotificationChannelInterface[]
     */
    public static function getChannels(): array {
        if (empty(self::$channels)) {
            self::$channels = [
                'in_app' => new InAppChannel(),
            ];
        }
        return self::$channels;
    }

    /**
     * Register or override a notification channel.
     *
     * @param NotificationChannelInterface $channel
     */
    public static function registerChannel(NotificationChannelInterface $channel): void {
        self::$channels[$channel->getChannelName()] = $channel;
    }

    /**
     * Dispatch notification to a specific channel or all registered channels.
     *
     * @param int $userId Target recipient user ID
     * @param int|null $orderId Associated order ID
     * @param string $type Notification type
     * @param string $title Title
     * @param string $message Body text
     * @param string $idempotencyKey Channel-scoped idempotency key
     * @param string|null $channelSpecific Specific channel to dispatch to (null = all)
     */
    public static function dispatch(
        int $userId,
        ?int $orderId,
        string $type,
        string $title,
        string $message,
        string $idempotencyKey,
        ?string $channelSpecific = 'in_app'
    ): void {
        try {
            $channels = self::getChannels();

            if ($channelSpecific !== null) {
                if (isset($channels[$channelSpecific])) {
                    $channels[$channelSpecific]->send($userId, $orderId, $type, $title, $message, $idempotencyKey);
                } else {
                    error_log("NotificationService: Requested channel '{$channelSpecific}' is not registered.");
                }
                return;
            }

            foreach ($channels as $channel) {
                try {
                    $channel->send($userId, $orderId, $type, $title, $message, $idempotencyKey);
                } catch (\Throwable $e) {
                    error_log("NotificationService [{$channel->getChannelName()}] dispatch failed: " . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            error_log("NotificationService: Unexpected dispatch error: " . $e->getMessage());
        }
    }

    /**
     * Notify customer when a new order is placed.
     */
    public static function notifyOrderCreated(int $orderId, int $customerId, string $orderNumber): void {
        try {
            $idempotencyKey = "in_app_order_{$orderId}_created";
            $title   = "Order Received";
            $message = "Your order {$orderNumber} has been received and scheduled for pickup.";

            self::dispatch(
                $customerId,
                $orderId,
                'order_created',
                $title,
                $message,
                $idempotencyKey,
                'in_app'
            );
        } catch (\Throwable $e) {
            error_log("NotificationService::notifyOrderCreated failed for order #{$orderId}: " . $e->getMessage());
        }
    }

    /**
     * Notify customer on order lifecycle status progression.
     */
    public static function notifyOrderStatusUpdated(
        int $orderId,
        int $customerId,
        string $orderNumber,
        string $newStatus,
        ?string $notes = null
    ): void {
        try {
            $idempotencyKey = "in_app_order_{$orderId}_status_{$newStatus}";

            $statusMessages = [
                'pickup_request' => [
                    'title'   => 'Pickup Request Received',
                    'message' => "Your order {$orderNumber} has been received and scheduled for pickup.",
                ],
                'accepted' => [
                    'title'   => 'Order Accepted',
                    'message' => "Your order {$orderNumber} has been accepted by our team.",
                ],
                'picked_up' => [
                    'title'   => 'Clothes Picked Up',
                    'message' => "Your laundry for order {$orderNumber} has been collected and is heading to our workshop.",
                ],
                'inspection_completed' => [
                    'title'   => 'Inspection Completed',
                    'message' => "Inspection is complete for order {$orderNumber}. Please review the bill and confirm your approval.",
                ],
                'price_confirmed' => [
                    'title'   => 'Price Confirmed',
                    'message' => "Price has been confirmed for order {$orderNumber}. Your laundry is now queued for processing.",
                ],
                'processing' => [
                    'title'   => 'Processing Started',
                    'message' => "Your laundry for order {$orderNumber} is now being washed and processed.",
                ],
                'ready' => [
                    'title'   => 'Order Ready',
                    'message' => "Your order {$orderNumber} is clean, packed, and ready for delivery.",
                ],
                'out_for_delivery' => [
                    'title'   => 'Out for Delivery',
                    'message' => "Your order {$orderNumber} is out for delivery. Our driver will reach you soon.",
                ],
                'delivered' => [
                    'title'   => 'Order Delivered',
                    'message' => "Your order {$orderNumber} has been delivered. Thank you for choosing our laundry service!",
                ],
                'completed' => [
                    'title'   => 'Order Completed',
                    'message' => "Order {$orderNumber} has been completed and fully settled.",
                ],
                'cancelled' => [
                    'title'   => 'Order Cancelled',
                    'message' => "Order {$orderNumber} has been cancelled.",
                ],
            ];

            if (isset($statusMessages[$newStatus])) {
                $title   = $statusMessages[$newStatus]['title'];
                $message = $statusMessages[$newStatus]['message'];
            } else {
                $friendly = ucwords(str_replace('_', ' ', $newStatus));
                $title    = "Order Status Updated";
                $message  = "Order {$orderNumber} status changed to {$friendly}.";
            }

            if (!empty($notes)) {
                $message .= " Note: " . $notes;
            }

            self::dispatch(
                $customerId,
                $orderId,
                'order_status',
                $title,
                $message,
                $idempotencyKey,
                'in_app'
            );
        } catch (\Throwable $e) {
            error_log("NotificationService::notifyOrderStatusUpdated failed for order #{$orderId}, status {$newStatus}: " . $e->getMessage());
        }
    }

    /**
     * Notify customer when a payment is recorded.
     */
    public static function notifyPaymentRecorded(
        int $paymentId,
        int $orderId,
        int $customerId,
        string $orderNumber,
        float $amount,
        string $method
    ): void {
        try {
            $idempotencyKey = "in_app_payment_{$paymentId}_recorded";
            $formattedAmount = number_format($amount, 2);
            $methodUpper = strtoupper($method);

            $title   = "Payment Received";
            $message = "We have received a payment of ₹{$formattedAmount} via {$methodUpper} for order {$orderNumber}.";

            self::dispatch(
                $customerId,
                $orderId,
                'payment_recorded',
                $title,
                $message,
                $idempotencyKey,
                'in_app'
            );
        } catch (\Throwable $e) {
            error_log("NotificationService::notifyPaymentRecorded failed for payment #{$paymentId}: " . $e->getMessage());
        }
    }

    /**
     * Notify customer when a refund is successfully processed.
     */
    public static function notifyRefundProcessed(
        int $refundId,
        int $orderId,
        int $customerId,
        string $orderNumber,
        float $amount
    ): void {
        try {
            $idempotencyKey = "in_app_refund_{$refundId}_processed";
            $formattedAmount = number_format($amount, 2);

            $title   = "Refund Processed";
            $message = "A refund of ₹{$formattedAmount} for order {$orderNumber} has been successfully processed.";

            self::dispatch(
                $customerId,
                $orderId,
                'refund_processed',
                $title,
                $message,
                $idempotencyKey,
                'in_app'
            );
        } catch (\Throwable $e) {
            error_log("NotificationService::notifyRefundProcessed failed for refund #{$refundId}: " . $e->getMessage());
        }
    }

    /**
     * Notify customer when an attempted refund fails.
     */
    public static function notifyRefundFailed(
        int $refundId,
        int $orderId,
        int $customerId,
        string $orderNumber,
        float $amount,
        ?string $reason = null
    ): void {
        try {
            $idempotencyKey = "in_app_refund_{$refundId}_failed";
            $formattedAmount = number_format($amount, 2);

            $title   = "Refund Failed";
            $message = "A refund of ₹{$formattedAmount} for order {$orderNumber} could not be completed.";
            if (!empty($reason)) {
                $message .= " Reason: {$reason}";
            }

            self::dispatch(
                $customerId,
                $orderId,
                'refund_failed',
                $title,
                $message,
                $idempotencyKey,
                'in_app'
            );
        } catch (\Throwable $e) {
            error_log("NotificationService::notifyRefundFailed failed for refund #{$refundId}: " . $e->getMessage());
        }
    }
}
