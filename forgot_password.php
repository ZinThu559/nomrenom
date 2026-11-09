<?php
/**
 * NomReNom - Password Reset Request
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mailer.php';

$pdo = getDbConnection();
$submitted = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $email = clean($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = generateSecureToken(32);
            $tokenStmt = $pdo->prepare("INSERT INTO password_reset_tokens 
                (user_id, token, expires_at) 
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
            $tokenStmt->execute([$user['id'], $token]);

            sendPasswordResetEmail($user['email'], $user['full_name'], $token);
        }
        $submitted = true;
    }
}

$pageTitle = 'Forgot Password';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="form-card">
                <div class="text-center mb-4">
                    <span class="badge bg-green-light text-green-dark px-3 py-2 mb-2">Password Recovery</span>
                    <h2 class="font-serif text-green-dark mb-1">Forgot Password?</h2>
                    <p class="text-muted small">Enter your registered email address to receive password reset instructions</p>
                </div>

                <?php if ($submitted): ?>
                    <div class="alert alert-success p-3 small mb-4" role="alert">
                        <i class="bi bi-envelope-check-fill fs-5 me-2"></i>
                        If an account exists for that email address, password reset instructions have been dispatched. Please check your inbox (and <code>storage/logs/mail.log</code> for local test environments).
                    </div>
                    <div class="text-center">
                        <a href="<?= BASE_URL ?>/login.php" class="btn btn-nrn-primary w-100 py-3">Return to Login</a>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger small mb-3"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>/forgot_password.php" method="POST">
                        <?= csrfField() ?>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Registered Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="name@domain.com" required autocomplete="email">
                        </div>

                        <button type="submit" class="btn btn-nrn-primary btn-lg w-100 py-3">
                            <i class="bi bi-send-fill me-2"></i> Send Reset Link
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top small text-muted">
                        Remembered your password? 
                        <a href="<?= BASE_URL ?>/login.php" class="text-green-primary fw-bold text-decoration-none">
                            Log in
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
