<?php
declare(strict_types=1);

// Lightweight .env parser helper
if (!function_exists('loadEnv')) {
    function loadEnv(string $path): array {
        $loaded = [];
        if (!file_exists($path) || !is_readable($path)) {
            return $loaded;
        }
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return $loaded;
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
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                @putenv("{$key}={$value}");
                $loaded[$key] = $value;
            }
        }
        return $loaded;
    }
}

// 1. Try loading from .env in project root
$envData = loadEnv(__DIR__ . '/../.env');

// 2. Also check if .env is in DOCUMENT_ROOT
if (empty($envData) && !empty($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/.env')) {
    $envData = loadEnv($_SERVER['DOCUMENT_ROOT'] . '/.env');
}

// Helper to get environment variable with priority: loaded .env array -> $_ENV -> $_SERVER -> getenv -> default
if (!function_exists('getEnvValue')) {
    function getEnvValue(string $key, mixed $default = null, array $envData = []): mixed {
        if (isset($envData[$key]) && $envData[$key] !== '') {
            return $envData[$key];
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        $val = @getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }
        return $default;
    }
}

$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$isInfinityFree = str_contains($httpHost, 'infinityfreeapp.com');

// Intelligent environment fallbacks
$defaultHost     = $isInfinityFree ? 'sql105.infinityfree.com' : '127.0.0.1';
$defaultPort     = '3306';
$defaultDatabase = $isInfinityFree ? 'if0_43251621_laundry_db' : 'laundry_db';
$defaultUsername = $isInfinityFree ? 'if0_43251621' : 'root';
$defaultPassword = '';
$defaultCharset  = 'utf8mb4';

return [
    'host'     => getEnvValue('DB_HOST', $defaultHost, $envData),
    'port'     => getEnvValue('DB_PORT', $defaultPort, $envData),
    'database' => getEnvValue('DB_DATABASE', $defaultDatabase, $envData),
    'username' => getEnvValue('DB_USERNAME', $defaultUsername, $envData),
    'password' => getEnvValue('DB_PASSWORD', $defaultPassword, $envData),
    'charset'  => getEnvValue('DB_CHARSET', $defaultCharset, $envData),
];
