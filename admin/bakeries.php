<?php
/**
 * NomReNom - Administrator Bakery Partners Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$pdo = getDbConnection();
$adminId = currentUserId();

// Handle Toggle or Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = clean($_POST['action'] ?? '');
    $bakeryId = (int)($_POST['bakery_id'] ?? 0);

    if ($action === 'toggle_active' && $bakeryId > 0) {
        $pdo->prepare("UPDATE bakeries SET is_active = NOT is_active WHERE id = ?")->execute([$bakeryId]);
        logAdminActivity($pdo, $adminId, 'TOGGLE_BAKERY', 'BAKERIES', $bakeryId, 'Toggled bakery partner active status.');
        setFlash('success', 'Bakery partner operating status updated successfully.');
        header('Location: ' . BASE_URL . '/admin/bakeries.php');
        exit;
    }
}

// Fetch all bakeries with box and order counts
$stmt = $pdo->query("SELECT b.*, 
                            (SELECT COUNT(*) FROM food_boxes WHERE bakery_id = b.id) AS total_boxes,
                            (SELECT COUNT(*) FROM reservations WHERE bakery_id = b.id) AS total_reservations 
                     FROM bakeries b 
                     ORDER BY b.is_active DESC, b.name ASC");
$bakeries = $stmt->fetchAll();

$pageTitle = 'Bakery Partner Management';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="font-serif text-green-dark m-0">Bakery Partners</h4>
        <small class="text-muted">Manage participating bakeries, operating hours, addresses, and partnerships</small>
    </div>
    <a href="<?= BASE_URL ?>/admin/bakery_form.php" class="btn btn-nrn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add New Bakery
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table admin-table m-0">
            <thead>
                <tr>
                    <th style="width: 70px;">Logo</th>
                    <th>Bakery Name</th>
                    <th>Address & District</th>
                    <th>Contact Info</th>
                    <th>Operating Hours</th>
                    <th>Boxes</th>
                    <th>Orders</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bakeries)): ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">No bakery partners found.</td></tr>
                <?php else: ?>
                    <?php foreach ($bakeries as $b): ?>
                        <tr>
                            <td>
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($b['logo_url'] ?? 'assets/img/bakeries/bakery-logo-1.svg') ?>" alt="Logo" class="rounded-circle" style="width: 48px; height: 48px; object-fit: cover;">
                            </td>
                            <td>
                                <strong class="text-green-dark"><?= htmlspecialchars($b['name']) ?></strong>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($b['address']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($b['district']) ?>, <?= htmlspecialchars($b['city']) ?></small>
                            </td>
                            <td>
                                <div><i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($b['phone']) ?></div>
                                <small class="text-muted"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($b['email']) ?></small>
                            </td>
                            <td>
                                <small class="badge bg-light text-dark border">
                                    <?= formatTime($b['opening_time']) ?> – <?= formatTime($b['closing_time']) ?>
                                </small>
                            </td>
                            <td><strong><?= (int)$b['total_boxes'] ?></strong> boxes</td>
                            <td><strong><?= (int)$b['total_reservations'] ?></strong> orders</td>
                            <td>
                                <form action="<?= BASE_URL ?>/admin/bakeries.php" method="POST" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="bakery_id" value="<?= $b['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $b['is_active'] ? 'btn-success' : 'btn-secondary' ?>">
                                        <?= $b['is_active'] ? 'Active' : 'Paused' ?>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/admin/bakery_form.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit details">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
