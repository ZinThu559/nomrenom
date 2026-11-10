<?php
/**
 * NomReNom - Customer Logout
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();
session_start();
setFlash('info', 'You have been logged out safely.');
header('Location: ' . BASE_URL . '/index.php');
exit;
