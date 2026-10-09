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

    public static function getKpis(string $start, string $end, ?int $branchId = null): array {
        $db = Database::getConnection();
        $params = ['start' => $start, 'end' => $end];
        $branchClause = "";
        if ($branchId !== null && $branchId > 0) {
            $branchClause = " AND branch_id = :branch_id";
            $params['branch_id'] = $branchId;
        }

        $sql = "SELECT 
                    COUNT(id) AS total_orders,
                    COALESCE(SUM(total_amount), 0.00) AS total_order_value,
                    COALESCE(SUM(amount_paid), 0.00) AS amount_collected,
                    SUM(CASE WHEN payment_status = 'unpaid' THEN 1 ELSE 0 END) AS unpaid_count,
                    SUM(CASE WHEN payment_status = 'partial' THEN 1 ELSE 0 END) AS partial_count,
                    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_count,
                    SUM(CASE WHEN status != 'completed' AND status != 'cancelled' THEN 1 ELSE 0 END) AS active_orders_count,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_orders_count
                FROM orders
                WHERE created_at >= :start AND created_at <= :end {$branchClause}";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
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

    public static function getFinancialSummary(string $start, string $end, ?int $branchId = null): array {
        $db = Database::getConnection();

        $startBoundary = (strlen($start) === 10) ? $start . ' 00:00:00' : $start;
        $endDate = substr($end, 0, 10);
        $nextDayExclusive = date('Y-m-d 00:00:00', strtotime($endDate . ' +1 day'));

        // 1. Payments Query: Joined to orders only when branchId filter is applied
        $payParams = [
            'start'    => $startBoundary,
            'next_day' => $nextDayExclusive
        ];
        $payJoin = "";
        $payClause = "";
        if ($branchId !== null && $branchId > 0) {
            $payJoin = " JOIN orders o ON p.order_id = o.id";
            $payClause = " AND o.branch_id = :branch_id";
            $payParams['branch_id'] = $branchId;
        }

        $sqlPay = "SELECT
                       p.payment_method,
                       COUNT(p.id) AS tx_count,
                       COALESCE(SUM(p.amount), 0.00) AS total_amount
                   FROM payments p{$payJoin}
                   WHERE p.created_at >= :start AND p.created_at < :next_day{$payClause}
                   GROUP BY p.payment_method";

        $stmtPay = $db->prepare($sqlPay);
        $stmtPay->execute($payParams);
        $payRows = $stmtPay->fetchAll();

        // 2. Refunds Query: Joined to orders only when branchId filter is applied
        $refParams = [
            'start'    => $startBoundary,
            'next_day' => $nextDayExclusive
        ];
        $refJoin = "";
        $refClause = "";
        if ($branchId !== null && $branchId > 0) {
            $refJoin = " JOIN orders o ON r.order_id = o.id";
            $refClause = " AND o.branch_id = :branch_id";
            $refParams['branch_id'] = $branchId;
        }

        $sqlRef = "SELECT
                       COUNT(r.id) AS refund_count,
                       COALESCE(SUM(r.amount), 0.00) AS total_refunded
                   FROM refunds r{$refJoin}
                   WHERE r.status = 'processed'
                     AND r.processed_at >= :start AND r.processed_at < :next_day{$refClause}";

        $stmtRef = $db->prepare($sqlRef);
        $stmtRef->execute($refParams);
        $refRow = $stmtRef->fetch() ?: ['refund_count' => 0, 'total_refunded' => 0.00];

        // 3. Process Payment Methods Breakdown & Gross Total
        $byMethod = [
            'cash'   => ['amount' => 0.00, 'count' => 0],
            'upi'    => ['amount' => 0.00, 'count' => 0],
            'online' => ['amount' => 0.00, 'count' => 0],
        ];

        $grossPayments = 0.00;
        $grossCount    = 0;

        foreach ($payRows as $row) {
            $method = (string)($row['payment_method'] ?? '');
            $amount = round((float)($row['total_amount'] ?? 0), 2);
            $cnt    = (int)($row['tx_count'] ?? 0);

            if (isset($byMethod[$method])) {
                $byMethod[$method]['amount'] = $amount;
                $byMethod[$method]['count']  = $cnt;
            } else {
                $byMethod[$method] = ['amount' => $amount, 'count' => $cnt];
            }
            $grossPayments += $amount;
            $grossCount    += $cnt;
        }
        $grossPayments = round($grossPayments, 2);

        $totalRefunded = round((float)($refRow['total_refunded'] ?? 0.00), 2);
        $refundCount   = (int)($refRow['refund_count'] ?? 0);
        $netCollections = round($grossPayments - $totalRefunded, 2);

        return [
            'gross_payments_recorded'    => $grossPayments,
            'gross_payments_count'       => $grossCount,
            'processed_refunds_recorded' => $totalRefunded,
            'processed_refunds_count'    => $refundCount,
            'net_recorded_collections'   => $netCollections,
            'by_method'                  => $byMethod
        ];
    }

    public static function getStatusSummary(string $start, string $end, ?int $branchId = null): array {
        $statuses = [
            'pickup_request', 'accepted', 'picked_up', 'inspection_completed',
            'price_confirmed', 'processing', 'ready', 'out_for_delivery', 'delivered', 'completed'
        ];

        $db = Database::getConnection();
        $params = ['start' => $start, 'end' => $end];
        $branchClause = "";
        if ($branchId !== null && $branchId > 0) {
            $branchClause = " AND branch_id = :branch_id";
            $params['branch_id'] = $branchId;
        }

        $sql = "SELECT status, COUNT(id) AS order_count, COALESCE(SUM(total_amount), 0.00) AS total_val
                FROM orders
                WHERE created_at >= :start AND created_at <= :end {$branchClause}
                GROUP BY status";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
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

    public static function getOperationalWorkload(?int $branchId = null): array {
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
        $params = [];
        $branchClause = "";
        if ($branchId !== null && $branchId > 0) {
            $branchClause = " AND branch_id = :branch_id";
            $params['branch_id'] = $branchId;
        }

        $sql = "SELECT status, COUNT(id) AS order_count
                FROM orders
                WHERE status != 'completed' AND status != 'cancelled' {$branchClause}
                GROUP BY status";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
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

    public static function getRecentOrders(int $limit = 10, ?int $branchId = null): array {
        $db = Database::getConnection();
        $params = [];
        $branchClause = "";
        if ($branchId !== null && $branchId > 0) {
            $branchClause = " WHERE o.branch_id = :branch_id";
            $params['branch_id'] = $branchId;
        }

        $sql = "SELECT o.*, c.name AS customer_name, c.mobile AS customer_mobile, b.name AS branch_name, b.code AS branch_code
                FROM orders o
                JOIN users c ON o.customer_id = c.id
                LEFT JOIN branches b ON o.branch_id = b.id
                {$branchClause}
                ORDER BY o.id DESC
                LIMIT :limit";

        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
