<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Middleware;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\User;

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

        // Check session flag OR persistent database flag
        $requiresChange = ($_SESSION['requires_pin_change'] ?? false) === true;
        if (!$requiresChange) {
            $user = User::find((int)$_SESSION['user_id']);
            if ($user && (int)($user['must_change_pin'] ?? 0) === 1) {
                $_SESSION['requires_pin_change'] = true;
                $requiresChange = true;
            }
        }

        if ($requiresChange) {
            $_SESSION['warning'] = "For security purposes, you must update your PIN before accessing your account.";
            Router::redirect('/customer/force-pin-update');
            return;
        }
    }
}
