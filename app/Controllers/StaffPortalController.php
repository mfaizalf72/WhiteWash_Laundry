<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\Order;
use Faizal\LaundryLms\Models\Customer;

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

        $queues = Order::getStaffOperationalQueues();

        Router::view('staff/dashboard', [
            'title'  => 'Staff Operations Dashboard - Launder',
            'queues' => $queues
        ], 'staff');
    }
}
