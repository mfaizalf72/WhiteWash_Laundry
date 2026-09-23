-- Phase 10C: Multi-Item Orders / Order Lines Table
-- Creates the order_items table with snapshot unit pricing, display ordering, and relational integrity.

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  `work_type_id` int(10) unsigned NOT NULL,
  `quantity` decimal(8,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pricing_type` enum('per_item','per_kg') NOT NULL DEFAULT 'per_item',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `item_notes` varchar(255) DEFAULT NULL,
  `display_order` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order_id` (`order_id`),
  KEY `idx_order_items_item_id` (`item_id`),
  KEY `idx_order_items_work_type_id` (`work_type_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_item` FOREIGN KEY (`item_id`) REFERENCES `laundry_items` (`id`),
  CONSTRAINT `fk_order_items_work` FOREIGN KEY (`work_type_id`) REFERENCES `work_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
