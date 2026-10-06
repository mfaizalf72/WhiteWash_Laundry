<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Services\Payment;

class RazorpayGateway implements PaymentGatewayInterface {
    private string $keyId;
    private string $keySecret;
    private string $webhookSecret;

    public function __construct(?string $keyId = null, ?string $keySecret = null, ?string $webhookSecret = null) {
        $this->keyId         = trim((string)($keyId ?: getenv('RAZORPAY_KEY_ID') ?: ''));
        $this->keySecret      = trim((string)($keySecret ?: getenv('RAZORPAY_KEY_SECRET') ?: ''));
        $this->webhookSecret = trim((string)($webhookSecret ?: getenv('RAZORPAY_WEBHOOK_SECRET') ?: ''));

        if (empty($this->keyId) || empty($this->keySecret)) {
            throw new \RuntimeException("Razorpay credentials (RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET) must be set in environment variables.");
        }
    }

    public function getDriverName(): string {
        return 'razorpay';
    }

    public function getPublicKey(): string {
        return $this->keyId;
    }

    public function createOrder(int $orderId, float $amount, string $currency = 'INR', array $metadata = []): array {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Payment amount must be greater than zero.");
        }

        // Razorpay expects amount in smallest currency unit (paise for INR)
        $amountInPaise = (int)round($amount * 100);
        $receipt = substr("ord_{$orderId}_" . time(), 0, 40);

        $payload = [
            'amount'   => $amountInPaise,
            'currency' => strtoupper($currency),
            'receipt'  => $receipt,
            'notes'    => array_merge(['order_id' => (string)$orderId], $metadata)
        ];

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_USERPWD        => "{$this->keyId}:{$this->keySecret}",
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($curlError)) {
            throw new \RuntimeException("Razorpay API connection error: " . $curlError);
        }

        $result = json_decode((string)$response, true);
        if ($httpCode < 200 || $httpCode >= 300 || empty($result['id'])) {
            $errorDesc = $result['error']['description'] ?? 'Failed to create Razorpay order.';
            throw new \RuntimeException("Razorpay Order Creation Failed (HTTP {$httpCode}): {$errorDesc}");
        }

        return [
            'gateway'          => 'razorpay',
            'gateway_order_id' => (string)$result['id'],
            'amount'           => round($amount, 2),
            'currency'         => strtoupper($currency),
            'key_id'           => $this->keyId,
            'metadata'         => $metadata
        ];
    }

    public function verifySignature(array $payload): bool {
        $orderId   = trim((string)($payload['gateway_order_id'] ?? ''));
        $paymentId = trim((string)($payload['gateway_payment_id'] ?? ''));
        $signature = trim((string)($payload['gateway_signature'] ?? ''));

        if (empty($orderId) || empty($paymentId) || empty($signature)) {
            return false;
        }

        $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->keySecret);
        return hash_equals($expected, $signature);
    }

    public function verifyWebhook(string $rawPayload, string $signatureHeader): array {
        if (empty($this->webhookSecret)) {
            return [
                'valid'   => false,
                'error'   => 'Razorpay webhook secret is not configured on server.',
                'payload' => null
            ];
        }

        $expected = hash_hmac('sha256', $rawPayload, $this->webhookSecret);
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
                'error'   => 'Malformed JSON in webhook body.',
                'payload' => null
            ];
        }

        $eventId   = (string)($data['id'] ?? '');
        $eventType = (string)($data['event'] ?? '');
        $payment   = $data['payload']['payment']['entity'] ?? [];
        $order     = $data['payload']['order']['entity'] ?? [];

        $gPaymentId = !empty($payment['id']) ? (string)$payment['id'] : null;
        $gOrderId   = !empty($payment['order_id']) ? (string)$payment['order_id'] : (!empty($order['id']) ? (string)$order['id'] : null);
        $amountPaise = $payment['amount'] ?? ($order['amount_paid'] ?? 0);
        $amount      = round(((float)$amountPaise) / 100, 2);

        $internalOrderId = null;
        if (!empty($payment['notes']['order_id'])) {
            $internalOrderId = (int)$payment['notes']['order_id'];
        } elseif (!empty($order['notes']['order_id'])) {
            $internalOrderId = (int)$order['notes']['order_id'];
        } elseif (!empty($order['receipt']) && preg_match('/^ord_(\d+)_/', (string)$order['receipt'], $matches)) {
            $internalOrderId = (int)$matches[1];
        }

        return [
            'valid'              => true,
            'gateway'            => 'razorpay',
            'event_id'           => $eventId,
            'event_type'         => $eventType,
            'order_id'           => $internalOrderId,
            'gateway_order_id'   => $gOrderId,
            'gateway_payment_id' => $gPaymentId,
            'amount'             => $amount,
            'payload'            => $data,
            'error'              => null
        ];
    }
}
