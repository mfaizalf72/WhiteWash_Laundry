<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Middleware;

use Faizal\LaundryLms\Helpers\Csrf;

class CsrfMiddleware implements MiddlewareInterface {
    public function handle(?string $param = null): void {
        $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Csrf::verify($token)) {
            http_response_code(403);
            echo "<div style='padding: 3rem; text-align: center; font-family: sans-serif; max-w: 600px; margin: 0 auto;'>" .
                 "<h1 style='color: #e11d48; font-size: 2rem;'>403 Forbidden</h1>" .
                 "<p style='color: #475569;'>CSRF verification failed. Request rejected.</p>" .
                 "<p><a href='/laundry-lms/login' style='color: #2563eb; text-decoration: underline;'>Return to Login</a></p>" .
                 "</div>";
            exit;
        }
    }
}
