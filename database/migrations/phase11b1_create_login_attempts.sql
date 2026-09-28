-- Phase 11B-1: Login Rate-Limiting & Brute-Force Protection
-- Creates the login_attempts table to track failed authentication attempts per mobile and IP.

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mobile_attempted` (`mobile`, `attempted_at`),
  KEY `idx_ip_attempted` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
