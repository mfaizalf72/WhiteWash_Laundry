<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Services\Payment;

interface PaymentGatewayInterface {
    /**
     * Get the driver name identifier (e.g. 'mock', 'razorpay').
     */
    public function getDriverName(): string;

    /**
     * Get the public client key/identifier for frontend checkout SDK (if any).
     */
    public function getPublicKey(): string;

    /**
     * Create a gateway order / payment intent for an order.
     *
     * @param int $orderId Internal LMS order ID
     * @param float $amount Amount to charge in rupees (e.g. 450.00)
     * @param string $currency Currency code (default 'INR')
     * @param array $metadata Additional context metadata
     * @return array Gateway order details: [
     *     'gateway'          => string,
     *     'gateway_order_id' => string,
     *     'amount'           => float,
     *     'currency'         => string,
     *     'key_id'           => string,
     *     'metadata'         => array
     * ]
     */
    public function createOrder(int $orderId, float $amount, string $currency = 'INR', array $metadata = []): array;

    /**
     * Verify client checkout signature / callback token.
     *
     * @param array $payload Verification payload containing gateway_order_id, gateway_payment_id, gateway_signature, etc.
     * @return bool True if signature is valid and authentic
     */
    public function verifySignature(array $payload): bool;

    /**
     * Verify and parse server-to-server webhook request.
     *
     * @param string $rawPayload Raw HTTP request body string
     * @param string $signatureHeader Value of signature HTTP header (e.g. X-Razorpay-Signature)
     * @return array [
     *     'valid'        => bool,
     *     'event_id'     => ?string,
     *     'event_type'   => ?string,
     *     'order_id'     => ?int,
     *     'gateway_order_id'   => ?string,
     *     'gateway_payment_id' => ?string,
     *     'amount'       => ?float,
     *     'payload'      => ?array,
     *     'error'        => ?string
     * ]
     */
    public function verifyWebhook(string $rawPayload, string $signatureHeader): array;

    /**
     * Issue a refund for an online payment via the gateway.
     *
     * @param string $gatewayPaymentId Gateway payment ID (e.g. 'pay_xxx')
     * @param float $amount Amount to refund in rupees (e.g. 100.00)
     * @param array $options Additional options: [
     *     'refund_id'       => int,     // LMS internal refund intent ID
     *     'order_id'        => int,     // LMS internal order ID
     *     'reason'          => string,  // Staff reason
     *     'idempotency_key' => string,  // Unique idempotency nonce
     * ]
     * @return array [
     *     'success'           => bool,
     *     'gateway_refund_id' => ?string,
     *     'status'            => string, // 'processed' | 'pending' | 'failed'
     *     'error'             => ?string
     * ]
     */
    public function createRefund(string $gatewayPaymentId, float $amount, array $options = []): array;
}
