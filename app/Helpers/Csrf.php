<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Helpers;

class Csrf {
    public static function getToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function field(): string {
        $token = self::getToken();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $stored = $_SESSION['_csrf_token'] ?? '';
        if (empty($stored) || empty($token)) {
            return false;
        }
        return hash_equals($stored, $token);
    }
}
