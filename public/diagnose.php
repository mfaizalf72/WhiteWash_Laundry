<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/html; charset=utf-8');

$checks = [];

// 1. Host and Environment
$host = $_SERVER['HTTP_HOST'] ?? 'unknown';
$checks['Host'] = [
    'status' => 'info',
    'message' => "HTTP_HOST is '{$host}'"
];

// 2. .env File Check
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    $readable = is_readable($envPath);
    $checks['.env File'] = [
        'status' => $readable ? 'ok' : 'error',
        'message' => $readable ? "Found and readable at {$envPath} (" . filesize($envPath) . " bytes)" : "File exists but is NOT readable!"
    ];
} else {
    $checks['.env File'] = [
        'status' => 'warn',
        'message' => "Not found at {$envPath}. Using automatic InfinityFree defaults."
    ];
}

// 3. Database Configuration Load
$dbConfig = require dirname(__DIR__) . '/config/database.php';
$maskedPass = empty($dbConfig['password']) ? 'EMPTY (NOT SET)' : 'SET (' . strlen($dbConfig['password']) . ' chars)';
$checks['DB Config Loaded'] = [
    'status' => empty($dbConfig['password']) ? 'warn' : 'ok',
    'message' => "Host: <strong>{$dbConfig['host']}</strong> | Database: <strong>{$dbConfig['database']}</strong> | User: <strong>{$dbConfig['username']}</strong> | Password: <strong>{$maskedPass}</strong>"
];

// 4. PDO Connection Test
$pdo = null;
try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5
    ]);
    $checks['MySQL Connection'] = [
        'status' => 'ok',
        'message' => "SUCCESS! Connected to MySQL server (" . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . ")"
    ];
} catch (PDOException $e) {
    $checks['MySQL Connection'] = [
        'status' => 'error',
        'message' => "FAILED: " . htmlspecialchars($e->getMessage())
    ];
}

// 5. Users Table and Admin Credentials Verification
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, role, mobile, name, pin_hash, is_active FROM users ORDER BY id ASC");
        $users = $stmt->fetchAll();
        $userSummary = [];
        $adminLoginOk = false;
        foreach ($users as $u) {
            $verify1234 = password_verify('1234', $u['pin_hash'] ?? '');
            if ($u['mobile'] === '9999999999' && $verify1234) {
                $adminLoginOk = true;
            }
            $userSummary[] = "ID {$u['id']}: <strong>{$u['mobile']}</strong> ({$u['role']}, active={$u['is_active']}) &rarr; PIN 1234 " . ($verify1234 ? '<span style="color:green">VALID</span>' : '<span style="color:red">INVALID</span>');
        }
        $checks['Users in DB (' . count($users) . ')'] = [
            'status' => $adminLoginOk ? 'ok' : 'error',
            'message' => implode('<br>', $userSummary)
        ];
    } catch (PDOException $e) {
        $checks['Users Table'] = [
            'status' => 'error',
            'message' => "Error querying users table: " . htmlspecialchars($e->getMessage())
        ];
    }
}

// 6. Session Persistence Test
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['diagnose_test'] = ($_SESSION['diagnose_test'] ?? 0) + 1;
$checks['PHP Session'] = [
    'status' => 'ok',
    'message' => "Session ID: <code>" . session_id() . "</code> | Hit Count: <strong>{$_SESSION['diagnose_test']}</strong> (Refresh page to verify hit count increments!)"
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>WhiteWash LMS - System Diagnosis</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f1f5f9; padding: 2rem; color: #1e293b; }
        .card { max-width: 800px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #cbd5e1; }
        h1 { margin-top: 0; color: #0f172a; font-size: 1.5rem; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.75rem; }
        .row { display: flex; align-items: flex-start; justify-content: space-between; padding: 1rem 0; border-bottom: 1px solid #f1f5f9; }
        .row:last-child { border-bottom: none; }
        .badge { display: inline-block; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .badge-ok { background: #dcfce7; color: #15803d; }
        .badge-warn { background: #fef9c3; color: #a16207; }
        .badge-error { background: #fee2e2; color: #b91c1c; }
        .badge-info { background: #e0f2fe; color: #0369a1; }
        .desc { font-size: 0.9rem; line-height: 1.5; margin-top: 0.25rem; color: #475569; }
        .btn { display: inline-block; background: #2563eb; color: #fff; padding: 0.6rem 1.2rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; margin-top: 1.5rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🩺 WhiteWash LMS Live Diagnostics</h1>
        <p style="color: #64748b; font-size: 0.9rem;">Real-time environment, database, and session check.</p>
        
        <?php foreach ($checks as $title => $data): ?>
            <div class="row">
                <div style="flex: 1; padding-right: 1rem;">
                    <strong><?= htmlspecialchars($title) ?></strong>
                    <div class="desc"><?= $data['message'] ?></div>
                </div>
                <div>
                    <span class="badge badge-<?= $data['status'] ?>"><?= strtoupper($data['status']) ?></span>
                </div>
            </div>
        <?php endforeach; ?>

        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; display: flex; gap: 1rem; align-items: center;">
            <a href="/login" class="btn">Go to Login Page</a>
            <a href="/diagnose.php" style="font-size: 0.9rem; color: #2563eb; text-decoration: underline;">Refresh Diagnostics</a>
        </div>
    </div>
</body>
</html>
