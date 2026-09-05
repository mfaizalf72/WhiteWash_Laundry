<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Helpers;

class Security {
    public static function e(?string $value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeMobile(string $mobile): string {
        return preg_replace('/[^0-9]/', '', $mobile) ?? '';
    }
}
