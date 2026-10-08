<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\Report;
use Faizal\LaundryLms\Models\Branch;

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

        $rawBranch        = (int)($_GET['branch_id'] ?? 0);
        $selectedBranchId = $rawBranch > 0 ? $rawBranch : null;

        $kpis       = Report::getKpis($range['start'], $range['end'], $selectedBranchId);
        $statusSum  = Report::getStatusSummary($range['start'], $range['end'], $selectedBranchId);
        $workload   = Report::getOperationalWorkload($selectedBranchId);
        $recent     = Report::getRecentOrders(10, $selectedBranchId);
        $branches   = Branch::allActive();

        Router::view('admin/dashboard', [
            'title'            => 'Dashboard - Launder',
            'range'            => $range,
            'kpis'             => $kpis,
            'statusSum'        => $statusSum,
            'workload'         => $workload,
            'recentOrders'     => $recent,
            'branches'         => $branches,
            'selectedBranchId' => $selectedBranchId
        ], 'admin');
    }
}