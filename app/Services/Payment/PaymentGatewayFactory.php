<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Services\Payment;

class PaymentGatewayFactory {
    public static function create(?string $driver = null): PaymentGatewayInterface {
        $driver = $driver ?: getenv('PAYMENT_GATEWAY_DRIVER') ?: 'mock';
        $driver = strtolower(trim($driver));

        switch ($driver) {
            case 'mock':
                $mockSecret = getenv('MOCK_PAYMENT_SECRET') ?: null;
                $mockKey    = getenv('MOCK_PAYMENT_KEY') ?: null;
                return new MockPaymentGateway($mockSecret, $mockKey);

            case 'razorpay':
                return new RazorpayGateway();

            default:
                throw new \InvalidArgumentException("Unsupported payment gateway driver: '{$driver}'. Supported drivers are 'mock' and 'razorpay'.");
        }
    }
}
