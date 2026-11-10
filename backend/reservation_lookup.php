<?php
/**
 * NomReNom - Redirect to Live Order Tracking
 */
require_once __DIR__ . '/config/config.php';
$code = $_GET['code'] ?? '';
if (!empty($code)) {
    header('Location: ' . BASE_URL . '/track.php?code=' . urlencode($code));
    exit;
}
header('Location: ' . BASE_URL . '/track.php');
exit;
