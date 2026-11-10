<?php
/**
 * NomReNom - Customer Registration
 * Enforces email validation, unique email constraint, password hashing, and token dispatch
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pdo = getDbConnection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $fullName = clean($_POST['full_name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Validation
    if (empty($fullName) || mb_strlen($fullName) < 2) {
        $errors[] = 'Full name must be at least 2 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!empty($phone) && !preg_match('/^[0-9+\s\-()]{8,20}$/', $phone)) {
        $errors[] = 'Please enter a valid contact phone number.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Password confirmation does not match.';
    }

    if (empty($errors)) {
        // Check for duplicate registered email
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $errors[] = 'This email address is already registered. Please log in or use the forgot password tool.';
        }
    }

    if (empty($errors)) {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insertUser = $pdo->prepare("INSERT INTO users 
                (full_name, email, phone, password_hash, role, is_email_verified, created_at) 
                VALUES (?, ?, ?, ?, 'customer', 1, NOW())");
            $insertUser->execute([$fullName, $email, $phone, $passwordHash]);
            $userId = (int)$pdo->lastInsertId();

            // Send Welcome Email to the customer's real email address
            try {
                sendWelcomeRegistrationEmail($email, $fullName);
            } catch (Exception $e) {
                error_log("Failed to send welcome registration email: " . $e->getMessage());
            }

            // Auto-login newly registered customer
            loginUser([
                'id' => $userId,
                'full_name' => $fullName,
                'email' => $email,
                'role' => 'customer'
            ]);

            setFlash('success', 'Registration successful! Welcome to NomReNom, <strong>' . htmlspecialchars($fullName) . '</strong>. A welcome email has been sent to <strong>' . htmlspecialchars($email) . '</strong>.');
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        } catch (Exception $e) {
            $errors[] = 'An error occurred during account creation: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Customer Registration';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="form-card">
                <div class="text-center mb-4">
                    <span class="badge bg-green-light text-green-dark px-3 py-2 mb-2">Join NomReNom</span>
                    <h2 class="fw-bold text-dark mb-1">Create an Account</h2>
                    <p class="text-muted small">Join students and neighbours in reducing food waste while saving money</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger small mb-4" role="alert">
                        <ul class="m-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/register.php" method="POST">
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" placeholder="e.g. Alex Nguyen" required autocomplete="name">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="name@gmail.com" required autocomplete="email">
                        <div class="form-text small">Your real email address for account login, security notices, and order receipts.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="0901234567" required autocomplete="tel">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required autocomplete="new-password">
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirm" class="form-control" placeholder="Re-enter password" required autocomplete="new-password">
                    </div>

                    <div class="form-check mb-4 small">
                        <input class="form-check-input" type="checkbox" id="termsCheck" required checked>
                        <label class="form-check-label text-muted" for="termsCheck">
                            I agree to the <a href="<?= BASE_URL ?>/terms.php" target="_blank" class="text-green-primary">Terms & Conditions</a> and <a href="<?= BASE_URL ?>/privacy.php" target="_blank" class="text-green-primary">Privacy Policy</a> of NomReNom.
                        </label>
                    </div>

                    <button type="submit" class="btn btn-nrn-primary btn-lg w-100 py-3 shadow-sm">
                        <i class="bi bi-person-plus-fill me-2"></i> Create Account
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top small text-muted">
                    Already have an account? 
                    <a href="<?= BASE_URL ?>/login.php" class="text-green-primary fw-bold text-decoration-none">
                        Log in here
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
