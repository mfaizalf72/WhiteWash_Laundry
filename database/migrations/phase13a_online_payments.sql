-- Phase 13A: Online Payment Gateway Schema Migration
-- 1. Extend payment_method ENUM to include 'online'
-- 2. Add gateway audit columns to payments table
-- 3. Add unique constraint on gateway_payment_id for duplicate prevention
-- 4. Create payment_webhooks table for webhook idempotency and deduplication

ALTER TABLE `payments`
    MODIFY COLUMN `payment_method` ENUM('cash', 'upi', 'online') NOT NULL DEFAULT 'cash',
    ADD COLUMN `gateway` VARCHAR(50) DEFAULT NULL AFTER `payment_method`,
    ADD COLUMN `gateway_order_id` VARCHAR(100) DEFAULT NULL AFTER `gateway`,
    ADD COLUMN `gateway_payment_id` VARCHAR(100) DEFAULT NULL AFTER `gateway_order_id`,
    ADD COLUMN `gateway_signature` VARCHAR(255) DEFAULT NULL AFTER `gateway_payment_id`,
    ADD UNIQUE KEY `uq_payments_gateway_payment` (`gateway_payment_id`),
    ADD INDEX `idx_payments_gateway_order` (`gateway_order_id`);

CREATE TABLE IF NOT EXISTS `payment_webhooks` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `gateway` VARCHAR(50) NOT NULL,
    `event_id` VARCHAR(100) NOT NULL,
    `event_type` VARCHAR(100) NOT NULL,
    `payload` LONGTEXT NULL,
    `processed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_webhook_event` (`gateway`, `event_id`),
    INDEX `idx_processed_at` (`processed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
