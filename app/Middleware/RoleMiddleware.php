<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Middleware;

use Faizal\LaundryLms\Core\Router;

class RoleMiddleware implements MiddlewareInterface {
    public function handle(?string $param = null): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = "Please sign in to access that page.";
            Router::redirect('/login');
        }

        $allowedRoles = $param ? array_map('trim', explode(',', $param)) : ['admin'];
        $userRole = $_SESSION['user_role'] ?? '';

        if (!in_array($userRole, $allowedRoles, true)) {
            http_response_code(403);
            if ($userRole === 'customer') {
                $redirectUrl = function_exists('url') ? url('/customer/dashboard') : '/customer/dashboard';
            } elseif ($userRole === 'staff') {
                $redirectUrl = function_exists('url') ? url('/staff/dashboard') : '/staff/dashboard';
            } else {
                $redirectUrl = function_exists('url') ? url('/dashboard') : '/dashboard';
            }
            echo "<div style='padding: 3rem; text-align: center; font-family: sans-serif; max-w: 600px; margin: 0 auto;'>" .
                 "<h1 style='color: #e11d48; font-size: 2rem;'>403 Forbidden</h1>" .
                 "<p style='color: #475569;'>You do not have authorization to access this area.</p>" .
                 "<p><a href='{$redirectUrl}' style='color: #2563eb; text-decoration: underline;'>Return to Dashboard</a></p>" .
                 "</div>";
            exit;
        }
    }
}
