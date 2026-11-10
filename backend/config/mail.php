<?php
/**
 * NomReNom - Real-Life Email & SMTP Configuration
 *
 * To send real live emails directly to customers' inboxes (e.g. Gmail, Outlook, Yahoo):
 * 1. Go to your Google Account -> Security -> 2-Step Verification -> App Passwords.
 * 2. Generate a 16-character App Password (e.g., 'abcd efgh ijkl mnop').
 * 3. Replace SMTP_USER with your Gmail address and SMTP_PASS with that App Password.
 *
 * Note: Even without SMTP configured, every email (Welcome email, Login Security Notification,
 * and Order Receipts) is fully generated and logged in real-time to:
 * C:\xampp\htdocs\nomrenom\storage\logs\mail.log
 */

require_once __DIR__ . '/config.php';

// SMTP Host Settings (supports Gmail, Outlook, Mailtrap, SendGrid, etc.)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); // 587 (TLS) or 465 (SSL)
define('SMTP_USER', 'your-email@gmail.com');      // Put your real email here
define('SMTP_PASS', 'your-app-password');        // Put your 16-character Google App Password here
define('SMTP_SECURE', 'tls');                    // 'tls' or 'ssl'
define('SMTP_FROM_EMAIL', 'support@nomrenom.vn');
define('SMTP_FROM_NAME', 'NomReNom Food Ordering');

// Local audit and verification log file
define('MAIL_LOG_FILE', ROOT_PATH . '/storage/logs/mail.log');
