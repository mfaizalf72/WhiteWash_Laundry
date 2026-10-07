<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Services\Notification;

use Faizal\LaundryLms\Models\Notification;

class InAppChannel implements NotificationChannelInterface {
    public function getChannelName(): string {
        return 'in_app';
    }

    public function send(
        int $userId,
        ?int $orderId,
        string $type,
        string $title,
        string $message,
        string $idempotencyKey
    ): bool {
        $result = Notification::create([
            'user_id'         => $userId,
            'order_id'        => $orderId,
            'type'            => $type,
            'channel'         => $this->getChannelName(),
            'title'           => $title,
            'message'         => $message,
            'idempotency_key' => $idempotencyKey,
        ]);

        return $result !== null;
    }
}
