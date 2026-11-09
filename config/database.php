<?php
/**
 * NomReNom - Database Connection Handler (PDO)
 */

require_once __DIR__ . '/config.php';

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'nomrenom_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

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
            // Friendly error message for users if database is not yet created or credentials wrong
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
