-- Phase 13B P2-01: Add gateway_error column to refunds table for permanent failure audit
ALTER TABLE `refunds`
ADD COLUMN `gateway_error` VARCHAR(255) NULL DEFAULT NULL AFTER `gateway_refund_id`;
