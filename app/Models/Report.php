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
                $sParts = explode('-', $customStart);
                $eParts = explode('-', $customEnd);
                if (!checkdate((int)$sParts[1], (int)$sParts[2], (int)$sParts[0]) ||
                    !checkdate((int)$eParts[1], (int)$eParts[2], (int)$eParts[0])) {
                    throw new \InvalidArgumentException("Invalid custom date range. Dates must be valid calendar dates.");
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
            'price_confirmed', 'processing', 'ready', 'out_for_delivery', 'delivered', 'completed', 'cancelled'
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

        $sql = "SELECT o.*, c.name AS customer_name, c.mobile AS customer_mobile, s.name AS staff_name,
                       b.name AS branch_name, b.code AS branch_code
                FROM orders o
                JOIN users c ON o.customer_id = c.id
                LEFT JOIN users s ON o.staff_id = s.id
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

    public static function getDailyTrends(string $start, string $end, ?int $branchId = null): array {
        $db = Database::getConnection();

        $startBoundary = (strlen($start) === 10) ? $start . ' 00:00:00' : $start;
        $startDate = substr($startBoundary, 0, 10);
        $endDate = substr($end, 0, 10);
        $nextDayExclusive = date('Y-m-d 00:00:00', strtotime($endDate . ' +1 day'));

        // Initialize every calendar day in the validated selected range
        $daily = [];
        $current = strtotime($startDate);
        $last = strtotime($endDate);
        while ($current <= $last) {
            $d = date('Y-m-d', $current);
            $daily[$d] = [
                'date'                       => $d,
                'orders_created'             => 0,
                'total_order_value'          => 0.00,
                'gross_payments_recorded'    => 0.00,
                'gross_payments_count'       => 0,
                'processed_refunds_recorded' => 0.00,
                'processed_refunds_count'    => 0,
                'net_recorded_collections'   => 0.00,
                'completed_cohort_count'     => 0,
                'cancelled_cohort_count'     => 0,
            ];
            $current = strtotime('+1 day', $current);
        }

        // 1. Independent Orders Cohort Aggregation by DATE(created_at)
        $ordParams = [
            'start'    => $startBoundary,
            'next_day' => $nextDayExclusive
        ];
        $ordBranchClause = "";
        if ($branchId !== null && $branchId > 0) {
            $ordBranchClause = " AND branch_id = :branch_id";
            $ordParams['branch_id'] = $branchId;
        }

        $sqlOrders = "SELECT
                          DATE(created_at) AS day_date,
                          COUNT(id) AS orders_count,
                          COALESCE(SUM(total_amount), 0.00) AS total_val,
                          SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
                          SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count
                      FROM orders
                      WHERE created_at >= :start AND created_at < :next_day{$ordBranchClause}
                      GROUP BY DATE(created_at)";

        $stmtOrders = $db->prepare($sqlOrders);
        $stmtOrders->execute($ordParams);
        $orderRows = $stmtOrders->fetchAll();

        foreach ($orderRows as $row) {
            $d = (string)$row['day_date'];
            if (isset($daily[$d])) {
                $daily[$d]['orders_created']         = (int)$row['orders_count'];
                $daily[$d]['total_order_value']      = round((float)$row['total_val'], 2);
                $daily[$d]['completed_cohort_count'] = (int)$row['completed_count'];
                $daily[$d]['cancelled_cohort_count'] = (int)$row['cancelled_count'];
            }
        }

        // 2. Independent Payments Event Aggregation by DATE(payments.created_at)
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
                       DATE(p.created_at) AS day_date,
                       COUNT(p.id) AS tx_count,
                       COALESCE(SUM(p.amount), 0.00) AS total_amount
                   FROM payments p{$payJoin}
                   WHERE p.created_at >= :start AND p.created_at < :next_day{$payClause}
                   GROUP BY DATE(p.created_at)";

        $stmtPay = $db->prepare($sqlPay);
        $stmtPay->execute($payParams);
        $payRows = $stmtPay->fetchAll();

        foreach ($payRows as $row) {
            $d = (string)$row['day_date'];
            if (isset($daily[$d])) {
                $daily[$d]['gross_payments_recorded'] = round((float)$row['total_amount'], 2);
                $daily[$d]['gross_payments_count']    = (int)$row['tx_count'];
            }
        }

        // 3. Independent Refunds Event Aggregation by DATE(refunds.processed_at)
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
                       DATE(r.processed_at) AS day_date,
                       COUNT(r.id) AS refund_count,
                       COALESCE(SUM(r.amount), 0.00) AS total_refunded
                   FROM refunds r{$refJoin}
                   WHERE r.status = 'processed'
                     AND r.processed_at >= :start AND r.processed_at < :next_day{$refClause}
                   GROUP BY DATE(r.processed_at)";

        $stmtRef = $db->prepare($sqlRef);
        $stmtRef->execute($refParams);
        $refRows = $stmtRef->fetchAll();

        foreach ($refRows as $row) {
            $d = (string)$row['day_date'];
            if (isset($daily[$d])) {
                $daily[$d]['processed_refunds_recorded'] = round((float)$row['total_refunded'], 2);
                $daily[$d]['processed_refunds_count']    = (int)$row['refund_count'];
            }
        }

        // 4. Calculate Net Collections per day
        foreach ($daily as $d => &$dayData) {
            $dayData['net_recorded_collections'] = round(
                $dayData['gross_payments_recorded'] - $dayData['processed_refunds_recorded'],
                2
            );
        }
        unset($dayData);

        return array_values($daily);
    }

    public static function getOrderValueDistribution(string $start, string $end, ?int $branchId = null): array {
        $db = Database::getConnection();

        $startBoundary = (strlen($start) === 10) ? $start . ' 00:00:00' : $start;
        $endDate = substr($end, 0, 10);
        $nextDayExclusive = date('Y-m-d 00:00:00', strtotime($endDate . ' +1 day'));

        $params = [
            'start'    => $startBoundary,
            'next_day' => $nextDayExclusive
        ];
        $branchClause = "";
        if ($branchId !== null && $branchId > 0) {
            $branchClause = " AND branch_id = :branch_id";
            $params['branch_id'] = $branchId;
        }

        $sql = "SELECT
                    COUNT(id) AS total_orders,
                    COALESCE(SUM(total_amount), 0.00) AS total_order_value,
                    SUM(CASE WHEN total_amount < 200.00 THEN 1 ELSE 0 END) AS count_under_200,
                    COALESCE(SUM(CASE WHEN total_amount < 200.00 THEN total_amount ELSE 0.00 END), 0.00) AS val_under_200,
                    SUM(CASE WHEN total_amount >= 200.00 AND total_amount <= 500.00 THEN 1 ELSE 0 END) AS count_200_500,
                    COALESCE(SUM(CASE WHEN total_amount >= 200.00 AND total_amount <= 500.00 THEN total_amount ELSE 0.00 END), 0.00) AS val_200_500,
                    SUM(CASE WHEN total_amount > 500.00 AND total_amount <= 1000.00 THEN 1 ELSE 0 END) AS count_501_1000,
                    COALESCE(SUM(CASE WHEN total_amount > 500.00 AND total_amount <= 1000.00 THEN total_amount ELSE 0.00 END), 0.00) AS val_501_1000,
                    SUM(CASE WHEN total_amount > 1000.00 THEN 1 ELSE 0 END) AS count_over_1000,
                    COALESCE(SUM(CASE WHEN total_amount > 1000.00 THEN total_amount ELSE 0.00 END), 0.00) AS val_over_1000
                FROM orders
                WHERE created_at >= :start AND created_at < :next_day{$branchClause}";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetch() ?: [];

        $totalOrders = (int)($data['total_orders'] ?? 0);
        $totalVal    = round((float)($data['total_order_value'] ?? 0.00), 2);
        $aov         = $totalOrders > 0 ? round($totalVal / $totalOrders, 2) : 0.00;

        $tUnder200Cnt = (int)($data['count_under_200'] ?? 0);
        $tUnder200Val = round((float)($data['val_under_200'] ?? 0.00), 2);

        $t200_500Cnt  = (int)($data['count_200_500'] ?? 0);
        $t200_500Val  = round((float)($data['val_200_500'] ?? 0.00), 2);

        $t501_1000Cnt = (int)($data['count_501_1000'] ?? 0);
        $t501_1000Val = round((float)($data['val_501_1000'] ?? 0.00), 2);

        $tOver1000Cnt = (int)($data['count_over_1000'] ?? 0);
        $tOver1000Val = round((float)($data['val_over_1000'] ?? 0.00), 2);

        $calcPct = function(int $cnt) use ($totalOrders): float {
            return $totalOrders > 0 ? round(($cnt / $totalOrders) * 100, 1) : 0.0;
        };

        return [
            'total_orders'        => $totalOrders,
            'total_order_value'   => $totalVal,
            'average_order_value' => $aov,
            'tiers'               => [
                'under_200' => [
                    'label'      => 'Below ₹200',
                    'count'      => $tUnder200Cnt,
                    'value'      => $tUnder200Val,
                    'percentage' => $calcPct($tUnder200Cnt)
                ],
                '200_to_500' => [
                    'label'      => '₹200–₹500',
                    'count'      => $t200_500Cnt,
                    'value'      => $t200_500Val,
                    'percentage' => $calcPct($t200_500Cnt)
                ],
                '501_to_1000' => [
                    'label'      => 'Above ₹500–₹1,000',
                    'count'      => $t501_1000Cnt,
                    'value'      => $t501_1000Val,
                    'percentage' => $calcPct($t501_1000Cnt)
                ],
                'over_1000' => [
                    'label'      => 'Above ₹1,000',
                    'count'      => $tOver1000Cnt,
                    'value'      => $tOver1000Val,
                    'percentage' => $calcPct($tOver1000Cnt)
                ]
            ]
        ];
    }

    public static function getOverdueCounts(?int $branchId = null): array {
        $db = Database::getConnection();
        $params = [];
        $branchClause = "";
        if ($branchId !== null && $branchId > 0) {
            $branchClause = " AND branch_id = :branch_id";
            $params['branch_id'] = $branchId;
        }

        $sql = "SELECT
                    SUM(CASE WHEN pickup_date < CURRENT_DATE() AND status IN ('pickup_request', 'accepted') THEN 1 ELSE 0 END) AS overdue_pickups,
                    SUM(CASE WHEN delivery_date IS NOT NULL AND delivery_date < CURRENT_DATE() AND status IN ('picked_up', 'inspection_completed', 'price_confirmed', 'processing', 'ready', 'out_for_delivery') THEN 1 ELSE 0 END) AS overdue_deliveries
                FROM orders
                WHERE 1=1{$branchClause}";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetch() ?: [];

        return [
            'overdue_pickups'    => (int)($data['overdue_pickups'] ?? 0),
            'overdue_deliveries' => (int)($data['overdue_deliveries'] ?? 0)
        ];
    }
}
