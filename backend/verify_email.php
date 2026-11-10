<?php
/**
 * NomReNom - Email Verification Handler
 * Validates cryptographic activation tokens
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$token = clean($_GET['token'] ?? '');
$pdo = getDbConnection();
$status = 'invalid';
$message = '';

if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT * FROM email_verification_tokens 
                          WHERE token = ? AND is_used = 0 AND expires_at > NOW() 
                          LIMIT 1");
    $stmt->execute([$token]);
    $tokenRecord = $stmt->fetch();

    if ($tokenRecord) {
        // Mark token as used
        $pdo->prepare("UPDATE email_verification_tokens SET is_used = 1 WHERE id = ?")
            ->execute([$tokenRecord['id']]);

        if (!empty($tokenRecord['user_id'])) {
            $pdo->prepare("UPDATE users SET is_email_verified = 1 WHERE id = ?")
                ->execute([$tokenRecord['user_id']]);

            // Auto-login the user
            $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $userStmt->execute([$tokenRecord['user_id']]);
            $user = $userStmt->fetch();
            if ($user) {
                loginUser($user);
            }
        } elseif (!empty($tokenRecord['guest_id'])) {
            $pdo->prepare("UPDATE guest_customers SET is_email_verified = 1 WHERE id = ?")
                ->execute([$tokenRecord['guest_id']]);
        }

        $status = 'success';
        $message = 'Your email address <strong>' . htmlspecialchars($tokenRecord['email']) . '</strong> has been successfully verified!';
    } else {
        $status = 'error';
        $message = 'This activation link is invalid, has expired, or has already been used.';
    }
}

$pageTitle = 'Email Verification';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 text-center bg-white">
                <?php if ($status === 'success'): ?>
                    <div class="step-num bg-green-light text-success mb-3 mx-auto">
                        <i class="bi bi-check-lg fs-3"></i>
                    </div>
                    <h2 class="font-serif text-green-dark mb-2">Email Verified!</h2>
                    <p class="text-muted small mb-4"><?= $message ?></p>
                    <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-nrn-primary w-100 py-3">
                        <i class="bi bi-basket2-fill me-2"></i> Explore Today's Food Boxes
                    </a>
                <?php else: ?>
                    <div class="step-num bg-danger bg-opacity-10 text-danger mb-3 mx-auto">
                        <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                    </div>
                    <h2 class="font-serif text-danger mb-2">Verification Failed</h2>
                    <p class="text-muted small mb-4"><?= $message ?: 'Please check the verification link in your inbox or request a new one.' ?></p>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-nrn-primary w-100 py-3">
                        Go to Account Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
