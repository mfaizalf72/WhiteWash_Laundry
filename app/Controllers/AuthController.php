<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Controllers;

use Faizal\LaundryLms\Core\Router;
use Faizal\LaundryLms\Models\User;
use Faizal\LaundryLms\Models\Customer;
use Faizal\LaundryLms\Models\LoginAttempt;
use Faizal\LaundryLms\Helpers\Security;

class AuthController {
    public function showLogin(): void {
        Router::view('auth/login', ['title' => 'Sign In - Launder']);
    }

    public function showRegister(): void {
        Router::view('auth/register', ['title' => 'Create Account - Launder']);
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

        // Check persistent rate limiting (mobile + IP)
        if (LoginAttempt::isLocked($mobile)) {
            $this->backWithErrors("Too many failed login attempts. Please try again in 15 minutes.");
            return;
        }

        $user = User::findByMobile($mobile);

        if ($user && password_verify($pin, $user['pin_hash'])) {
            if (isset($user['is_active']) && (int)$user['is_active'] !== 1) {
                $this->backWithErrors("Your account is currently inactive. Please contact support.");
                return;
            }

            // Successful authentication clears failed attempt records
            LoginAttempt::clearAttempts($mobile);

            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            $_SESSION['user_id']         = (int)$user['id'];
            $_SESSION['user_role']       = $user['role'];
            $_SESSION['user_name']       = $user['name'];
            $_SESSION['user_branch_id']  = !empty($user['branch_id']) ? (int)$user['branch_id'] : null;
            $_SESSION['session_version'] = (int)($user['session_version'] ?? 1);

            if ($user['role'] === 'customer') {
                if ($pin === '1234' || (int)($user['must_change_pin'] ?? 0) === 1) {
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
            LoginAttempt::recordFailure($mobile);
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

    public function register(): void {
        // 1. Check IP rate limiting before processing
        if (LoginAttempt::isRegistrationLocked()) {
            $this->backToRegisterWithErrors("Too many registration attempts. Please try again in 15 minutes.");
            return;
        }

        // Record the registration attempt so abuse cannot bypass throttling with malformed payloads
        LoginAttempt::recordRegistrationAttempt();

        $name       = trim((string)($_POST['name'] ?? ''));
        $rawMobile  = (string)($_POST['mobile'] ?? '');
        $mobile     = Security::sanitizeMobile($rawMobile);
        $address    = trim((string)($_POST['address'] ?? ''));
        $pin        = trim((string)($_POST['pin'] ?? ''));
        $confirmPin = trim((string)($_POST['confirm_pin'] ?? ''));

        // 2. Name validation
        if (empty($name)) {
            $this->backToRegisterWithErrors("Full name is required.");
            return;
        }
        if (strlen($name) > 100) {
            $this->backToRegisterWithErrors("Full name cannot exceed 100 characters.");
            return;
        }

        // 3. Mobile validation
        if (empty($mobile)) {
            $this->backToRegisterWithErrors("Mobile number is required.");
            return;
        }
        if (strlen($mobile) < 10 || strlen($mobile) > 15) {
            $this->backToRegisterWithErrors("Please enter a valid mobile number (10 to 15 digits).");
            return;
        }

        // 4. Address validation
        if (empty($address)) {
            $this->backToRegisterWithErrors("Address is required for pickup and delivery services.");
            return;
        }

        // 5. PIN format and strength validation via shared validator
        $pinError = Customer::validatePinChoice($pin, $confirmPin);
        if ($pinError !== null) {
            $this->backToRegisterWithErrors($pinError);
            return;
        }

        // 8. Mobile uniqueness check (database unique constraint remains authoritative)
        if (Customer::findByMobile($mobile)) {
            $this->backToRegisterWithErrors("An account with mobile number '{$mobile}' already exists. Please sign in.");
            return;
        }

        // 9. Persist customer account (role strictly forced to customer, is_active forced to 1)
        Customer::create([
            'name'      => $name,
            'mobile'    => $mobile,
            'pin'       => $pin,
            'address'   => $address,
            'is_active' => 1
        ]);

        // 10. Success notification and redirect to login
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['success'] = "Registration successful! Please login with your mobile number and PIN.";
        Router::redirect('/login');
    }

    private function backToRegisterWithErrors(string $message): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['error'] = $message;
        Router::redirect('/register');
    }
}