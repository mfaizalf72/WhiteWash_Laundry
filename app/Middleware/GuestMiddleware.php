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
            Router::redirect('/dashboard');
        }
    }
}
