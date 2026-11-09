<?php
/**
 * NomReNom - Authentication & Session Security Service
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if a customer user is currently logged in
 * @return bool
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

/**
 * Check if the currently logged in user is an administrator
 * @return bool
 */
function isAdmin(): bool {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

/**
 * Get current logged in user ID
 * @return int|null
 */
function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Retrieve current user record from database
 * @return array|null
 */
function currentUser(): ?array {
    $userId = currentUserId();
    if (!$userId) return null;

    static $cachedUser = null;
    if ($cachedUser !== null && $cachedUser['id'] === $userId) {
        return $cachedUser;
    }

    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT id, full_name, email, phone, role, is_email_verified, is_phone_verified, created_at 
                           FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $cachedUser = $stmt->fetch();
    return $cachedUser ?: null;
}

/**
 * Enforce authenticated access, redirecting guests to login
 * @param string|null $redirectTo
 */
function requireAuth(?string $redirectTo = null): void {
    if (!isLoggedIn()) {
        $target = $redirectTo ?? ($_SERVER['REQUEST_URI'] ?? (BASE_URL . '/index.php'));
        $_SESSION['intended_url'] = $target;
        setFlash('warning', 'Please log in to your account to continue.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Enforce administrator access, redirecting unauthorized users to admin login
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        $_SESSION['admin_intended_url'] = $_SERVER['REQUEST_URI'] ?? (BASE_URL . '/admin/index.php');
        setFlash('danger', 'The administrative portal requires Admin privileges.');
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

/**
 * Establish authenticated user session
 * @param array $user
 */
function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['last_activity'] = time();
}

/**
 * Securely destroy current user session
 */
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
