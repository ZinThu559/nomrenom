<?php
/**
 * NomReNom - Customer Login
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pdo = getDbConnection();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Ensure account is marked verified
            if (!$user['is_email_verified']) {
                $pdo->prepare("UPDATE users SET is_email_verified = 1 WHERE id = ?")->execute([$user['id']]);
                $user['is_email_verified'] = 1;
            }

            loginUser($user);

            // Send Login Notification Email to the customer's real email address
            require_once __DIR__ . '/includes/mailer.php';
            try {
                sendLoginNotificationEmail($user['email'], $user['full_name']);
            } catch (Exception $e) {
                error_log("Failed to send login notification: " . $e->getMessage());
            }

            setFlash('success', 'Welcome back, <strong>' . htmlspecialchars($user['full_name']) . '</strong>! A security login notification has been sent to your email (<strong>' . htmlspecialchars($user['email']) . '</strong>).');

            $targetUrl = $_SESSION['intended_url'] ?? (($user['role'] === 'admin') ? (BASE_URL . '/admin/index.php') : (BASE_URL . '/index.php'));
            unset($_SESSION['intended_url']);
            header('Location: ' . $targetUrl);
            exit;
        } else {
            $error = 'Incorrect email or password. Please double-check your credentials.';
        }
    }
}

$pageTitle = 'Customer Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="form-card">
                <div class="text-center mb-4">
                    <span class="badge bg-green-light text-green-dark px-3 py-2 mb-2">NomReNom Member</span>
                    <h2 class="fw-bold text-dark mb-1">Account Login</h2>
                    <p class="text-muted small">Manage your bakery reservations and track your food waste impact</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center small mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/login.php" method="POST">
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="name@domain.com" required autocomplete="email">
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label small fw-bold m-0">Password</label>
                            <a href="<?= BASE_URL ?>/forgot_password.php" class="small text-green-primary text-decoration-none">Forgot password?</a>
                        </div>
                        <input type="password" name="password" class="form-control mt-1" placeholder="Enter your password" required autocomplete="current-password">
                    </div>

                    <div class="form-check mb-4 small">
                        <input class="form-check-input" type="checkbox" id="rememberMe">
                        <label class="form-check-label text-muted" for="rememberMe">Remember me on this device</label>
                    </div>

                    <button type="submit" class="btn btn-nrn-primary btn-lg w-100 py-3 shadow-sm">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Log In
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top small text-muted">
                    Don't have an account yet? 
                    <a href="<?= BASE_URL ?>/register.php" class="text-green-primary fw-bold text-decoration-none">
                        Sign up for free
                    </a>
                </div>

                <!-- Guest Customer Info Card -->
                <div class="mt-4 p-3 bg-light rounded-3 border text-center small">
                    <div class="fw-bold text-dark mb-1">
                        <i class="bi bi-bag-check-fill text-success me-1"></i> Ordered as a Guest?
                    </div>
                    <p class="text-muted mb-2">Guest customers do not need a password. You can track your order status directly using your Order Reference or Phone Number.</p>
                    <a href="<?= BASE_URL ?>/track.php" class="btn btn-sm btn-outline-dark rounded-pill px-3">
                        <i class="bi bi-search me-1"></i> Track Guest Order
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
