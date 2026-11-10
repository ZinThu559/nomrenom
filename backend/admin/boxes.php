<?php
/**
 * NomReNom - Admin Food Box Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$pdo = getDbConnection();
$adminId = currentUserId();

// Handle Status Toggle or Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = clean($_POST['action'] ?? '');
    $boxId = (int)($_POST['box_id'] ?? 0);

    if ($action === 'toggle_status' && $boxId > 0) {
        $stmt = $pdo->prepare("UPDATE food_boxes SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$boxId]);
        logAdminActivity($pdo, $adminId, 'TOGGLE_STATUS', 'FOOD_BOXES', $boxId, 'Toggled food box availability status.');
        setFlash('success', 'Food box visibility status updated successfully.');
        header('Location: ' . BASE_URL . '/admin/boxes.php');
        exit;
    } elseif ($action === 'delete' && $boxId > 0) {
        // Check if box has active reservations
        $resCheck = $pdo->prepare("SELECT COUNT(*) FROM reservation_items WHERE food_box_id = ?");
        $resCheck->execute([$boxId]);
        $hasReservations = (int)$resCheck->fetchColumn();

        if ($hasReservations > 0) {
            // Soft-deactivate to preserve historical reservation integrity
            $pdo->prepare("UPDATE food_boxes SET is_active = 0, available_qty = 0 WHERE id = ?")->execute([$boxId]);
            logAdminActivity($pdo, $adminId, 'DEACTIVATE_BOX', 'FOOD_BOXES', $boxId, 'Deactivated food box due to existing reservation history.');
            setFlash('warning', 'This food box has previous order records; status set to <strong>Inactive</strong> to preserve accounting audits.');
        } else {
            $pdo->prepare("DELETE FROM food_boxes WHERE id = ?")->execute([$boxId]);
            logAdminActivity($pdo, $adminId, 'DELETE_BOX', 'FOOD_BOXES', $boxId, 'Permanently deleted unused food box product.');
            setFlash('success', 'Food box product deleted permanently.');
        }
        header('Location: ' . BASE_URL . '/admin/boxes.php');
        exit;
    }
}

// Fetch all food boxes with category
$stmt = $pdo->query("SELECT f.*, b.name AS bakery_name, b.district, c.name AS category_name 
                     FROM food_boxes f 
                     JOIN bakeries b ON f.bakery_id = b.id 
                     LEFT JOIN categories c ON f.category_id = c.id 
                     ORDER BY f.is_active DESC, f.id DESC");
$boxes = $stmt->fetchAll();

$pageTitle = 'Food Box Management';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="font-serif text-green-dark m-0">Food Box Inventory</h4>
        <small class="text-muted">Manage surprise surplus boxes, stock quantities, and bakery assignments</small>
    </div>
    <a href="<?= BASE_URL ?>/admin/box_form.php" class="btn btn-nrn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Food Box
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table admin-table m-0">
            <thead>
                <tr>
                    <th style="width: 70px;">Photo</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Bakery</th>
                    <th>Selling Price</th>
                    <th>Original Value</th>
                    <th>Stock</th>
                    <th>Availability</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($boxes)): ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">No food boxes found in the database.</td></tr>
                <?php else: ?>
                    <?php foreach ($boxes as $b): 
                        $isSnack = ($b['box_type'] === 'snack_box');
                    ?>
                        <tr>
                            <td>
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($b['image_url'] ?? 'assets/img/food_boxes/snack-box-1.jpg') ?>" alt="Thumb" class="rounded object-fit-cover" style="width: 50px; height: 50px;">
                            </td>
                            <td>
                                <strong class="text-green-dark d-block"><?= htmlspecialchars($b['name']) ?></strong>
                                <small class="text-muted"><?= htmlspecialchars(mb_strimwidth($b['description'], 0, 60, '...')) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars($b['category_name'] ?? ($isSnack ? 'Snack Box' : 'Meal Box')) ?>
                                </span>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($b['bakery_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($b['district']) ?></small>
                            </td>
                            <td><strong class="text-green-dark"><?= formatVnd($b['price']) ?></strong></td>
                            <td><span class="text-muted text-decoration-line-through"><?= formatVnd($b['original_value']) ?></span></td>
                            <td>
                                <span class="badge <?= $b['available_qty'] <= 3 ? 'bg-danger' : 'bg-primary' ?> fs-6">
                                    <?= (int)$b['available_qty'] ?> boxes
                                </span>
                            </td>
                            <td>
                                <form action="<?= BASE_URL ?>/admin/boxes.php" method="POST" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="box_id" value="<?= $b['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $b['is_active'] ? 'btn-success' : 'btn-secondary' ?>" title="Click to toggle availability">
                                        <?= $b['is_active'] ? 'Available' : 'Hidden' ?>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/admin/box_form.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit box details">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="<?= BASE_URL ?>/admin/boxes.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete or deactivate this food box?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="box_id" value="<?= $b['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
