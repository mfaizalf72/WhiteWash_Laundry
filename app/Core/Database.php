<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Core;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/database.php';

            $host    = $config['host'];
            $db      = $config['database'];
            $user    = $config['username'];
            $pass    = $config['password'];
            $port    = $config['port'];
            $charset = $config['charset'];

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                $isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'])
                    || str_starts_with($_SERVER['HTTP_HOST'] ?? '', 'localhost:');
                $tip = "";
                if (!$isLocal) {
                    $tip = "<p style='color:#64748b; font-size: 0.9rem; margin-top: 1rem;'>" .
                           "<strong>Tip:</strong> In your hosting File Manager, open the <code>.env</code> file in <code>htdocs</code> and verify <code>DB_PASSWORD</code> matches your hosting account password.<br>" .
                           "Attempted Host: <code>" . htmlspecialchars($host) . "</code> | Database: <code>" . htmlspecialchars($db) . "</code> | User: <code>" . htmlspecialchars($user) . "</code>" .
                           "</p>";
                }
                die("<div style='max-width: 600px; margin: 4rem auto; padding: 2rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; font-family: system-ui, sans-serif; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);'>" .
                    "<h2 style='color: #e11d48; margin-top: 0;'>Database Connection Error</h2>" .
                    "<p style='color: #334155; font-family: monospace; background: #f8fafc; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #cbd5e1;'>" .
                    htmlspecialchars($e->getMessage()) .
                    "</p>" .
                    $tip .
                    "</div>");
            }
        }
        return self::$instance;
    }
}
