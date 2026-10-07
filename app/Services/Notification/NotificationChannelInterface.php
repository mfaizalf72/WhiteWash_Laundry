<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Services\Notification;

interface NotificationChannelInterface {
    /**
     * Get unique channel name identifier (e.g. 'in_app', 'whatsapp', 'sms').
     */
    public function getChannelName(): string;

    /**
     * Send notification to recipient user.
     *
     * @param int $userId Target recipient user ID
     * @param int|null $orderId Associated order ID if applicable
     * @param string $type Notification type category (e.g. 'order_created', 'order_status', 'payment_recorded')
     * @param string $title Short notification title
     * @param string $message Detailed notification body text
     * @param string $idempotencyKey Unique channel-scoped idempotency key
     * @return bool True if notification was sent or already handled
     */
    public function send(
        int $userId,
        ?int $orderId,
        string $type,
        string $title,
        string $message,
        string $idempotencyKey
    ): bool;
}
