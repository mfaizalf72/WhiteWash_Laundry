<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Services\Payment;

class MockPaymentGateway implements PaymentGatewayInterface {
    private string $secret;
    private string $keyId;

    public function __construct(?string $secret = null, ?string $keyId = null) {
        $this->secret = $secret ?: 'mock_secret_laundry_lms_sandbox';
        $this->keyId  = $keyId ?: 'mock_key_public';
    }

    public function getDriverName(): string {
        return 'mock';
    }

    public function getPublicKey(): string {
        return $this->keyId;
    }

    public function createOrder(int $orderId, float $amount, string $currency = 'INR', array $metadata = []): array {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Payment amount must be greater than zero.");
        }

        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        $gatewayOrderId   = "mock_ord_{$orderId}_{$timestamp}_{$random}";
        $mockPaymentId    = "mock_pay_{$orderId}_{$timestamp}_{$random}";
        $mockSignature    = $this->generateSignature($gatewayOrderId, $mockPaymentId);

        return [
            'gateway'            => 'mock',
            'gateway_order_id'   => $gatewayOrderId,
            'gateway_payment_id' => $mockPaymentId,
            'gateway_signature'  => $mockSignature,
            'amount'             => round($amount, 2),
            'currency'           => strtoupper($currency),
            'key_id'             => $this->keyId,
            'metadata'           => $metadata
        ];
    }

    public function generateSignature(string $gatewayOrderId, string $gatewayPaymentId): string {
        return hash_hmac('sha256', $gatewayOrderId . '|' . $gatewayPaymentId, $this->secret);
    }

    public function verifySignature(array $payload): bool {
        // If simulated failure requested in payload
        if (!empty($payload['mock_status']) && $payload['mock_status'] === 'failed') {
            return false;
        }

        $gatewayOrderId   = trim((string)($payload['gateway_order_id'] ?? ''));
        $gatewayPaymentId = trim((string)($payload['gateway_payment_id'] ?? ''));
        $signature        = trim((string)($payload['gateway_signature'] ?? ''));

        if (empty($gatewayOrderId) || empty($gatewayPaymentId) || empty($signature)) {
            return false;
        }

        $expected = $this->generateSignature($gatewayOrderId, $gatewayPaymentId);
        return hash_equals($expected, $signature);
    }

    public function generateWebhookSignature(string $rawPayload): string {
        return hash_hmac('sha256', $rawPayload, $this->secret);
    }

    public function verifyWebhook(string $rawPayload, string $signatureHeader): array {
        $expected = $this->generateWebhookSignature($rawPayload);

        if (!hash_equals($expected, $signatureHeader)) {
            return [
                'valid'   => false,
                'error'   => 'Invalid webhook signature.',
                'payload' => null
            ];
        }

        $data = json_decode($rawPayload, true);
        if (!is_array($data)) {
            return [
                'valid'   => false,
                'error'   => 'Malformed JSON in webhook payload.',
                'payload' => null
            ];
        }

        $eventId   = (string)($data['event_id'] ?? '');
        $eventType = (string)($data['event'] ?? $data['event_type'] ?? '');
        $orderId   = isset($data['order_id']) ? (int)$data['order_id'] : null;
        $gOrderId  = (string)($data['gateway_order_id'] ?? '');
        $gPayId    = (string)($data['gateway_payment_id'] ?? '');
        $amount    = isset($data['amount']) ? round((float)$data['amount'], 2) : null;

        if (empty($eventId) || empty($eventType)) {
            return [
                'valid'   => false,
                'error'   => 'Missing event_id or event_type in webhook.',
                'payload' => $data
            ];
        }

        return [
            'valid'              => true,
            'gateway'            => 'mock',
            'event_id'           => $eventId,
            'event_type'         => $eventType,
            'order_id'           => $orderId,
            'gateway_order_id'   => $gOrderId,
            'gateway_payment_id' => $gPayId,
            'amount'             => $amount,
            'payload'            => $data,
            'error'              => null
        ];
    }
}
