<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers;

use Faizal\LaundryLms\Core\Database;
use Faizal\LaundryLms\Models\Order;
use Faizal\LaundryLms\Models\Payment;
use Faizal\LaundryLms\Services\Payment\PaymentGatewayFactory;

class PaymentWebhookController {
    public function handle(): void {
        header('Content-Type: application/json');

        $rawPayload = file_get_contents('php://input');
        if (empty($rawPayload)) {
            http_response_code(400);
            echo json_encode(['error' => 'Empty webhook payload.']);
            exit;
        }

        // Determine driver based on header or environment
        $driver = getenv('PAYMENT_GATEWAY_DRIVER') ?: 'mock';
        $signatureHeader = '';

        if (isset($_SERVER['HTTP_X_RAZORPAY_SIGNATURE'])) {
            $driver = 'razorpay';
            $signatureHeader = (string)$_SERVER['HTTP_X_RAZORPAY_SIGNATURE'];
        } elseif (isset($_SERVER['HTTP_X_MOCK_SIGNATURE'])) {
            $driver = 'mock';
            $signatureHeader = (string)$_SERVER['HTTP_X_MOCK_SIGNATURE'];
        } elseif (isset($_SERVER['HTTP_X_SIGNATURE'])) {
            $signatureHeader = (string)$_SERVER['HTTP_X_SIGNATURE'];
        }

        try {
            $gateway = PaymentGatewayFactory::create($driver);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Unsupported gateway driver.']);
            exit;
        }

        // Cryptographic verification
        $verification = $gateway->verifyWebhook($rawPayload, $signatureHeader);
        if (empty($verification['valid'])) {
            http_response_code(400);
            echo json_encode(['error' => $verification['error'] ?? 'Invalid signature.']);
            exit;
        }

        $gatewayName = $verification['gateway'] ?? $driver;
        $eventId     = (string)($verification['event_id'] ?? '');
        $eventType   = (string)($verification['event_type'] ?? '');

        if (empty($eventId) || empty($eventType)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing event identifier or type.']);
            exit;
        }

        $db = Database::getConnection();

        // 1. Deduplicate webhook event: check if event was already successfully processed
        $checkEvent = $db->prepare("SELECT id FROM payment_webhooks WHERE gateway = :gw AND event_id = :eid LIMIT 1");
        $checkEvent->execute(['gw' => $gatewayName, 'eid' => $eventId]);
        if ($checkEvent->fetch()) {
            // Already processed! Return 200 OK immediately for idempotency
            http_response_code(200);
            echo json_encode(['status' => 'already_processed', 'event_id' => $eventId]);
            exit;
        }

        // 2. Process supported events (e.g. payment.captured, order.paid)
        $supportedEvents = ['payment.captured', 'order.paid'];
        if (!in_array($eventType, $supportedEvents, true)) {
            // Record ignored event so retries do not re-parse
            $insEvent = $db->prepare("INSERT INTO payment_webhooks (gateway, event_id, event_type, payload) VALUES (:gw, :eid, :etype, :payload)");
            try {
                $insEvent->execute([
                    'gw'      => $gatewayName,
                    'eid'     => $eventId,
                    'etype'   => $eventType,
                    'payload' => $rawPayload
                ]);
            } catch (\PDOException $e) {
                // Ignore race
            }

            http_response_code(200);
            echo json_encode(['status' => 'ignored_event_type', 'event_type' => $eventType]);
            exit;
        }

        $orderId          = $verification['order_id'] ?? null;
        $gatewayOrderId   = $verification['gateway_order_id'] ?? null;
        $gatewayPaymentId = $verification['gateway_payment_id'] ?? null;
        $amount           = $verification['amount'] ?? null;

        if (empty($gatewayPaymentId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing gateway payment ID in event payload.']);
            exit;
        }

        // Validate internal order correlation strictly from verified signed payload
        if (empty($orderId) || (int)$orderId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Unable to correlate webhook event to an internal order.']);
            exit;
        }

        $order = Order::find((int)$orderId);
        if (!$order) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found.']);
            exit;
        }

        if ($order['status'] === 'cancelled') {
            // Record so we do not reprocess cancelled orders
            $insEvent = $db->prepare("INSERT INTO payment_webhooks (gateway, event_id, event_type, payload) VALUES (:gw, :eid, :etype, :payload)");
            try {
                $insEvent->execute([
                    'gw'      => $gatewayName,
                    'eid'     => $eventId,
                    'etype'   => $eventType,
                    'payload' => $rawPayload
                ]);
            } catch (\PDOException $e) {
                // Ignore race
            }

            http_response_code(200);
            echo json_encode(['status' => 'cancelled_order_payment_received', 'message' => 'Order is cancelled.']);
            exit;
        }

        $recordedBy = (int)$order['customer_id'];

        // 3. Financial settlement and post-settlement webhook logging (SEC-P1-01)
        try {
            $paymentResult = Payment::create([
                'order_id'           => (int)$orderId,
                'payment_method'     => 'online',
                'amount'             => (float)$amount,
                'gateway'            => $gatewayName,
                'gateway_order_id'   => $gatewayOrderId,
                'gateway_payment_id' => $gatewayPaymentId,
                'gateway_signature'  => $signatureHeader
            ], $recordedBy);

            // Record webhook receipt in payment_webhooks ONLY after successful settlement
            $insEvent = $db->prepare("INSERT INTO payment_webhooks (gateway, event_id, event_type, payload) VALUES (:gw, :eid, :etype, :payload)");
            try {
                $insEvent->execute([
                    'gw'      => $gatewayName,
                    'eid'     => $eventId,
                    'etype'   => $eventType,
                    'payload' => $rawPayload
                ]);
            } catch (\PDOException $e) {
                // Duplicate event race (another concurrent worker processed simultaneously) - safe
            }

            http_response_code(200);
            echo json_encode([
                'status'           => 'success',
                'order_id'         => (int)$orderId,
                'already_recorded' => $paymentResult['already_recorded'] ?? false
            ]);
            exit;
        } catch (\Throwable $e) {
            // Transient failure: DO NOT insert into payment_webhooks!
            // Return 500 so gateway retry mechanism can deliver the event again.
            http_response_code(500);
            echo json_encode(['error' => 'Failed to record payment: ' . $e->getMessage()]);
            exit;
        }
    }
}
