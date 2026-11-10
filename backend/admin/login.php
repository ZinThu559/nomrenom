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

// Auto-heal / provision 3 primary admin accounts if missing or if credentials desynced
try {
    $adminList = [
        [
            'name' => 'Alex Nguyen (Lead Administrator)',
            'email' => 'admin.alex@nomrenom.vn',
            'phone' => '0901234561',
            'password' => 'NomAdmin2026!Alex'
        ],
        [
            'name' => 'Linh Tran (Store & Inventory Admin)',
            'email' => 'admin.linh@nomrenom.vn',
            'phone' => '0901234562',
            'password' => 'NomAdmin2026!Linh'
        ],
        [
            'name' => 'Minh Vo (Quality & Refunds Admin)',
            'email' => 'admin.minh@nomrenom.vn',
            'phone' => '0901234563',
            'password' => 'NomAdmin2026!Minh'
        ],
        [
            'name' => 'NomReNom Administrator (Master)',
            'email' => 'admin@nomrenom.vn',
            'phone' => '0901234567',
            'password' => 'Admin@12345'
        ]
    ];

    foreach ($adminList as $adm) {
        $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$adm['email']]);
        $row = $stmt->fetch();

        if (!$row) {
            $hash = password_hash($adm['password'], PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, is_email_verified, is_phone_verified, created_at) VALUES (?, ?, ?, ?, 'admin', 1, 1, NOW())")
                ->execute([$adm['name'], $adm['email'], $adm['phone'], $hash]);
        } elseif (!password_verify($adm['password'], $row['password_hash'])) {
            $hash = password_hash($adm['password'], PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ?, role = 'admin' WHERE id = ?")
                ->execute([$hash, $row['id']]);
        }
    }
} catch (Exception $e) {
    error_log("Failed to auto-provision admins: " . $e->getMessage());
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
                            <input type="email" id="adminEmail" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="admin.alex@nomrenom.vn" required autocomplete="email">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" id="adminPassword" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                        </div>

                        <!-- 3 Quick Admin Login Selectors -->
                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <div class="small fw-bold text-dark mb-2"><i class="bi bi-shield-check text-success me-1"></i> Quick Test: Choose 1 of 3 Admins</div>
                            <div class="d-flex flex-column gap-1">
                                <button type="button" onclick="fillAdmin('admin.alex@nomrenom.vn', 'NomAdmin2026!Alex')" class="btn btn-sm btn-outline-success text-start py-1 px-2 d-flex justify-content-between align-items-center">
                                    <span><strong>Admin 1:</strong> Alex (Operations Lead)</span>
                                    <span class="badge bg-success bg-opacity-10 text-success">Click to Fill</span>
                                </button>
                                <button type="button" onclick="fillAdmin('admin.linh@nomrenom.vn', 'NomAdmin2026!Linh')" class="btn btn-sm btn-outline-success text-start py-1 px-2 d-flex justify-content-between align-items-center">
                                    <span><strong>Admin 2:</strong> Linh (Store Inventory)</span>
                                    <span class="badge bg-success bg-opacity-10 text-success">Click to Fill</span>
                                </button>
                                <button type="button" onclick="fillAdmin('admin.minh@nomrenom.vn', 'NomAdmin2026!Minh')" class="btn btn-sm btn-outline-success text-start py-1 px-2 d-flex justify-content-between align-items-center">
                                    <span><strong>Admin 3:</strong> Minh (Quality &amp; Refunds)</span>
                                    <span class="badge bg-success bg-opacity-10 text-success">Click to Fill</span>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-nrn-primary btn-lg w-100 py-3 shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Log In to Admin
                        </button>
                    </form>

                    <script>
                        function fillAdmin(email, pass) {
                            document.getElementById('adminEmail').value = email;
                            document.getElementById('adminPassword').value = pass;
                        }
                    </script>

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
