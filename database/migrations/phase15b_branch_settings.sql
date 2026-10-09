-- Phase 15B Branch-Specific Operating Settings Migration
-- Adds optional, nullable working_hours column to branches table with global fallback

ALTER TABLE `branches`
  ADD COLUMN `working_hours` varchar(100) DEFAULT NULL AFTER `address`;
