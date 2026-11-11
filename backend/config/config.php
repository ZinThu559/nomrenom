<?php
/**
 * NomReNom - Core Application Configuration
 * Surplus-Food Marketplace Platform
 */

if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    @ini_set('session.cookie_httponly', 1);
    @ini_set('session.use_only_cookies', 1);
    @ini_set('session.cookie_samesite', 'Lax');
    @session_name('NOMRENOM_SESSID');
    @session_start();
}

// Environment & Base Paths
if (!defined('APP_NAME')) define('APP_NAME', 'NomReNom');
if (!defined('APP_TAGLINE')) define('APP_TAGLINE', 'Near-Expiry & Imperfect Snacks • Store Pickup • Zero Shipping');
if (!defined('APP_VERSION')) define('APP_VERSION', '1.0.0-MVP');

// Base URL detection for local XAMPP or custom vhost
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Normalize base path for /nomrenom or subdirectories
if (strpos($scriptDir, '/admin') !== false) {
    $basePath = substr($scriptDir, 0, strpos($scriptDir, '/admin'));
} elseif (strpos($scriptDir, '/payments') !== false) {
    $basePath = substr($scriptDir, 0, strpos($scriptDir, '/payments'));
} elseif (strpos($scriptDir, '/api') !== false) {
    $basePath = substr($scriptDir, 0, strpos($scriptDir, '/api'));
} else {
    $basePath = rtrim($scriptDir, '/');
}
if (!defined('BASE_URL')) define('BASE_URL', $protocol . $host . $basePath);
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Currency & Pricing Standards
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', '₫');
if (!defined('PRICE_SNACK_BOX')) define('PRICE_SNACK_BOX', 40000);
if (!defined('PRICE_MEAL_BOX')) define('PRICE_MEAL_BOX', 70000);

// Platform Configuration
if (!defined('TEAM_NAME')) define('TEAM_NAME', 'NomReNom Operations Team');
if (!defined('REPORT_DATE')) define('REPORT_DATE', 'November 2026');
if (!defined('DEMO_MODE')) define('DEMO_MODE', false);
if (!defined('DEMO_NOTICE')) define('DEMO_NOTICE', '');
