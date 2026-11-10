<?php
/**
 * NomReNom - Live Snack Pickup & Counter Inspection Tracking
 * Real-time pickup status, digital counter voucher, and customer inspection guarantee:
 * [ Confirm Pickup & Take Home ] or [ Refuse at Counter / Doesn't Match Photo -> Instant Refund ]
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDbConnection();
$loggedInUser = currentUser();

$orderCode = clean($_GET['code'] ?? $_POST['code'] ?? '');
$searchQuery = clean($_POST['search_query'] ?? '');
$order = null;
$error = null;

// Handle Counter Inspection Refusal (Item Doesn't Match Photo -> Refund)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'refuse_order') {
    requireCsrfToken();
    $refuseCode = clean($_POST['refuse_order_code'] ?? '');
    $refusalReason = clean($_POST['refusal_reason'] ?? 'Item does not match photo');
    $refusalNotes = clean($_POST['refusal_notes'] ?? '');

    $chkStmt = $pdo->prepare("SELECT id, reservation_status, total_amount, payment_method FROM reservations WHERE reservation_code = ?");
    $chkStmt->execute([$refuseCode]);
    $toRefuse = $chkStmt->fetch();

    if ($toRefuse && !in_array(strtolower($toRefuse['reservation_status']), ['completed', 'refused at counter', 'cancelled'])) {
        $upd = $pdo->prepare("UPDATE reservations 
                              SET reservation_status = 'Refused at Counter', 
                                  payment_status = 'Refunded', 
                                  refusal_reason = ?, 
                                  refusal_notes = ?, 
                                  updated_at = NOW() 
                              WHERE id = ?");
        $upd->execute([$refusalReason, $refusalNotes, $toRefuse['id']]);

        // Restock inventory
        $itemsStmt = $pdo->prepare("SELECT food_box_id, quantity FROM reservation_items WHERE reservation_id = ?");
        $itemsStmt->execute([$toRefuse['id']]);
        $items = $itemsStmt->fetchAll();
        $restockStmt = $pdo->prepare("UPDATE food_boxes SET available_qty = available_qty + ? WHERE id = ?");
        foreach ($items as $it) {
            $restockStmt->execute([$it['quantity'], $it['food_box_id']]);
        }

        // Record refund in payments table
        $refundPay = $pdo->prepare("UPDATE payments SET payment_status = 'Refunded' WHERE reservation_id = ?");
        $refundPay->execute([$toRefuse['id']]);

        setFlash('warning', "Inspection report recorded: Item refused because it did not match photo. A full refund of <strong>" . formatVnd($toRefuse['total_amount']) . "</strong> has been initiated!");
        header("Location: " . BASE_URL . "/track.php?code=" . urlencode($refuseCode));
        exit;
    } else {
        $error = 'This order cannot be refused because it has already been finalized.';
    }
}

// Handle Customer Counter Acceptance (Picked Up & Taken Home)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_pickup') {
    requireCsrfToken();
    $confirmCode = clean($_POST['confirm_order_code'] ?? '');

    $chkStmt = $pdo->prepare("SELECT id, reservation_status FROM reservations WHERE reservation_code = ?");
    $chkStmt->execute([$confirmCode]);
    $toConfirm = $chkStmt->fetch();

    if ($toConfirm && !in_array(strtolower($toConfirm['reservation_status']), ['completed', 'refused at counter', 'cancelled'])) {
        $upd = $pdo->prepare("UPDATE reservations SET reservation_status = 'Completed', payment_status = 'Paid', updated_at = NOW() WHERE id = ?");
        $upd->execute([$toConfirm['id']]);

        setFlash('success', "Pickup confirmed! Thank you for inspecting your snack and rescuing food waste with NomReNom.");
        header("Location: " . BASE_URL . "/track.php?code=" . urlencode($confirmCode));
        exit;
    }
}

// Handle Order Cancellation (before pickup window)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    requireCsrfToken();
    $cancelCode = clean($_POST['cancel_order_code'] ?? '');
    $cancelReason = clean($_POST['cancel_reason'] ?? 'Customer cancellation');

    $chkStmt = $pdo->prepare("SELECT id, reservation_status FROM reservations WHERE reservation_code = ?");
    $chkStmt->execute([$cancelCode]);
    $toCancel = $chkStmt->fetch();

    if ($toCancel && in_array(strtolower($toCancel['reservation_status']), ['pending', 'confirmed'])) {
        $upd = $pdo->prepare("UPDATE reservations SET reservation_status = 'Cancelled', cancellation_reason = ?, cancelled_at = NOW() WHERE id = ?");
        $upd->execute([$cancelReason, $toCancel['id']]);

        // Restore stock
        $itemsStmt = $pdo->prepare("SELECT food_box_id, quantity FROM reservation_items WHERE reservation_id = ?");
        $itemsStmt->execute([$toCancel['id']]);
        $items = $itemsStmt->fetchAll();
        $restockStmt = $pdo->prepare("UPDATE food_boxes SET available_qty = available_qty + ? WHERE id = ?");
        foreach ($items as $it) {
            $restockStmt->execute([$it['quantity'], $it['food_box_id']]);
        }

        setFlash('warning', "Reservation #{$cancelCode} has been cancelled.");
        header("Location: " . BASE_URL . "/track.php?code=" . urlencode($cancelCode));
        exit;
    } else {
        $error = 'This order cannot be cancelled because it is already preparing or completed.';
    }
}

// Lookup order by code or phone
if (!empty($orderCode)) {
    $stmt = $pdo->prepare("SELECT r.*, b.name AS bakery_name, b.address, b.district, b.city, b.phone AS bakery_phone,
                                  b.opening_time, b.closing_time,
                                  s.pickup_date, s.start_time, s.end_time 
                           FROM reservations r 
                           JOIN bakeries b ON r.bakery_id = b.id 
                           LEFT JOIN pickup_slots s ON r.pickup_slot_id = s.id 
                           WHERE r.reservation_code = ? LIMIT 1");
    $stmt->execute([$orderCode]);
    $order = $stmt->fetch();
} elseif (!empty($searchQuery)) {
    $stmt = $pdo->prepare("SELECT r.*, b.name AS bakery_name, b.address, b.district, b.city, b.phone AS bakery_phone,
                                  b.opening_time, b.closing_time,
                                  s.pickup_date, s.start_time, s.end_time 
                           FROM reservations r 
                           JOIN bakeries b ON r.bakery_id = b.id 
                           LEFT JOIN pickup_slots s ON r.pickup_slot_id = s.id 
                           WHERE r.reservation_code = ? OR r.customer_phone = ? 
                           ORDER BY r.id DESC LIMIT 1");
    $stmt->execute([$searchQuery, $searchQuery]);
    $order = $stmt->fetch();
    if ($order) {
        $orderCode = $order['reservation_code'];
    } else {
        $error = "No active snack reservation found for code or phone number: '{$searchQuery}'.";
    }
} elseif ($loggedInUser) {
    $stmt = $pdo->prepare("SELECT r.*, b.name AS bakery_name, b.address, b.district, b.city, b.phone AS bakery_phone,
                                  b.opening_time, b.closing_time,
                                  s.pickup_date, s.start_time, s.end_time 
                           FROM reservations r 
                           JOIN bakeries b ON r.bakery_id = b.id 
                           LEFT JOIN pickup_slots s ON r.pickup_slot_id = s.id 
                           WHERE r.user_id = ? 
                           ORDER BY r.id DESC LIMIT 1");
    $stmt->execute([$loggedInUser['id']]);
    $order = $stmt->fetch();
    if ($order) {
        $orderCode = $order['reservation_code'];
    }
}

// Fetch line items with their condition grade & defect notes
$orderItems = [];
$payment = null;
if ($order) {
    $itStmt = $pdo->prepare("SELECT ri.*, fb.condition_grade, fb.expiry_date, fb.defect_notes, fb.image_url 
                            FROM reservation_items ri 
                            LEFT JOIN food_boxes fb ON ri.food_box_id = fb.id 
                            WHERE ri.reservation_id = ?");
    $itStmt->execute([$order['id']]);
    $orderItems = $itStmt->fetchAll();

    $payStmt = $pdo->prepare("SELECT * FROM payments WHERE reservation_id = ? ORDER BY id DESC LIMIT 1");
    $payStmt->execute([$order['id']]);
    $payment = $payStmt->fetch();
}

$pageTitle = $order ? 'Pickup Voucher #' . $order['reservation_code'] : 'Track & Inspect Pickup – NomReNom';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
                <li class="breadcrumb-item active text-dark" aria-current="page">Snack Pickup & Counter Inspection</li>
            </ol>
        </nav>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h1 class="h3 fw-bold text-dark m-0 d-flex align-items-center gap-2">
                <i class="bi bi-qr-code-scan text-success"></i> Snack Pickup Voucher & Counter Inspection
            </h1>
            <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill">
                <i class="bi bi-shield-check me-1"></i> Inspect at Counter Guarantee
            </span>
        </div>
    </div>

    <!-- Order Search Bar -->
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
        <form method="POST" action="<?= BASE_URL ?>/track.php" class="row g-2 align-items-center">
            <?= csrfField() ?>
            <div class="col-12 col-md-8">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search_query" class="form-control border-0 bg-light" 
                           placeholder="Enter your 6-digit Pickup Code (e.g. NRN-20261109-XXXX) or Phone Number..." 
                           value="<?= htmlspecialchars($orderCode ?: $searchQuery) ?>" required>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <button type="submit" class="btn btn-nrn-primary w-100 py-2 rounded-pill">
                    <i class="bi bi-ticket-perforated me-1"></i> View Pickup Voucher
                </button>
            </div>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center rounded-4 mb-4 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($order): 
        $statusStr = strtolower(trim($order['reservation_status']));
        $isRefused = ($statusStr === 'refused at counter');
        $isCancelled = ($statusStr === 'cancelled');
        $isCompleted = ($statusStr === 'completed' || $statusStr === 'collected');
        
        // 4-stage stepper mapping
        $stage = match($statusStr) {
            'pending' => 1,
            'confirmed' => 2,
            'ready for pickup', 'ready', 'preparing' => 2,
            'completed', 'collected' => 4,
            default => 2
        };
        if ($isRefused) {
            $stage = -2;
        } elseif ($isCancelled) {
            $stage = -1;
        }
    ?>
        <div class="row g-4">
            <!-- Left: Digital Pickup Voucher & Stepper -->
            <div class="col-lg-8">
                <!-- Status Timeline Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 pb-3 border-bottom">
                        <div>
                            <span class="text-muted small text-uppercase fw-bold letter-spacing-1">Digital Pickup Code</span>
                            <h2 class="fw-bold text-green-dark m-0 font-monospace user-select-all"><?= htmlspecialchars($order['reservation_code']) ?></h2>
                            <div class="text-muted small mt-1">Reserved on <?= formatDateTime($order['created_at'], 'M d, Y \a\t H:i') ?></div>
                        </div>
                        <div class="text-end">
                            <?php if ($isRefused): ?>
                                <span class="badge bg-danger fs-6 px-3 py-2 rounded-pill">
                                    <i class="bi bi-x-octagon-fill me-1"></i> Refused at Counter (Refunded)
                                </span>
                            <?php elseif ($isCancelled): ?>
                                <span class="badge bg-secondary fs-6 px-3 py-2 rounded-pill">Cancelled</span>
                            <?php elseif ($isCompleted): ?>
                                <span class="badge bg-success fs-6 px-3 py-2 rounded-pill">
                                    <i class="bi bi-check2-circle me-1"></i> Picked Up & Taken Home
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success fs-6 px-3 py-2 rounded-pill">
                                    <span class="pulse-dot me-1"></span> Ready for Counter Pickup
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($isRefused): ?>
                        <div class="alert alert-danger rounded-4 p-4 mb-0 border-start border-4 border-danger">
                            <h5 class="fw-bold mb-2"><i class="bi bi-shield-x me-2"></i> Snack Refused at Counter — 100% Refund Initiated</h5>
                            <p class="small mb-2">
                                <strong>Refusal Reason:</strong> <?= htmlspecialchars($order['refusal_reason'] ?? 'Item discrepancy') ?>
                            </p>
                            <?php if (!empty($order['refusal_notes'])): ?>
                                <p class="small text-muted mb-2">
                                    <strong>Inspection Note:</strong> "<?= htmlspecialchars($order['refusal_notes']) ?>"
                                </p>
                            <?php endif; ?>
                            <div class="small text-success fw-bold">
                                <i class="bi bi-check-circle-fill me-1"></i> A full refund of <?= formatVnd($order['total_amount']) ?> has been processed via VietQR.
                            </div>
                        </div>
                    <?php elseif ($isCancelled): ?>
                        <div class="alert alert-secondary rounded-4 p-3 mb-0">
                            <h6 class="fw-bold mb-1"><i class="bi bi-x-circle-fill me-1"></i> This reservation was cancelled</h6>
                            <p class="small mb-0">Reason: <?= htmlspecialchars($order['cancellation_reason'] ?? 'Cancelled by customer') ?></p>
                        </div>
                    <?php else: ?>
                        <!-- 4-Stage Live Progress Stepper -->
                        <div class="order-progress-stepper my-3">
                            <div class="stepper-line">
                                <div class="stepper-line-fill" style="width: <?= min(100, max(0, ($stage - 1) * 33.3)) ?>%;"></div>
                            </div>
                            <div class="stepper-steps">
                                <!-- Step 1 -->
                                <div class="stepper-step <?= $stage >= 1 ? 'completed' : '' ?> <?= $stage === 1 ? 'current' : '' ?>">
                                    <div class="step-circle">
                                        <i class="bi <?= $stage > 1 ? 'bi-check-lg' : 'bi-qr-code' ?>"></i>
                                    </div>
                                    <div class="step-title">1. Reserved & Paid</div>
                                    <div class="step-sub">VietQR Verified</div>
                                </div>

                                <!-- Step 2 -->
                                <div class="stepper-step <?= $stage >= 2 ? 'completed' : '' ?> <?= $stage === 2 ? 'current' : '' ?>">
                                    <div class="step-circle">
                                        <i class="bi <?= $stage > 2 ? 'bi-check-lg' : 'bi-shop' ?>"></i>
                                    </div>
                                    <div class="step-title">2. Ready at Store</div>
                                    <div class="step-sub">At cashier counter</div>
                                </div>

                                <!-- Step 3 -->
                                <div class="stepper-step <?= $stage >= 3 ? 'completed' : '' ?> <?= $stage === 3 ? 'current' : '' ?>">
                                    <div class="step-circle">
                                        <i class="bi <?= $stage > 3 ? 'bi-check-lg' : 'bi-search' ?>"></i>
                                    </div>
                                    <div class="step-title">3. Inspect at Store</div>
                                    <div class="step-sub">Check vs photo</div>
                                </div>

                                <!-- Step 4 -->
                                <div class="stepper-step <?= $stage >= 4 ? 'completed' : '' ?>">
                                    <div class="step-circle">
                                        <i class="bi bi-bag-check-fill"></i>
                                    </div>
                                    <div class="step-title">4. Pick Up & Enjoy</div>
                                    <div class="step-sub">Taken home</div>
                                </div>
                            </div>
                        </div>

                        <!-- Real-time Status Guidance Box -->
                        <div class="p-3 bg-light rounded-4 border mt-4">
                            <?php if ($stage < 4): ?>
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-geo-alt-fill fs-2 text-success"></i>
                                    <div>
                                        <strong class="d-block text-dark">Ready for Pickup at <?= htmlspecialchars($order['bakery_name']) ?>!</strong>
                                        <span class="small text-muted">
                                            Show your pickup code <strong><?= htmlspecialchars($order['reservation_code']) ?></strong> to the cashier counter. Compare your snack with the real photo before confirming.
                                        </span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="bi bi-emoji-smile-fill fs-2 text-success"></i>
                                        <div>
                                            <strong class="d-block text-dark">Snack Inspected & Collected!</strong>
                                            <span class="small text-muted">Thank you for rescuing imperfect snacks and cutting student food waste!</span>
                                        </div>
                                    </div>
                                    <a href="<?= BASE_URL ?>/feedback.php?code=<?= urlencode($order['reservation_code']) ?>" class="btn btn-warning text-dark fw-bold btn-sm text-nowrap rounded-pill px-3 shadow-sm">
                                        <i class="bi bi-star-fill me-1"></i> Rate This Snack
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- CRITICAL: Counter Inspection Action Bar (While at Store Counter) -->
                <?php if (!$isRefused && !$isCancelled && !$isCompleted): ?>
                    <div class="card border-0 shadow rounded-4 p-4 bg-white mb-4 border-top border-4 border-warning">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-shield-check text-warning fs-3"></i>
                            <div>
                                <h5 class="fw-bold text-dark m-0">Store Counter Inspection & Decision</h5>
                                <small class="text-muted">Inspect the snacks handed to you by the store cashier:</small>
                            </div>
                        </div>

                        <div class="row g-3">
                            <!-- Option A: Happy with item -> Confirm & Take Home -->
                            <div class="col-md-6">
                                <form method="POST" action="<?= BASE_URL ?>/track.php">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="confirm_pickup">
                                    <input type="hidden" name="confirm_order_code" value="<?= htmlspecialchars($order['reservation_code']) ?>">
                                    <button type="submit" class="btn btn-success w-100 py-3 rounded-4 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                                        <i class="bi bi-check2-circle fs-5"></i>
                                        <span>Confirm Pickup & Take Home</span>
                                    </button>
                                    <small class="text-muted text-center d-block mt-2">Item matches photo and I'm ready to take it.</small>
                                </form>
                            </div>

                            <!-- Option B: Discrepancy -> Refuse & Get Refund -->
                            <div class="col-md-6">
                                <button type="button" class="btn btn-outline-danger w-100 py-3 rounded-4 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#refuseModal">
                                    <i class="bi bi-x-circle fs-5"></i>
                                    <span>Refuse Item (Doesn't Match Photo)</span>
                                </button>
                                <small class="text-danger text-center d-block mt-2">Doesn't match photo? Tap here for instant refund.</small>
                            </div>
                        </div>

                        <!-- Refuse Modal -->
                        <div class="modal fade" id="refuseModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-4 border-0">
                                    <form method="POST" action="<?= BASE_URL ?>/track.php">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="refuse_order">
                                        <input type="hidden" name="refuse_order_code" value="<?= htmlspecialchars($order['reservation_code']) ?>">
                                        <div class="modal-header border-bottom bg-danger bg-opacity-10">
                                            <h5 class="modal-title fw-bold text-danger">
                                                <i class="bi bi-shield-x me-2"></i> Refuse Item & Request Refund
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <p class="small text-muted mb-3">
                                                At NomReNom, transparency is our promise. If the snack provided by the store has worse damage than shown on the photo or has any unlisted defect, you can refuse to take it and we will refund you 100% immediately.
                                            </p>
                                            
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Select Discrepancy Reason: <span class="text-danger">*</span></label>
                                                <select name="refusal_reason" class="form-select rounded-3" required>
                                                    <option value="Packaging damage is much worse than photo shown">Packaging damage is much worse than photo shown</option>
                                                    <option value="Food appears broken/melted beyond what was described">Food appears broken/melted beyond what was described</option>
                                                    <option value="Printed expiry date is less than 7 days left or expired">Printed expiry date is less than 7 days left or expired</option>
                                                    <option value="Factory inner seal is punctured or broken">Factory inner seal is punctured or broken</option>
                                                    <option value="Store handed me the wrong item/flavor">Store handed me the wrong item/flavor</option>
                                                    <option value="Other condition discrepancy">Other condition discrepancy</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Inspection Details / Comments (Optional):</label>
                                                <textarea name="refusal_notes" class="form-control rounded-3" rows="3" placeholder="Describe what looks different from the listing photo..."></textarea>
                                            </div>

                                            <div class="alert alert-info py-2 px-3 small rounded-3 m-0">
                                                <i class="bi bi-info-circle me-1"></i> You do not need to hand cash to the cashier. Leave the snack at the counter and our system will reverse your VietQR payment of <strong><?= formatVnd($order['total_amount']) ?></strong>.
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top">
                                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Back</button>
                                            <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">
                                                Confirm Refusal & Refund
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Items Reserved Details Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark mb-3">Reserved Snacks in this Voucher</h5>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-3 py-2">Snack Item</th>
                                    <th class="py-2">Condition Grade</th>
                                    <th class="py-2 text-center">Qty</th>
                                    <th class="pe-3 py-2 text-end">Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td class="ps-3 py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($item['image_url'] ?? 'assets/img/food_boxes/snack-box-1.jpg') ?>" 
                                                     alt="<?= htmlspecialchars($item['box_name']) ?>" 
                                                     class="rounded-3 object-fit-cover" 
                                                     style="width: 50px; height: 50px;">
                                                <div>
                                                    <strong class="text-dark d-block"><?= htmlspecialchars($item['box_name']) ?></strong>
                                                    <span class="small text-muted"><?= htmlspecialchars($order['bakery_name']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3">
                                            <?= getConditionGradeBadge($item['condition_grade'] ?? 'dented_box') ?>
                                            <?php if (!empty($item['expiry_date'])): ?>
                                                <div class="mt-1"><?= getExpiryCountdownBadge($item['expiry_date']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 text-center">
                                            <span class="badge bg-light text-dark border"><?= $item['quantity'] ?></span>
                                        </td>
                                        <td class="pe-3 py-3 text-end fw-bold text-dark">
                                            <?= formatVnd($item['subtotal']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="border-top">
                                <tr>
                                    <td colspan="3" class="ps-3 py-2 text-end text-muted small">Total:</td>
                                    <td class="pe-3 py-2 text-end fw-bold text-green-dark fs-5"><?= formatVnd($order['total_amount']) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Action Controls (Receipt, Cancel before pickup) -->
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <a href="<?= BASE_URL ?>/confirmation.php?code=<?= urlencode($order['reservation_code']) ?>" class="btn btn-outline-dark rounded-pill">
                        <i class="bi bi-printer me-1"></i> Printable Receipt
                    </a>

                    <?php if ($stage === 1): ?>
                        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-toggle="modal" data-bs-target="#cancelModal">
                            <i class="bi bi-x-circle me-1"></i> Cancel Reservation
                        </button>

                        <!-- Cancel Modal -->
                        <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-4 border-0">
                                    <form method="POST" action="<?= BASE_URL ?>/track.php">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="cancel_order">
                                        <input type="hidden" name="cancel_order_code" value="<?= htmlspecialchars($order['reservation_code']) ?>">
                                        <div class="modal-header border-bottom">
                                            <h5 class="modal-title fw-bold">Cancel Reservation #<?= htmlspecialchars($order['reservation_code']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="text-muted small">Cancel this reservation? Your spot will be released back to other students and payment refunded.</p>
                                            <label class="form-label small fw-bold">Reason</label>
                                            <select name="cancel_reason" class="form-select rounded-3 mb-2">
                                                <option value="Changed my mind">Changed my mind</option>
                                                <option value="Unable to visit store today">Unable to visit store today</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div class="modal-footer border-top">
                                            <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Keep</button>
                                            <button type="submit" class="btn btn-danger rounded-pill">Confirm Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Store Counter Pickup Location & Customer Info -->
            <div class="col-lg-4">
                <!-- Store Counter Voucher Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="bi bi-shop text-success me-2"></i> Partner Store Counter
                    </h5>

                    <div class="p-3 bg-light rounded-4 mb-3 border">
                        <span class="text-muted small d-block">Store Name:</span>
                        <strong class="text-dark fs-6 d-block"><?= htmlspecialchars($order['bakery_name']) ?></strong>
                        <div class="small text-muted mt-2">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                            <?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['district']) ?>
                        </div>
                        <div class="small text-muted mt-1">
                            <i class="bi bi-clock-fill text-warning me-1"></i>
                            Counter Hours: <?= formatTime($order['opening_time'] ?? '07:00:00') ?> – <?= formatTime($order['closing_time'] ?? '23:00:00') ?>
                        </div>
                        <div class="small text-muted mt-1">
                            <i class="bi bi-telephone-fill text-success me-1"></i>
                            <?= htmlspecialchars($order['bakery_phone']) ?>
                        </div>
                    </div>

                    <?php if (!empty($order['pickup_date'])): ?>
                        <div class="small text-muted mb-2">
                            <i class="bi bi-calendar-event me-1 text-success"></i> 
                            Pickup Window: <strong><?= formatDateTime($order['pickup_date'], 'D, M d, Y') ?></strong>
                        </div>
                        <div class="small text-muted mb-3">
                            <i class="bi bi-clock me-1 text-warning"></i> 
                            Time: <strong><?= formatTime($order['start_time']) ?> – <?= formatTime($order['end_time']) ?></strong>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-success d-flex align-items-center gap-2 p-2 m-0 small rounded-3">
                        <i class="bi bi-check2-circle text-success fs-5"></i>
                        <span>Zero shipping • Collect in under 60 seconds at cashier</span>
                    </div>
                </div>

                <!-- Customer Details Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-fill text-success me-2"></i> Customer Details</h5>
                    <div class="small text-muted mb-2">
                        <span>Name:</span> <strong class="text-dark ms-1"><?= htmlspecialchars($order['customer_name'] ?? 'Guest Customer') ?></strong>
                    </div>
                    <div class="small text-muted mb-2">
                        <span>Phone:</span> <strong class="text-dark ms-1"><?= htmlspecialchars($order['customer_phone'] ?? 'N/A') ?></strong>
                    </div>
                    <div class="small text-muted mb-3">
                        <span>Email:</span> <strong class="text-dark ms-1"><?= htmlspecialchars($order['customer_email'] ?? 'N/A') ?></strong>
                    </div>

                    <hr class="my-2">

                    <h6 class="fw-bold text-dark my-2">Payment Details</h6>
                    <div class="small text-muted mb-1">
                        <span>Method:</span> <strong class="text-dark ms-1"><?= htmlspecialchars($order['payment_method']) ?></strong>
                    </div>
                    <div class="small text-muted">
                        <span>Payment Status:</span> 
                        <span class="badge <?= $order['payment_status'] === 'Paid' ? 'bg-success' : ($order['payment_status'] === 'Refunded' ? 'bg-danger' : 'bg-warning text-dark') ?> ms-1">
                            <?= htmlspecialchars($order['payment_status']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
/* Visual Stepper Styles */
.order-progress-stepper {
    position: relative;
    padding: 1.5rem 0;
}
.stepper-line {
    position: absolute;
    top: 36px;
    left: 8%;
    right: 8%;
    height: 4px;
    background-color: #E2E8F0;
    z-index: 1;
}
.stepper-line-fill {
    height: 100%;
    background-color: #0F4C3A;
    transition: width 0.4s ease;
}
.stepper-steps {
    position: relative;
    display: flex;
    justify-content: space-between;
    z-index: 2;
}
.stepper-step {
    text-align: center;
    flex: 1;
}
.step-circle {
    width: 44px;
    height: 44px;
    margin: 0 auto 0.5rem;
    border-radius: 50%;
    background-color: #FFFFFF;
    border: 3px solid #CBD5E1;
    color: #94A3B8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    font-weight: bold;
    transition: all 0.3s ease;
}
.stepper-step.completed .step-circle {
    background-color: #0F4C3A;
    border-color: #0F4C3A;
    color: #FFFFFF;
}
.stepper-step.current .step-circle {
    border-color: #0F4C3A;
    color: #0F4C3A;
    box-shadow: 0 0 0 5px rgba(15, 76, 58, 0.15);
}
.step-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #64748B;
    margin-bottom: 2px;
}
.stepper-step.completed .step-title,
.stepper-step.current .step-title {
    color: #0F4C3A;
}
.step-sub {
    font-size: 0.72rem;
    color: #94A3B8;
}
.pulse-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    background-color: #0F4C3A;
    border-radius: 50%;
    animation: pulseDot 1.5s infinite;
}
@keyframes pulseDot {
    0% { transform: scale(0.9); opacity: 0.8; }
    50% { transform: scale(1.3); opacity: 1; }
    100% { transform: scale(0.9); opacity: 0.8; }
}
@media (max-width: 576px) {
    .step-circle { width: 34px; height: 34px; font-size: 0.9rem; }
    .step-title { font-size: 0.72rem; }
    .step-sub { display: none; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
