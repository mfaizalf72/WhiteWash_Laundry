<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Middleware;

use Faizal\LaundryLms\Core\Router;

class GuestMiddleware implements MiddlewareInterface {
    public function handle(?string $param = null): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user_id'])) {
            $role = $_SESSION['user_role'] ?? '';
            if ($role === 'customer') {
                Router::redirect('/customer/dashboard');
            } elseif ($role === 'staff') {
                Router::redirect('/staff/dashboard');
            } else {
                Router::redirect('/dashboard');
            }
        }
    }
}
