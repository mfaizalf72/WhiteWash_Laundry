<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Middleware;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\User;

class AuthMiddleware implements MiddlewareInterface {
    public function handle(?string $param = null): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = "Please sign in to access that page.";
            Router::redirect('/login');
            return;
        }

        // Verify user account is active and session is not superseded
        $user = User::find((int)$_SESSION['user_id']);
        if (!$user || (int)($user['is_active'] ?? 1) !== 1) {
            $_SESSION = [];
            session_destroy();
            session_start();
            $_SESSION['error'] = "Your account is no longer active. Please contact support.";
            Router::redirect('/login');
            return;
        }

        if (isset($_SESSION['session_version']) && (int)($user['session_version'] ?? 1) !== (int)$_SESSION['session_version']) {
            $_SESSION = [];
            session_destroy();
            session_start();
            $_SESSION['error'] = "Your session has expired or your credentials were reset. Please sign in again.";
            Router::redirect('/login');
            return;
        }
    }
}
