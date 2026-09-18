<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Middleware;

use Faizal\LaundryLms\Core\Router;

class ForcePinChangeMiddleware implements MiddlewareInterface {
    public function handle(?string $param = null): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'customer') {
            return;
        }

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        $base = '/laundry-lms';
        if (strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }

        // Allow force pin update routes and logout
        if (in_array($uri, ['/customer/force-pin-update', '/logout'], true)) {
            return;
        }

        if (($_SESSION['requires_pin_change'] ?? false) === true) {
            $_SESSION['warning'] = "For security purposes, you must change your default PIN before accessing your account.";
            Router::redirect('/customer/force-pin-update');
            return;
        }
    }
}
