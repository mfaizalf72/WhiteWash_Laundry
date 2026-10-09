<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\Report;
use Faizal\LaundryLms\Models\Branch;
use Faizal\LaundryLms\Models\User;

class ReportController {
    public function index(): void {
        $userRole = (string)($_SESSION['user_role'] ?? '');
        $userId   = (int)($_SESSION['user_id'] ?? 0);

        $filter      = trim($_GET['filter'] ?? 'last_30_days');
        $customStart = trim($_GET['start_date'] ?? '');
        $customEnd   = trim($_GET['end_date'] ?? '');

        try {
            $range = Report::getDateRange($filter, $customStart, $customEnd);
        } catch (\InvalidArgumentException $e) {
            $_SESSION['error'] = $e->getMessage();
            $range = Report::getDateRange('last_30_days');
        }

        $selectedBranchId = null;
        $branches         = [];
        $currentBranch    = null;

        if ($userRole === 'staff') {
            $staffBranchId = (int)($_SESSION['user_branch_id'] ?? 0);
            if ($staffBranchId <= 0 && $userId > 0) {
                $user = User::find($userId);
                $staffBranchId = (int)($user['branch_id'] ?? 0);
                $_SESSION['user_branch_id'] = $staffBranchId;
            }

            if ($staffBranchId <= 0) {
                $_SESSION['error'] = "You are not assigned to an active branch. Please contact an administrator.";
                Router::redirect('/staff/dashboard');
                return;
            }

            // Strictly lock staff to their assigned branch - cannot be overridden by GET parameters
            $selectedBranchId = $staffBranchId;
            $currentBranch    = Branch::find($staffBranchId);
            $branches         = [];
            $layout           = 'staff';
        } else {
            // Admin: can filter by GET branch_id if provided
            $rawBranch = (int)($_GET['branch_id'] ?? 0);
            if ($rawBranch > 0) {
                $found = Branch::find($rawBranch);
                if ($found) {
                    $selectedBranchId = $rawBranch;
                    $currentBranch    = $found;
                } else {
                    $_SESSION['error'] = "Selected branch not found.";
                }
            }
            $branches = Branch::allActive();
            $layout   = 'admin';
        }

        $kpis      = Report::getKpis($range['start'], $range['end'], $selectedBranchId);
        $statusSum = Report::getStatusSummary($range['start'], $range['end'], $selectedBranchId);
        $workload  = Report::getOperationalWorkload($selectedBranchId);

        Router::view('admin/reports', [
            'title'            => 'Operational Reports - Launder',
            'range'            => $range,
            'kpis'             => $kpis,
            'statusSum'        => $statusSum,
            'workload'         => $workload,
            'userRole'         => $userRole,
            'branches'         => $branches,
            'selectedBranchId' => $selectedBranchId,
            'currentBranch'    => $currentBranch
        ], $layout);
    }
}
