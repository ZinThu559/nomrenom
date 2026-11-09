<?php
/**
 * NomReNom - Customer Profile Management
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireAuth();
$pdo = getDbConnection();
$user = currentUser();
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = clean($_POST['action'] ?? '');

    if ($action === 'update_info') {
        $fullName = clean($_POST['full_name'] ?? '');
        $phone = clean($_POST['phone'] ?? '');

        if (empty($fullName) || mb_strlen($fullName) < 3) {
            $errors[] = 'Full name must be at least 3 characters.';
        }
        if (!preg_match('/^(0[3|5|7|8|9])[0-9]{8}$/', $phone)) {
            $errors[] = 'Please enter a valid phone number (10 digits).';
        }

        if (empty($errors)) {
            $update = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
            $update->execute([$fullName, $phone, $user['id']]);
            $_SESSION['user_name'] = $fullName;
            $success = 'Your contact information has been updated successfully.';
            $user = currentUser(); // refresh
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch();

        if (!password_verify($currentPassword, $row['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirmation password do not match.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
                ->execute([$newHash, $user['id']]);
            $success = 'Your password has been changed successfully!';
        }
    }
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white text-center mb-4">
                <div class="team-avatar mx-auto mb-3 d-flex align-items-center justify-content-center bg-green-light text-green-dark fs-1">
                    <i class="bi bi-person-circle"></i>
                </div>
                <h5 class="text-green-dark mb-1"><?= htmlspecialchars($user['full_name']) ?></h5>
                <p class="text-muted small mb-2"><?= htmlspecialchars($user['email']) ?></p>
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge <?= $user['is_email_verified'] ? 'bg-success' : 'bg-warning text-dark' ?>">
                        <i class="bi <?= $user['is_email_verified'] ? 'bi-patch-check-fill' : 'bi-exclamation-circle' ?> me-1"></i>
                        <?= $user['is_email_verified'] ? 'Email Verified' : 'Unverified Email' ?>
                    </span>
                    <span class="badge bg-light text-dark border">Member since <?= formatDateTime($user['created_at'], 'M Y') ?></span>
                </div>
            </div>

            <div class="list-group shadow-sm rounded-3 border-0">
                <a href="<?= BASE_URL ?>/profile.php" class="list-group-item list-group-item-action active bg-green-dark border-0 py-3">
                    <i class="bi bi-person-badge me-2"></i> Account Details
                </a>
                <a href="<?= BASE_URL ?>/my_reservations.php" class="list-group-item list-group-item-action py-3">
                    <i class="bi bi-calendar-check me-2"></i> Reservation History
                </a>
                <a href="<?= BASE_URL ?>/feedback.php" class="list-group-item list-group-item-action py-3">
                    <i class="bi bi-star me-2"></i> Rate & Review Bakery
                </a>
            </div>
        </div>

        <!-- Main Form Column -->
        <div class="col-md-8">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger small mb-4">
                    <ul class="m-0 ps-3">
                        <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center small mb-4">
                    <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                    <div><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>

            <!-- Edit Contact Info Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h5 class="text-green-dark mb-3"><i class="bi bi-pencil-square me-2 text-success"></i>Personal Information</h5>
                <form action="<?= BASE_URL ?>/profile.php" method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_info">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required autocomplete="name">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address (Account Identifier)</label>
                        <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                        <div class="form-text small">Email address is unique and used for authentication security.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Mobile Phone Number</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" required autocomplete="tel">
                    </div>

                    <button type="submit" class="btn btn-nrn-primary">
                        <i class="bi bi-check-lg me-1"></i> Save Changes
                    </button>
                </form>
            </div>

            <!-- Change Password Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="text-green-dark mb-3"><i class="bi bi-key-fill me-2 text-warning"></i>Change Password</h5>
                <form action="<?= BASE_URL ?>/profile.php" method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters" required autocomplete="new-password">
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-outline-success">
                        <i class="bi bi-shield-lock me-1"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
