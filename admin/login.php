<?php
/**
 * NomReNom - Administrator Login Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (isAdmin()) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$pdo = getDbConnection();
$error = null;

// Auto-heal / provision admin account if missing or if credentials desynced
try {
    $adminCheck = $pdo->prepare("SELECT id, password_hash FROM users WHERE email = 'admin@nomrenom.vn' AND role = 'admin' LIMIT 1");
    $adminCheck->execute();
    $existingAdmin = $adminCheck->fetch();

    if (!$existingAdmin) {
        $defaultAdminPass = password_hash('Admin@12345', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, is_email_verified, is_phone_verified, created_at) VALUES ('NomReNom Administrator', 'admin@nomrenom.vn', '0901234567', ?, 'admin', 1, 1, NOW())")
            ->execute([$defaultAdminPass]);
    } elseif (!password_verify('Admin@12345', $existingAdmin['password_hash'])) {
        $defaultAdminPass = password_hash('Admin@12345', PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
            ->execute([$defaultAdminPass, $existingAdmin['id']]);
    }
} catch (Exception $e) {
    error_log("Failed to auto-provision admin: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter your administrator email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            loginUser($admin);
            logAdminActivity($pdo, $admin['id'], 'LOGIN', 'USERS', $admin['id'], 'Admin logged in to operations dashboard.');
            
            $redirect = $_SESSION['admin_intended_url'] ?? (BASE_URL . '/admin/index.php');
            unset($_SESSION['admin_intended_url']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid administrator credentials. Access denied.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login – NomReNom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="bg-cream d-flex align-items-center min-vh-100">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5 bg-white">
                    <div class="text-center mb-4">
                        <div class="step-num bg-green-dark text-warning mb-2 mx-auto"><i class="bi bi-shield-lock-fill"></i></div>
                        <h3 class="font-serif text-green-dark mb-1">Admin Operations Portal</h3>
                        <p class="text-muted small">NomReNom Surplus-Food Marketplace Admin</p>
                    </div>

                    <?= renderFlash() ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger small mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>/admin/login.php" method="POST">
                        <?= csrfField() ?>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Administrator Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="admin@nomrenom.vn" required autocomplete="email">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                        </div>

                        <button type="submit" class="btn btn-nrn-primary btn-lg w-100 py-3 shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Log In to Admin
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <a href="<?= BASE_URL ?>/index.php" class="text-muted small text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i> Return to customer storefront
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
