<?php
/**
 * NomReNom - Password Reset Completion
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

$token = clean($_GET['token'] ?? $_POST['token'] ?? '');
$pdo = getDbConnection();
$error = null;

if (empty($token)) {
    die("Invalid password reset token.");
}

$stmt = $pdo->prepare("SELECT * FROM password_reset_tokens 
                      WHERE token = ? AND is_used = 0 AND expires_at > NOW() LIMIT 1");
$stmt->execute([$token]);
$resetRecord = $stmt->fetch();

if (!$resetRecord) {
    setFlash('danger', 'Password reset link is invalid or has expired. Please submit a new reset request.');
    header('Location: ' . BASE_URL . '/forgot_password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Password confirmation does not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
            ->execute([$hash, $resetRecord['user_id']]);

        $pdo->prepare("UPDATE password_reset_tokens SET is_used = 1 WHERE id = ?")
            ->execute([$resetRecord['id']]);

        setFlash('success', 'Your password has been successfully updated! Please log in with your new password.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

$pageTitle = 'Set New Password';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="form-card">
                <div class="text-center mb-4">
                    <span class="badge bg-green-light text-green-dark px-3 py-2 mb-2">Account Security</span>
                    <h2 class="font-serif text-green-dark mb-1">Set New Password</h2>
                    <p class="text-muted small">Enter a secure new password for your NomReNom account</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger small mb-3"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/reset_password.php" method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">New Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required autocomplete="new-password">
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Confirm New Password</label>
                        <input type="password" name="password_confirm" class="form-control" placeholder="Re-type new password" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-nrn-primary btn-lg w-100 py-3">
                        <i class="bi bi-shield-lock-fill me-2"></i> Save New Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
