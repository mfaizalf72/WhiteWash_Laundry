-- Phase 11A: Order Exceptions & Rescheduling
-- Extends orders.status ENUM with: 'cancelled'

ALTER TABLE `orders` 
MODIFY COLUMN `status` ENUM(
    'pickup_request',
    'accepted',
    'picked_up',
    'inspection_completed',
    'price_confirmed',
    'processing',
    'ready',
    'out_for_delivery',
    'delivered',
    'completed',
    'cancelled'
) NOT NULL DEFAULT 'pickup_request';
