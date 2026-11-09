<?php
/**
 * NomReNom - Administrator Feedback Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$pdo = getDbConnection();
$adminId = currentUserId();

// Handle Toggle Visibility or Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = clean($_POST['action'] ?? '');
    $feedbackId = (int)($_POST['feedback_id'] ?? 0);

    if ($action === 'toggle_visibility' && $feedbackId > 0) {
        $pdo->prepare("UPDATE feedback SET is_public = NOT is_public WHERE id = ?")->execute([$feedbackId]);
        logAdminActivity($pdo, $adminId, 'TOGGLE_FEEDBACK', 'FEEDBACK', $feedbackId, 'Toggled public visibility of customer review.');
        setFlash('success', 'Review visibility status updated.');
        header('Location: ' . BASE_URL . '/admin/feedback.php');
        exit;
    } elseif ($action === 'delete' && $feedbackId > 0) {
        $pdo->prepare("DELETE FROM feedback WHERE id = ?")->execute([$feedbackId]);
        logAdminActivity($pdo, $adminId, 'DELETE_FEEDBACK', 'FEEDBACK', $feedbackId, 'Deleted customer review from system.');
        setFlash('success', 'Customer review deleted successfully.');
        header('Location: ' . BASE_URL . '/admin/feedback.php');
        exit;
    }
}

// Fetch all feedback
$stmt = $pdo->query("SELECT fb.*, r.reservation_code, b.name AS bakery_name, bx.name AS box_name 
                     FROM feedback fb 
                     LEFT JOIN reservations r ON fb.reservation_id = r.id 
                     LEFT JOIN food_boxes bx ON fb.food_box_id = bx.id 
                     LEFT JOIN bakeries b ON (bx.bakery_id = b.id OR r.bakery_id = b.id) 
                     ORDER BY fb.created_at DESC");
$feedbacks = $stmt->fetchAll();

$pageTitle = 'Customer Reviews Management';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-4">
    <h4 class="font-serif text-green-dark m-0">Customer Reviews & Moderation</h4>
    <small class="text-muted">Moderate ratings, manage public feedback, and monitor bakery satisfaction scores</small>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table admin-table m-0">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Order / Food Box</th>
                    <th>Rating</th>
                    <th>Comments</th>
                    <th>Visibility</th>
                    <th>Timestamp</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($feedbacks)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No customer reviews submitted yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($feedbacks as $fb): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($fb['customer_name']) ?></strong></td>
                            <td>
                                <?php if (!empty($fb['reservation_code'])): ?>
                                    <span class="badge bg-light text-dark border">#<?= htmlspecialchars($fb['reservation_code']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($fb['box_name'])): ?>
                                    <small class="text-muted d-block"><?= htmlspecialchars($fb['box_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="text-warning small text-nowrap">
                                    <?php for ($i=1; $i<=5; $i++): ?>
                                        <i class="bi <?= $i <= $fb['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                    <?php endfor; ?>
                                    <span class="text-dark ms-1 fw-bold">(<?= $fb['rating'] ?>/5)</span>
                                </div>
                            </td>
                            <td>
                                <span class="small text-muted"><?= nl2br(htmlspecialchars($fb['comment'])) ?></span>
                            </td>
                            <td>
                                <form action="<?= BASE_URL ?>/admin/feedback.php" method="POST" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="toggle_visibility">
                                    <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $fb['is_public'] ? 'btn-success' : 'btn-secondary' ?>">
                                        <?= $fb['is_public'] ? 'Public' : 'Hidden' ?>
                                    </button>
                                </form>
                            </td>
                            <td><small class="text-muted"><?= formatDateTime($fb['created_at']) ?></small></td>
                            <td class="text-end">
                                <form action="<?= BASE_URL ?>/admin/feedback.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this review?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
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
