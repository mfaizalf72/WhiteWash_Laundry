<?php
declare(strict_types=1);

// Lightweight .env parser helper
if (!function_exists('loadEnv')) {
    function loadEnv(string $path): void {
        if (!file_exists($path) || !is_readable($path)) {
            return;
        }
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }
}

loadEnv(__DIR__ . '/../.env');

return [
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'laundry_db',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
    'charset'  => getenv('DB_CHARSET') ?: 'utf8mb4',
];
