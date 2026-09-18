<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\User;
use Faizal\LaundryLms\Helpers\Security;

class AuthController {
    public function showLogin(): void {
        Router::view('auth/login', ['title' => 'Sign In - Launder']);
    }

    public function login(): void {
        $rawMobile = $_POST['mobile'] ?? '';
        $mobile = Security::sanitizeMobile($rawMobile);
        $pin = trim($_POST['pin'] ?? '');

        if (empty($mobile) || empty($pin)) {
            $this->backWithErrors("Please enter both mobile number and PIN.");
            return;
        }

        if (strlen($pin) !== 4 || !ctype_digit($pin)) {
            $this->backWithErrors("PIN must be a 4-digit number.");
            return;
        }

        $user = User::findByMobile($mobile);

        if ($user && password_verify($pin, $user['pin_hash'])) {
            if (isset($user['is_active']) && (int)$user['is_active'] !== 1) {
                $this->backWithErrors("Your account is currently inactive. Please contact support.");
                return;
            }

            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            $_SESSION['user_id']   = (int)$user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];

            if ($user['role'] === 'customer') {
                if ($pin === '1234') {
                    $_SESSION['requires_pin_change'] = true;
                } else {
                    unset($_SESSION['requires_pin_change']);
                }
                Router::redirect('/customer/dashboard');
            } elseif ($user['role'] === 'staff') {
                unset($_SESSION['requires_pin_change']);
                Router::redirect('/staff/dashboard');
            } else {
                unset($_SESSION['requires_pin_change']);
                Router::redirect('/dashboard');
            }
        } else {
            $this->backWithErrors("Invalid mobile number or PIN.");
        }
    }

    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
        Router::redirect('/login');
    }

    private function backWithErrors(string $message): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['error'] = $message;
        Router::redirect('/login');
    }
}