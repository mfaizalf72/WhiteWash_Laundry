-- Phase 13B: Online Payment Refunds Schema Migration
-- Creates the refunds table for tracking refund intents, gateway reversals, and financial settlements

CREATE TABLE IF NOT EXISTS `refunds` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `payment_id` BIGINT UNSIGNED NOT NULL,
    `recorded_by` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `reason` VARCHAR(255) NOT NULL,
    `gateway` VARCHAR(50) NOT NULL,
    `gateway_payment_id` VARCHAR(100) NOT NULL,
    `gateway_refund_id` VARCHAR(100) DEFAULT NULL,
    `gateway_error` VARCHAR(255) DEFAULT NULL,
    `idempotency_key` VARCHAR(100) NOT NULL,
    `status` ENUM('pending', 'processed', 'failed') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `processed_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `uq_refunds_idempotency` (`idempotency_key`),
    UNIQUE KEY `uq_refunds_gateway_refund` (`gateway_refund_id`),
    INDEX `idx_refunds_order` (`order_id`),
    INDEX `idx_refunds_payment` (`payment_id`),
    INDEX `idx_refunds_status` (`status`),
    CONSTRAINT `fk_refunds_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_refunds_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_refunds_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
