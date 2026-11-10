<?php
/**
 * NomReNom - Reservation Confirmation & Printable Receipt
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$code = clean($_GET['code'] ?? '');
if (empty($code)) {
    setFlash('warning', 'Please provide a reservation reference to view your confirmation.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT r.*, b.name AS bakery_name, b.address, b.district, b.city, b.phone AS bakery_phone, 
                              s.pickup_date, s.start_time, s.end_time 
                       FROM reservations r 
                       JOIN bakeries b ON r.bakery_id = b.id 
                       JOIN pickup_slots s ON r.pickup_slot_id = s.id 
                       WHERE r.reservation_code = ? LIMIT 1");
$stmt->execute([$code]);
$reservation = $stmt->fetch();

if (!$reservation) {
    setFlash('danger', 'No reservation found for reference: ' . htmlspecialchars($code));
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Fetch line items
$itemsStmt = $pdo->prepare("SELECT * FROM reservation_items WHERE reservation_id = ?");
$itemsStmt->execute([$reservation['id']]);
$items = $itemsStmt->fetchAll();

// Fetch payment details
$payStmt = $pdo->prepare("SELECT * FROM payments WHERE reservation_id = ? ORDER BY id DESC LIMIT 1");
$payStmt->execute([$reservation['id']]);
$payment = $payStmt->fetch();

$pageTitle = 'Reservation Confirmation #' . $reservation['reservation_code'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Printable Receipt Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white receipt-container" id="printableReceipt">
                <!-- Status Header -->
                <div class="text-center mb-4 pb-3 border-bottom">
                    <?php if ($reservation['reservation_status'] === 'Collected'): ?>
                        <div class="step-num bg-success text-white mb-2"><i class="bi bi-bag-check-fill"></i></div>
                        <h2 class="text-success fw-bold mb-1">Pickup Completed</h2>
                        <p class="text-muted small">Thank you for helping rescue surplus food with NomReNom!</p>
                    <?php elseif ($reservation['reservation_status'] === 'Cancelled'): ?>
                        <div class="step-num bg-danger text-white mb-2"><i class="bi bi-x-lg"></i></div>
                        <h2 class="text-danger fw-bold mb-1">Reservation Cancelled</h2>
                        <p class="text-muted small">Reason: <?= htmlspecialchars($reservation['cancellation_reason'] ?? 'Customer request') ?></p>
                    <?php elseif ($reservation['payment_status'] === 'Pending'): ?>
                        <div class="step-num bg-warning text-dark mb-2"><i class="bi bi-hourglass-split"></i></div>
                        <h2 class="text-warning fw-bold mb-1">Awaiting Online Payment</h2>
                        <p class="text-muted small">Please complete your gateway payment to confirm your reservation spot.</p>
                        <a href="<?= BASE_URL ?>/payments/vnpay_create.php?code=<?= urlencode($reservation['reservation_code']) ?>" class="btn btn-primary btn-sm mt-2">
                            <i class="bi bi-credit-card me-1"></i> Pay Now via VNPay
                        </a>
                    <?php else: ?>
                        <div class="step-num bg-green-light text-green-dark mb-2"><i class="bi bi-check2-circle fs-3 text-success"></i></div>
                        <h2 class="fw-bold text-dark mb-1">Reservation Confirmed!</h2>
                        <p class="text-muted small">Your reservation has been sent to the bakery. Please arrive during your pickup window.</p>
                    <?php endif; ?>

                    <!-- Big Reservation Code Badge -->
                    <div class="mt-3 p-3 bg-light rounded-3 border d-inline-block">
                        <small class="text-muted text-uppercase d-block fw-bold letter-spacing-1">Reservation Reference</small>
                        <span class="fs-2 fw-bold text-dark user-select-all"><?= htmlspecialchars($reservation['reservation_code']) ?></span>
                    </div>
                </div>

                <!-- Pickup & Store Details -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-3 h-100">
                            <h6 class="text-green-dark fw-bold mb-2"><i class="bi bi-shop me-1 text-success"></i> Pickup Location</h6>
                            <strong class="d-block text-dark"><?= htmlspecialchars($reservation['bakery_name']) ?></strong>
                            <small class="text-muted d-block mt-1"><?= htmlspecialchars($reservation['address']) ?>, <?= htmlspecialchars($reservation['district']) ?></small>
                            <small class="text-muted d-block mt-1"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($reservation['bakery_phone']) ?></small>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-3 h-100">
                            <h6 class="text-green-dark fw-bold mb-2"><i class="bi bi-clock-history me-1 text-warning"></i> Collection Time Window</h6>
                            <strong class="d-block text-green-dark fs-6"><?= formatDateTime($reservation['pickup_date'], 'M d, Y') ?></strong>
                            <span class="badge bg-green-dark text-white mt-1 fs-6">
                                <?= formatTime($reservation['start_time']) ?> – <?= formatTime($reservation['end_time']) ?>
                            </span>
                            <small class="text-muted d-block mt-2 fst-italic">* Please arrive on time to collect the freshest bakery items.</small>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <h6 class="text-green-dark fw-bold mb-2"><i class="bi bi-basket me-1 text-success"></i> Reserved Food Box Items</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Item Name</th>
                                <th class="text-center" style="width: 110px;">Unit Price</th>
                                <th class="text-center" style="width: 80px;">Qty</th>
                                <th class="text-end" style="width: 130px;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($it['box_name']) ?></strong></td>
                                    <td class="text-center"><?= formatVnd($it['unit_price']) ?></td>
                                    <td class="text-center font-monospace fw-bold">x<?= (int)$it['quantity'] ?></td>
                                    <td class="text-end fw-bold"><?= formatVnd($it['subtotal']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total Amount:</th>
                                <th class="text-end text-green-dark fs-5"><?= formatVnd($reservation['total_amount']) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Payment Status Card -->
                <div class="p-3 bg-cream rounded-3 border mb-4 small">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span>Payment Method:</span>
                        <strong><?= $reservation['payment_method'] === 'Pickup' ? 'Pay at Pickup Counter' : 'Online Payment (' . htmlspecialchars($payment['gateway_provider'] ?? 'Online') . ')' ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span>Payment Status:</span>
                        <span class="badge <?= $reservation['payment_status'] === 'Paid' ? 'bg-success' : ($reservation['payment_status'] === 'Pay at Pickup' ? 'bg-info text-dark' : 'bg-warning text-dark') ?>">
                            <?= htmlspecialchars($reservation['payment_status']) ?>
                        </span>
                    </div>
                    <?php if (!empty($reservation['special_instructions'])): ?>
                        <div class="mt-2 pt-2 border-top">
                            <span class="text-muted">Special instructions:</span> <em><?= htmlspecialchars($reservation['special_instructions']) ?></em>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actions / Print / Home -->
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center print-hide">
                    <div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i> Print Receipt
                        </button>
                        <a href="<?= BASE_URL ?>/reservation_lookup.php?code=<?= urlencode($reservation['reservation_code']) ?>" class="btn btn-outline-success btn-sm ms-2">
                            <i class="bi bi-link-45deg me-1"></i> Tracking Link
                        </a>
                    </div>
                    <div>
                        <?php if (isLoggedIn()): ?>
                            <a href="<?= BASE_URL ?>/my_reservations.php" class="btn btn-nrn-outline btn-sm">
                                <i class="bi bi-list-check me-1"></i> My Orders
                            </a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-nrn-primary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> Browse More Boxes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .navbar-nomrenom, .footer-nomrenom, .print-hide, .flash-container {
        display: none !important;
    }
    body, .bg-cream {
        background: #FFF !important;
    }
    .receipt-container {
        box-shadow: none !important;
        border: 1px solid #CCC !important;
        padding: 0 !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
