-- Phase 17 Admin-Assisted PIN Reset & Lockout Relief Migration
-- Adds must_change_pin flag and session_version to users table

ALTER TABLE `users`
  ADD COLUMN `must_change_pin` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_active`,
  ADD COLUMN `session_version` int unsigned NOT NULL DEFAULT 1 AFTER `must_change_pin`;
