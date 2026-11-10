<?php
/**
 * NomReNom - CSRF Protection Utilities
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Generates or retrieves the active session CSRF token
 * @return string
 */
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Returns an HTML hidden input field for forms
 * @return string
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCsrfToken()) . '">';
}

/**
 * Verifies a submitted CSRF token using hash_equals
 * @param string|null $token
 * @return bool
 */
function verifyCsrfToken(?string $token = null): bool {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Enforces CSRF token check on POST requests or terminates with 403
 */
function requireCsrfToken(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrfToken()) {
            http_response_code(403);
            die('<div style="font-family:sans-serif;max-width:500px;margin:50px auto;padding:24px;border:1px solid #dc2626;background:#fee2e2;color:#991b1b;border-radius:8px;">' .
                '<h3>403 Security Validation Error (CSRF Token Expired)</h3>' .
                '<p>Your security session has expired or the form submission was untrusted. Please return to the previous page, refresh, and try again.</p>' .
                '<a href="javascript:history.back()" style="display:inline-block;padding:8px 16px;background:#991b1b;color:#fff;text-decoration:none;border-radius:4px;font-weight:bold;">Go Back</a>' .
                '</div>');
        }
    }
}
