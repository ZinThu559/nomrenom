<?php
/**
 * NomReNom - Administrator Logout
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

logoutUser();
session_start();
setFlash('info', 'Administrator logged out successfully.');
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
