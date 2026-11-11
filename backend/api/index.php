<?php
/**
 * NomReNom - Backend Health & API Status Endpoint
 */

define('IS_API', true);

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$dbConnected = false;
$dbMessage = 'Database not initialized';

try {
    $db = getDbConnection();
    $dbConnected = true;
    $dbMessage = 'Connected to database successfully';
} catch (Throwable $e) {
    $dbMessage = $e->getMessage();
}

echo json_encode([
    'status' => 'online',
    'app' => 'NomReNom Backend Server',
    'version' => '1.0.0-MVP',
    'environment' => getenv('PORT') ? 'cloud' : 'local',
    'database' => [
        'connected' => $dbConnected,
        'message' => $dbMessage,
        'host' => DB_HOST
    ],
    'endpoints' => [
        'cart' => '/api/cart.php',
        'health' => '/api/index.php',
        'admin_portal' => '/admin/login.php',
        'frontend' => '/frontend/index.html'
    ],
    'timestamp' => date('c')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
