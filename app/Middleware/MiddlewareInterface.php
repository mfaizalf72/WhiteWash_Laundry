<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Middleware;

interface MiddlewareInterface {
    public function handle(?string $param = null): void;
}
