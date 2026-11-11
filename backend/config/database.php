<?php
/**
 * NomReNom - Database Connection Handler (PDO)
 */

require_once __DIR__ . '/config.php';

// Parse DATABASE_URL or cloud environment variables (Render, Railway, Docker, Heroku)
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'nomrenom_db';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

$databaseUrl = getenv('DATABASE_URL') ?: getenv('CLEARDB_DATABASE_URL') ?: getenv('JAWSDB_URL');
if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    if ($parts) {
        $dbHost = $parts['host'] ?? $dbHost;
        $dbPort = $parts['port'] ?? $dbPort;
        $dbUser = $parts['user'] ?? $dbUser;
        $dbPass = $parts['pass'] ?? $dbPass;
        $dbName = ltrim($parts['path'] ?? '', '/') ?: $dbName;
    }
}

defined('DB_HOST') or define('DB_HOST', $dbHost);
defined('DB_PORT') or define('DB_PORT', $dbPort);
defined('DB_NAME') or define('DB_NAME', $dbName);
defined('DB_USER') or define('DB_USER', $dbUser);
defined('DB_PASS') or define('DB_PASS', $dbPass);
defined('DB_CHARSET') or define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a shared PDO instance
 * @return PDO
 */
function getDbConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $isApi = defined('IS_API') || 
                     (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api') !== false) ||
                     (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/api') !== false);

            if ($isApi) {
                http_response_code(503);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => 'Database Connection Error',
                    'message' => $e->getMessage()
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                exit;
            }

            // Friendly HTML error message for web browser requests
            die('<div style="font-family:sans-serif;max-width:600px;margin:50px auto;padding:24px;background:#fef2f2;border:1px solid #f87171;border-radius:8px;">' .
                '<h2 style="color:#991b1b;margin-top:0;">Database Connection Error</h2>' .
                '<p style="color:#7f1d1d;">Could not connect to MySQL MariaDB with the current configuration.</p>' .
                '<p><strong>Error Details:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>' .
                '<p>Please verify MySQL is running in your XAMPP Control Panel and database <code>nomrenom_db</code> is imported.</p>' .
                '</div>');
        }
    }

    return $pdo;
}
