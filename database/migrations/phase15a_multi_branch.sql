-- Phase 15A Multi-Branch / Multi-Location Foundation Migration

-- 1. Create branches table
CREATE TABLE IF NOT EXISTS `branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_branches_code` (`code`),
  KEY `idx_branches_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Seed initial Main Branch using existing business_settings values
INSERT INTO `branches` (`id`, `name`, `code`, `phone`, `address`, `is_active`)
SELECT 1, COALESCE(NULLIF(business_name, ''), 'Main Branch'), 'MAIN', whatsapp_number, address, 1
FROM `business_settings` WHERE `id` = 1
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Fallback seed if business_settings had no row
INSERT IGNORE INTO `branches` (`id`, `name`, `code`, `phone`, `address`, `is_active`)
VALUES (1, 'Main Branch', 'MAIN', NULL, NULL, 1);

-- 3. Add orders.branch_id temporarily nullable
ALTER TABLE `orders` ADD COLUMN `branch_id` bigint(20) unsigned DEFAULT NULL AFTER `customer_id`;

-- 4. Backfill all existing orders to the initial branch (id 1)
UPDATE `orders` SET `branch_id` = 1 WHERE `branch_id` IS NULL;

-- 5 & 6. Enforce NOT NULL on orders.branch_id
ALTER TABLE `orders` MODIFY COLUMN `branch_id` bigint(20) unsigned NOT NULL;

-- 7 & 8. Add FK and justified indexes to orders
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD KEY `idx_orders_branch` (`branch_id`),
  ADD KEY `idx_orders_branch_status` (`branch_id`, `status`),
  ADD KEY `idx_orders_branch_created` (`branch_id`, `created_at`);

-- 9. Add users.branch_id nullable
ALTER TABLE `users` ADD COLUMN `branch_id` bigint(20) unsigned DEFAULT NULL AFTER `role`;

-- 10. Backfill existing staff to initial branch (id 1)
UPDATE `users` SET `branch_id` = 1 WHERE `role` = 'staff' AND `branch_id` IS NULL;

-- 11 & 12: Admin and customer branch_id remain NULL

-- 13. Add FK and index to users.branch_id
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD KEY `idx_users_branch` (`branch_id`);
