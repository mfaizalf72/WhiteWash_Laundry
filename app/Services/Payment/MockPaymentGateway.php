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

        $refundId   = isset($data['refund_id']) ? (int)$data['refund_id'] : (isset($data['notes']['refund_id']) ? (int)$data['notes']['refund_id'] : (isset($data['payload']['refund']['entity']['notes']['refund_id']) ? (int)$data['payload']['refund']['entity']['notes']['refund_id'] : null));
        $gRefundId  = !empty($data['gateway_refund_id']) ? (string)$data['gateway_refund_id'] : (!empty($data['payload']['refund']['entity']['id']) ? (string)$data['payload']['refund']['entity']['id'] : null);
        if (empty($gPayId) && !empty($data['payload']['refund']['entity']['payment_id'])) {
            $gPayId = (string)$data['payload']['refund']['entity']['payment_id'];
        }

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
            'refund_id'          => $refundId,
            'gateway_order_id'   => $gOrderId,
            'gateway_payment_id' => $gPayId,
            'gateway_refund_id'  => $gRefundId,
            'amount'             => $amount,
            'payload'            => $data,
            'error'              => null
        ];
    }

    public function createRefund(string $gatewayPaymentId, float $amount, array $options = []): array {
        if ($amount <= 0) {
            return [
                'success'           => false,
                'gateway_refund_id' => null,
                'status'            => 'failed',
                'error'             => 'Refund amount must be greater than zero.'
            ];
        }

        if (empty($gatewayPaymentId)) {
            return [
                'success'           => false,
                'gateway_refund_id' => null,
                'status'            => 'failed',
                'error'             => 'Gateway payment ID is required for refunds.'
            ];
        }

        // Support simulated failures in testing
        if (!empty($options['mock_status']) && $options['mock_status'] === 'failed') {
            return [
                'success'           => false,
                'gateway_refund_id' => null,
                'status'            => 'failed',
                'error'             => 'Simulated gateway refund failure.'
            ];
        }
        if (!empty($options['reason']) && $options['reason'] === 'simulated_failure') {
            return [
                'success'           => false,
                'gateway_refund_id' => null,
                'status'            => 'failed',
                'error'             => 'Simulated gateway refund failure.'
            ];
        }
        if ($amount == 9999.00) {
            return [
                'success'           => false,
                'gateway_refund_id' => null,
                'status'            => 'failed',
                'error'             => 'Simulated amount failure.'
            ];
        }

        $refundId = $options['refund_id'] ?? bin2hex(random_bytes(4));
        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        $mockRefundId = "mock_rfnd_{$refundId}_{$timestamp}_{$random}";

        // Support simulated pending state
        if (!empty($options['mock_status']) && $options['mock_status'] === 'pending') {
            return [
                'success'           => true,
                'gateway_refund_id' => $mockRefundId,
                'status'            => 'pending',
                'error'             => null
            ];
        }

        return [
            'success'           => true,
            'gateway_refund_id' => $mockRefundId,
            'status'            => 'processed',
            'error'             => null
        ];
    }
}
