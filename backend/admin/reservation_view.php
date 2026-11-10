<?php
/**
 * NomReNom - Administrator Detailed Reservation View & Status Editor
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$pdo = getDbConnection();
$adminId = currentUserId();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . BASE_URL . '/admin/reservations.php');
    exit;
}

// Fetch reservation details
$stmt = $pdo->prepare("SELECT r.*, b.name AS bakery_name, b.address, b.district, b.city, b.phone AS bakery_phone,
                              s.pickup_date, s.start_time, s.end_time,
                              COALESCE(u.full_name, g.full_name) AS customer_name,
                              COALESCE(u.email, g.email) AS customer_email,
                              COALESCE(u.phone, g.phone) AS customer_phone,
                              u.role AS user_role
                       FROM reservations r
                       JOIN bakeries b ON r.bakery_id = b.id
                       JOIN pickup_slots s ON r.pickup_slot_id = s.id
                       LEFT JOIN users u ON r.user_id = u.id
                       LEFT JOIN guest_customers g ON r.guest_id = g.id
                       WHERE r.id = ? LIMIT 1");
$stmt->execute([$id]);
$reservation = $stmt->fetch();

if (!$reservation) {
    setFlash('danger', 'Reservation information not found.');
    header('Location: ' . BASE_URL . '/admin/reservations.php');
    exit;
}

// Fetch line items
$itemsStmt = $pdo->prepare("SELECT * FROM reservation_items WHERE reservation_id = ?");
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();

// Fetch payment transactions
$payStmt = $pdo->prepare("SELECT * FROM payments WHERE reservation_id = ? ORDER BY id DESC");
$payStmt->execute([$id]);
$payments = $payStmt->fetchAll();

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $newReservationStatus = clean($_POST['reservation_status'] ?? $reservation['reservation_status']);
    $newPaymentStatus = clean($_POST['payment_status'] ?? $reservation['payment_status']);
    $adminNotes = clean($_POST['admin_notes'] ?? '');

    try {
        $pdo->beginTransaction();

        $oldStatus = $reservation['reservation_status'];

        // If cancelling, restore inventory atomically
        if ($newReservationStatus === 'Cancelled' && $oldStatus !== 'Cancelled') {
            foreach ($items as $item) {
                $pdo->prepare("UPDATE food_boxes SET available_qty = available_qty + ? WHERE id = ?")
                    ->execute([$item['quantity'], $item['food_box_id']]);
                $pdo->prepare("UPDATE pickup_slots SET reserved_count = GREATEST(0, reserved_count - ?) WHERE id = ?")
                    ->execute([$item['quantity'], $reservation['pickup_slot_id']]);
            }
        } elseif ($oldStatus === 'Cancelled' && $newReservationStatus !== 'Cancelled') {
            // Un-cancelling: check and re-deduct
            foreach ($items as $item) {
                $pdo->prepare("UPDATE food_boxes SET available_qty = GREATEST(0, available_qty - ?) WHERE id = ?")
                    ->execute([$item['quantity'], $item['food_box_id']]);
                $pdo->prepare("UPDATE pickup_slots SET reserved_count = reserved_count + ? WHERE id = ?")
                    ->execute([$item['quantity'], $reservation['pickup_slot_id']]);
            }
        }

        $collectedAt = ($newReservationStatus === 'Collected') ? date('Y-m-d H:i:s') : $reservation['collected_at'];
        $cancelledAt = ($newReservationStatus === 'Cancelled') ? date('Y-m-d H:i:s') : $reservation['cancelled_at'];

        $update = $pdo->prepare("UPDATE reservations SET 
            reservation_status = ?,
            payment_status = ?,
            collected_at = ?,
            cancelled_at = ?,
            cancellation_reason = ? 
            WHERE id = ?");
        $update->execute([
            $newReservationStatus,
            $newPaymentStatus,
            $collectedAt,
            $cancelledAt,
            $adminNotes,
            $id
        ]);

        // If payment status changed, record update on latest payment record
        if ($newPaymentStatus === 'Paid') {
            $pdo->prepare("UPDATE payments SET payment_status = 'Paid', payment_date = NOW() WHERE reservation_id = ?")
                ->execute([$id]);
        }

        logAdminActivity($pdo, $adminId, 'UPDATE_RESERVATION', 'RESERVATIONS', $id, 
            "Updated reservation status: [{$oldStatus} -> {$newReservationStatus}], Payment: [{$newPaymentStatus}].");

        $pdo->commit();
        setFlash('success', 'Reservation status and payment records updated successfully.');
        header('Location: ' . BASE_URL . '/admin/reservation_view.php?id=' . $id);
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setFlash('danger', 'Error updating reservation: ' . $e->getMessage());
        header('Location: ' . BASE_URL . '/admin/reservation_view.php?id=' . $id);
        exit;
    }
}

$pageTitle = 'Reservation Details #' . $reservation['reservation_code'];
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="row g-4">
    <!-- Left Column: Details -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <span class="badge bg-green-light text-green-dark fs-6 px-3 py-1 font-monospace">
                        #<?= htmlspecialchars($reservation['reservation_code']) ?>
                    </span>
                    <small class="text-muted ms-2">Reserved on: <?= formatDateTime($reservation['created_at']) ?></small>
                </div>
                <div class="d-flex gap-2">
                    <span class="status-badge status-<?= strtolower($reservation['reservation_status']) ?>">
                        <?= htmlspecialchars($reservation['reservation_status']) ?>
                    </span>
                    <span class="status-badge payment-<?= strtolower(str_replace(' ', '', $reservation['payment_status'])) ?>">
                        <?= htmlspecialchars($reservation['payment_status']) ?>
                    </span>
                </div>
            </div>

            <!-- Customer & Bakery Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 h-100 small">
                        <strong class="d-block text-green-dark fs-6 mb-2"><i class="bi bi-person-fill text-success me-1"></i> Customer Profile</strong>
                        <div><strong>Full Name:</strong> <?= htmlspecialchars($reservation['customer_name'] ?? 'Guest Customer') ?></div>
                        <div><strong>Email:</strong> <?= htmlspecialchars($reservation['customer_email']) ?></div>
                        <div><strong>Phone:</strong> <?= htmlspecialchars($reservation['customer_phone']) ?></div>
                        <div class="mt-1">
                            <span class="badge bg-secondary"><?= !empty($reservation['user_id']) ? 'Registered Member' : 'Guest Customer' ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 h-100 small">
                        <?php if ($reservation['fulfillment_type'] === 'delivery'): ?>
                            <strong class="d-block text-primary fs-6 mb-2"><i class="bi bi-bicycle me-1"></i> Delivery Order</strong>
                            <div><strong>Delivery Address:</strong> <?= htmlspecialchars($reservation['delivery_address'] ?? 'Campus Address') ?></div>
                            <div><strong>Bakery Source:</strong> <?= htmlspecialchars($reservation['bakery_name']) ?></div>
                        <?php else: ?>
                            <strong class="d-block text-green-dark fs-6 mb-2"><i class="bi bi-shop text-success me-1"></i> Store Pickup Location</strong>
                            <div><strong>Bakery:</strong> <?= htmlspecialchars($reservation['bakery_name']) ?></div>
                            <div><strong>Address:</strong> <?= htmlspecialchars($reservation['address']) ?>, <?= htmlspecialchars($reservation['district']) ?></div>
                            <?php if (!empty($reservation['pickup_date'])): ?>
                                <div class="mt-1 text-green-dark fw-bold">
                                    <i class="bi bi-clock me-1"></i> <?= formatDateTime($reservation['pickup_date'], 'M d, Y') ?> (<?= formatTime($reservation['start_time']) ?> – <?= formatTime($reservation['end_time']) ?>)
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <h6 class="text-green-dark fw-bold mb-2">Reserved Food Boxes</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead class="table-light small">
                        <tr>
                            <th>Product Name</th>
                            <th class="text-center">Unit Price</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($item['box_name']) ?></strong></td>
                                <td class="text-center"><?= formatVnd($item['unit_price']) ?></td>
                                <td class="text-center font-monospace fw-bold">x<?= (int)$item['quantity'] ?></td>
                                <td class="text-end fw-bold"><?= formatVnd($item['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total Reservation Due:</th>
                            <th class="text-end fs-5 text-green-dark"><?= formatVnd($reservation['total_amount']) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Payment Records -->
            <h6 class="text-green-dark fw-bold mb-2"><i class="bi bi-credit-card me-1"></i> Payment Transactions</h6>
            <?php if (empty($payments)): ?>
                <p class="small text-muted">No gateway transactions recorded for this reservation.</p>
            <?php else: ?>
                <div class="table-responsive small">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Gateway / Method</th>
                                <th>Transaction Ref</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td><?= strtoupper(htmlspecialchars($p['gateway_provider'])) ?> (<?= htmlspecialchars($p['payment_method']) ?>)</td>
                                    <td><code><?= htmlspecialchars($p['transaction_reference'] ?? 'N/A') ?></code></td>
                                    <td><strong><?= formatVnd($p['amount']) ?></strong></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['payment_status']) ?></span></td>
                                    <td><?= formatDateTime($p['payment_date'] ?? $p['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Status Editor -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 90px;">
            <h5 class="text-green-dark font-serif mb-3">Status Management</h5>

            <form action="<?= BASE_URL ?>/admin/reservation_view.php?id=<?= $id ?>" method="POST">
                <?= csrfField() ?>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Reservation Status</label>
                    <select name="reservation_status" class="form-select" required>
                        <option value="Pending" <?= $reservation['reservation_status'] === 'Pending' ? 'selected' : '' ?>>Pending (Order Placed)</option>
                        <option value="Confirmed" <?= $reservation['reservation_status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed (Bakery Accepted)</option>
                        <option value="Preparing" <?= $reservation['reservation_status'] === 'Preparing' ? 'selected' : '' ?>>Preparing (Baking & Packing)</option>
                        <option value="Ready for Pickup" <?= $reservation['reservation_status'] === 'Ready for Pickup' ? 'selected' : '' ?>>Ready for Pickup / Out for Delivery</option>
                        <option value="Completed" <?= in_array($reservation['reservation_status'], ['Completed', 'Collected']) ? 'selected' : '' ?>>Completed</option>
                        <option value="Cancelled" <?= $reservation['reservation_status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled (Restores Stock)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Payment Status</label>
                    <select name="payment_status" class="form-select" required>
                        <option value="Pay at Pickup" <?= $reservation['payment_status'] === 'Pay at Pickup' ? 'selected' : '' ?>>Pay at Pickup</option>
                        <option value="Paid" <?= $reservation['payment_status'] === 'Paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="Pending" <?= $reservation['payment_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Failed" <?= $reservation['payment_status'] === 'Failed' ? 'selected' : '' ?>>Failed</option>
                        <option value="Refunded" <?= $reservation['payment_status'] === 'Refunded' ? 'selected' : '' ?>>Refunded</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Admin Notes / Cancellation Reason</label>
                    <textarea name="admin_notes" rows="3" class="form-control" placeholder="Internal notes or customer reason..."><?= htmlspecialchars($reservation['cancellation_reason'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-nrn-primary w-100 py-3 mb-2">
                    <i class="bi bi-save-fill me-1"></i> Save Changes
                </button>

                <a href="<?= BASE_URL ?>/admin/reservations.php" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="bi bi-arrow-left me-1"></i> Back to Reservations
                </a>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
