<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Models;

use Faizal\LaundryLms\Core\Database;
use PDO;
use PDOException;

class Notification {
    /**
     * Create an in-app notification record with strict idempotency.
     * If the idempotency key already exists, returns the existing notification ID without error.
     *
     * @param array $data
     * @return int|null Notification ID
     */
    public static function create(array $data): ?int {
        $userId         = (int)($data['user_id'] ?? 0);
        $orderId        = !empty($data['order_id']) ? (int)$data['order_id'] : null;
        $type           = trim((string)($data['type'] ?? ''));
        $channel        = !empty($data['channel']) ? trim((string)$data['channel']) : 'in_app';
        $title          = trim((string)($data['title'] ?? ''));
        $message        = trim((string)($data['message'] ?? ''));
        $idempotencyKey = trim((string)($data['idempotency_key'] ?? ''));

        if ($userId <= 0 || empty($type) || empty($title) || empty($message) || empty($idempotencyKey)) {
            throw new \InvalidArgumentException("Missing required notification attributes.");
        }

        $db = Database::getConnection();

        // 1. Idempotency check: return existing if key was already processed
        $existing = self::findByIdempotencyKey($idempotencyKey);
        if ($existing !== null) {
            return (int)$existing['id'];
        }

        try {
            $stmt = $db->prepare("
                INSERT INTO notifications (
                    user_id, order_id, type, channel, title, message, idempotency_key, is_read, created_at
                ) VALUES (
                    :user_id, :order_id, :type, :channel, :title, :message, :idempotency_key, 0, CURRENT_TIMESTAMP
                )
            ");
            $stmt->execute([
                'user_id'         => $userId,
                'order_id'        => $orderId,
                'type'            => $type,
                'channel'         => $channel,
                'title'           => $title,
                'message'         => $message,
                'idempotency_key' => $idempotencyKey,
            ]);

            return (int)$db->lastInsertId();
        } catch (PDOException $e) {
            // Check for duplicate key race condition (SQLSTATE 23000, error 1062)
            if ($e->getCode() === '23000' || ($e->errorInfo[1] ?? 0) === 1062) {
                $retryExisting = self::findByIdempotencyKey($idempotencyKey);
                if ($retryExisting !== null) {
                    return (int)$retryExisting['id'];
                }
            }
            throw $e;
        }
    }

    /**
     * Get paginated notifications for a customer, ordered by newest first.
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getForUser(int $userId, int $limit = 50, int $offset = 0): array {
        $db = Database::getConnection();
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);

        $stmt = $db->prepare("
            SELECT n.*, o.job_id
            FROM notifications n
            LEFT JOIN orders o ON n.order_id = o.id
            WHERE n.user_id = :uid
            ORDER BY n.id DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get unread notification count for a customer.
     *
     * @param int $userId
     * @return int
     */
    public static function getUnreadCount(int $userId): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM notifications
            WHERE user_id = :uid AND is_read = 0
        ");
        $stmt->execute(['uid' => $userId]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Mark a specific notification as read, strictly scoped to the user ID.
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public static function markAsRead(int $id, int $userId): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE notifications
            SET is_read = 1, read_at = CURRENT_TIMESTAMP
            WHERE id = :id AND user_id = :uid
        ");
        $stmt->execute(['id' => $id, 'uid' => $userId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Mark all unread notifications as read for a customer.
     *
     * @param int $userId
     * @return int Number of rows updated
     */
    public static function markAllAsRead(int $userId): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE notifications
            SET is_read = 1, read_at = CURRENT_TIMESTAMP
            WHERE user_id = :uid AND is_read = 0
        ");
        $stmt->execute(['uid' => $userId]);

        return (int)$stmt->rowCount();
    }

    /**
     * Find a notification by ID strictly belonging to a specific user.
     *
     * @param int $id
     * @param int $userId
     * @return array|null
     */
    public static function find(int $id, int $userId): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT n.*, o.job_id
            FROM notifications n
            LEFT JOIN orders o ON n.order_id = o.id
            WHERE n.id = :id AND n.user_id = :uid
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Find a notification by unique idempotency key.
     *
     * @param string $key
     * @return array|null
     */
    public static function findByIdempotencyKey(string $key): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE idempotency_key = :k LIMIT 1");
        $stmt->execute(['k' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
