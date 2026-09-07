<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Models;

use Faizal\LaundryLms\Core\Database;
use PDO;

class Report {
    public static function getDateRange(string $filterType = 'today', ?string $customStart = null, ?string $customEnd = null): array {
        $filterType = strtolower(trim($filterType));
        $now = date('Y-m-d');

        switch ($filterType) {
            case 'last_7_days':
                $startDate = date('Y-m-d', strtotime('-6 days'));
                $endDate   = $now;
                break;
            case 'last_30_days':
                $startDate = date('Y-m-d', strtotime('-29 days'));
                $endDate   = $now;
                break;
            case 'custom':
                if (empty($customStart) || empty($customEnd) ||
                    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $customStart) ||
                    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $customEnd)) {
                    throw new \InvalidArgumentException("Invalid custom date range. Dates must be in YYYY-MM-DD format.");
                }
                if (strtotime($customEnd) < strtotime($customStart)) {
                    throw new \InvalidArgumentException("End date cannot be earlier than start date.");
                }
                $startDate = $customStart;
                $endDate   = $customEnd;
                break;
            case 'today':
            default:
                $filterType = 'today';
                $startDate  = $now;
                $endDate    = $now;
                break;
        }

        return [
            'filter'     => $filterType,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'start'      => $startDate . ' 00:00:00',
            'end'        => $endDate . ' 23:59:59'
        ];
    }

    public static function getKpis(string $start, string $end): array {
        $db = Database::getConnection();
        $sql = "SELECT 
                    COUNT(id) AS total_orders,
                    COALESCE(SUM(total_amount), 0.00) AS total_order_value,
                    COALESCE(SUM(amount_paid), 0.00) AS amount_collected,
                    SUM(CASE WHEN payment_status = 'unpaid' THEN 1 ELSE 0 END) AS unpaid_count,
                    SUM(CASE WHEN payment_status = 'partial' THEN 1 ELSE 0 END) AS partial_count,
                    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_count,
                    SUM(CASE WHEN status != 'completed' THEN 1 ELSE 0 END) AS active_orders_count,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_orders_count
                FROM orders
                WHERE created_at >= :start AND created_at <= :end";

        $stmt = $db->prepare($sql);
        $stmt->execute(['start' => $start, 'end' => $end]);
        $data = $stmt->fetch() ?: [];

        $totalVal   = round((float)($data['total_order_value'] ?? 0), 2);
        $collected  = round((float)($data['amount_collected'] ?? 0), 2);
        $outstanding = max(0.00, round($totalVal - $collected, 2));

        return [
            'total_orders'           => (int)($data['total_orders'] ?? 0),
            'total_order_value'      => $totalVal,
            'amount_collected'       => $collected,
            'outstanding_balance'    => $outstanding,
            'unpaid_orders_count'    => (int)($data['unpaid_count'] ?? 0),
            'partial_orders_count'   => (int)($data['partial_count'] ?? 0),
            'paid_orders_count'      => (int)($data['paid_count'] ?? 0),
            'active_orders_count'    => (int)($data['active_orders_count'] ?? 0),
            'completed_orders_count' => (int)($data['completed_orders_count'] ?? 0)
        ];
    }

    public static function getStatusSummary(string $start, string $end): array {
        $statuses = [
            'pickup_request', 'accepted', 'picked_up', 'inspection_completed',
            'price_confirmed', 'processing', 'ready', 'out_for_delivery', 'delivered', 'completed'
        ];

        $db = Database::getConnection();
        $sql = "SELECT status, COUNT(id) AS order_count, COALESCE(SUM(total_amount), 0.00) AS total_val
                FROM orders
                WHERE created_at >= :start AND created_at <= :end
                GROUP BY status";

        $stmt = $db->prepare($sql);
        $stmt->execute(['start' => $start, 'end' => $end]);
        $rows = $stmt->fetchAll();

        $mapped = [];
        foreach ($rows as $r) {
            $mapped[$r['status']] = [
                'count' => (int)$r['order_count'],
                'val'   => round((float)$r['total_val'], 2)
            ];
        }

        $summary = [];
        foreach ($statuses as $st) {
            $summary[$st] = [
                'name'  => ucwords(str_replace('_', ' ', $st)),
                'count' => $mapped[$st]['count'] ?? 0,
                'val'   => $mapped[$st]['val'] ?? 0.00
            ];
        }

        return $summary;
    }

    public static function getOperationalWorkload(): array {
        $activeStatuses = [
            'pickup_request'       => 'Pickup Requests Pending',
            'accepted'             => 'Accepted (Awaiting Pickup)',
            'picked_up'            => 'Picked Up (Inspection Pending)',
            'inspection_completed' => 'Inspection Done (Price Confirmation Pending)',
            'processing'           => 'Currently In Processing',
            'ready'                => 'Ready For Delivery',
            'out_for_delivery'     => 'Out For Delivery'
        ];

        $db = Database::getConnection();
        $sql = "SELECT status, COUNT(id) AS order_count
                FROM orders
                WHERE status != 'completed'
                GROUP BY status";

        $stmt = $db->query($sql);
        $rows = $stmt->fetchAll();

        $counts = [];
        foreach ($rows as $r) {
            $counts[$r['status']] = (int)$r['order_count'];
        }

        $workload = [];
        foreach ($activeStatuses as $st => $label) {
            $workload[$st] = [
                'status' => $st,
                'label'  => $label,
                'count'  => $counts[$st] ?? 0
            ];
        }

        return $workload;
    }

    public static function getRecentOrders(int $limit = 10): array {
        $db = Database::getConnection();
        $sql = "SELECT o.*, c.name AS customer_name, c.mobile AS customer_mobile
                FROM orders o
                JOIN users c ON o.customer_id = c.id
                ORDER BY o.id DESC
                LIMIT :limit";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
