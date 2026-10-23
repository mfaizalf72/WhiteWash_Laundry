<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\Order;
use Faizal\LaundryLms\Models\Customer;
use Faizal\LaundryLms\Models\Branch;

class StaffPortalController {
    public function dashboard(): void {
        $userId   = (int)($_SESSION['user_id'] ?? 0);
        $userRole = $_SESSION['user_role'] ?? '';

        if ($userRole !== 'staff') {
            http_response_code(403);
            $_SESSION['error'] = "Unauthorized: Staff dashboard is restricted to staff members.";
            Router::redirect('/dashboard');
            return;
        }

        $staffBranchId = (int)($_SESSION['user_branch_id'] ?? 0);
        if ($staffBranchId <= 0 && $userId > 0) {
            $user = \Faizal\LaundryLms\Models\User::find($userId);
            $staffBranchId = (int)($user['branch_id'] ?? 0);
            $_SESSION['user_branch_id'] = $staffBranchId;
        }

        $branches = Branch::allActive();

        $selectedBranchParam = $_GET['branch_id'] ?? null;
        if ($selectedBranchParam === 'all' || $selectedBranchParam === '0') {
            $selectedBranchId = null;
        } elseif ($selectedBranchParam !== null && is_numeric($selectedBranchParam)) {
            $selectedBranchId = (int)$selectedBranchParam;
            if ($selectedBranchId > 0) {
                $_SESSION['user_branch_id'] = $selectedBranchId;
                $staffBranchId = $selectedBranchId;
            }
        } else {
            $selectedBranchId = $staffBranchId > 0 ? $staffBranchId : null;
        }

        $queues = Order::getStaffOperationalQueues($selectedBranchId);

        Router::view('staff/dashboard', [
            'title'            => 'Staff Operations Dashboard - Laundry',
            'queues'           => $queues,
            'staffBranchId'    => $staffBranchId,
            'branches'         => $branches,
            'selectedBranchId' => $selectedBranchId
        ], 'staff');
    }
}
