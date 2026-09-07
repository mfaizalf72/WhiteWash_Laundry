<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\Report;

class DashboardController {
    public function index(): void {
        $filter      = trim($_GET['filter'] ?? 'today');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd   = trim($_GET['end_date'] ?? '');

        try {
            $range = Report::getDateRange($filter, $customStart, $customEnd);
        } catch (\InvalidArgumentException $e) {
            $_SESSION['error'] = $e->getMessage();
            $range = Report::getDateRange('today');
        }

        $kpis       = Report::getKpis($range['start'], $range['end']);
        $statusSum  = Report::getStatusSummary($range['start'], $range['end']);
        $workload   = Report::getOperationalWorkload();
        $recent     = Report::getRecentOrders(10);

        Router::view('admin/dashboard', [
            'title'        => 'Dashboard - Launder',
            'range'        => $range,
            'kpis'         => $kpis,
            'statusSum'    => $statusSum,
            'workload'     => $workload,
            'recentOrders' => $recent
        ], 'admin');
    }
}