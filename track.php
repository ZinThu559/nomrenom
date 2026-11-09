<?php
/**
 * NomReNom - Commercial Live Order Tracking System
 * GrabFood / Foodpanda style visual order progress stepper:
 * Pending -> Confirmed -> Preparing -> Ready for Pickup / Out for Delivery -> Completed
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

// Handle cancellation request if still pending
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

        setFlash('warning', "Order #{$cancelCode} has been cancelled.");
        header("Location: " . BASE_URL . "/track.php?code=" . urlencode($cancelCode));
        exit;
    } else {
        $error = 'This order cannot be cancelled because it is already preparing or completed.';
    }
}

// Lookup order by code or phone
if (!empty($orderCode)) {
    $stmt = $pdo->prepare("SELECT r.*, b.name AS bakery_name, b.address, b.district, b.city, b.phone AS bakery_phone,
                                  s.pickup_date, s.start_time, s.end_time 
                           FROM reservations r 
                           JOIN bakeries b ON r.bakery_id = b.id 
                           LEFT JOIN pickup_slots s ON r.pickup_slot_id = s.id 
                           WHERE r.reservation_code = ? LIMIT 1");
    $stmt->execute([$orderCode]);
    $order = $stmt->fetch();
} elseif (!empty($searchQuery)) {
    // Search by code OR phone number
    $stmt = $pdo->prepare("SELECT r.*, b.name AS bakery_name, b.address, b.district, b.city, b.phone AS bakery_phone,
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
        $error = "No active order found for reference or phone number: '{$searchQuery}'.";
    }
} elseif ($loggedInUser) {
    // If logged in and no code specified, fetch customer's latest order
    $stmt = $pdo->prepare("SELECT r.*, b.name AS bakery_name, b.address, b.district, b.city, b.phone AS bakery_phone,
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

// Fetch line items and payment record if order found
$orderItems = [];
$payment = null;
if ($order) {
    $itStmt = $pdo->prepare("SELECT * FROM reservation_items WHERE reservation_id = ?");
    $itStmt->execute([$order['id']]);
    $orderItems = $itStmt->fetchAll();

    $payStmt = $pdo->prepare("SELECT * FROM payments WHERE reservation_id = ? ORDER BY id DESC LIMIT 1");
    $payStmt->execute([$order['id']]);
    $payment = $payStmt->fetch();
}

$pageTitle = $order ? 'Track Order #' . $order['reservation_code'] : 'Track Your Order – NomReNom';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
                <li class="breadcrumb-item active text-dark" aria-current="page">Live Order Tracking</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="bi bi-geo-alt-fill text-success"></i> Live Order Tracking
        </h1>
    </div>

    <!-- Order Search Bar -->
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
        <form method="POST" action="<?= BASE_URL ?>/track.php" class="row g-2 align-items-center">
            <?= csrfField() ?>
            <div class="col-12 col-md-8">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search_query" class="form-control border-0 bg-light" 
                           placeholder="Enter your Order Number (e.g. NRN-20261105-XXXX) or Phone Number..." 
                           value="<?= htmlspecialchars($orderCode ?: $searchQuery) ?>" required>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <button type="submit" class="btn btn-nrn-primary w-100 py-2">
                    <i class="bi bi-radar"></i> Track Order Status
                </button>
            </div>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center rounded-3 mb-4 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($order): 
        // Map order status to stage index (1..5)
        $statusStr = strtolower(trim($order['reservation_status']));
        $stage = match($statusStr) {
            'pending' => 1,
            'confirmed' => 2,
            'preparing' => 3,
            'ready for pickup', 'ready', 'out for delivery' => 4,
            'completed', 'collected' => 5,
            'cancelled' => -1,
            default => 2
        };

        $isDelivery = ($order['fulfillment_type'] === 'delivery');
        $step4Label = $isDelivery ? 'Out for Delivery' : 'Ready for Pickup';
        $step4Desc = $isDelivery ? 'Rider heading to your address' : 'Ready at bakery counter';
    ?>
        <div class="row g-4">
            <!-- Left: Tracking Progress & Order Summary -->
            <div class="col-lg-8">
                <!-- Status Timeline Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 pb-3 border-bottom">
                        <div>
                            <span class="text-muted small text-uppercase fw-bold letter-spacing-1">Order Reference</span>
                            <h3 class="fw-bold text-dark m-0 user-select-all"><?= htmlspecialchars($order['reservation_code']) ?></h3>
                            <div class="text-muted small mt-1">Placed on <?= formatDateTime($order['created_at'], 'M d, Y \a\t H:i') ?></div>
                        </div>
                        <div class="text-end">
                            <?php if ($stage === -1): ?>
                                <span class="badge bg-danger fs-6 px-3 py-2 rounded-pill">Cancelled</span>
                            <?php elseif ($stage === 5): ?>
                                <span class="badge bg-success fs-6 px-3 py-2 rounded-pill"><i class="bi bi-check2-circle me-1"></i> Order Completed</span>
                            <?php else: ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success fs-6 px-3 py-2 rounded-pill">
                                    <span class="pulse-dot me-1"></span> <?= htmlspecialchars(ucwords($order['reservation_status'])) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($stage === -1): ?>
                        <div class="alert alert-danger rounded-3 p-3 mb-0">
                            <h6 class="fw-bold mb-1"><i class="bi bi-x-circle-fill me-1"></i> This order was cancelled</h6>
                            <p class="small mb-0">Reason: <?= htmlspecialchars($order['cancellation_reason'] ?? 'Cancelled by customer') ?></p>
                        </div>
                    <?php else: ?>
                        <!-- 5-Stage Live Visual Progress Stepper -->
                        <div class="order-progress-stepper my-3">
                            <div class="stepper-line">
                                <div class="stepper-line-fill" style="width: <?= min(100, max(0, ($stage - 1) * 25)) ?>%;"></div>
                            </div>
                            <div class="stepper-steps">
                                <!-- Step 1 -->
                                <div class="stepper-step <?= $stage >= 1 ? 'completed' : '' ?> <?= $stage === 1 ? 'current' : '' ?>">
                                    <div class="step-circle">
                                        <i class="bi <?= $stage > 1 ? 'bi-check-lg' : 'bi-receipt' ?>"></i>
                                    </div>
                                    <div class="step-title">Order Placed</div>
                                    <div class="step-sub">Pending</div>
                                </div>

                                <!-- Step 2 -->
                                <div class="stepper-step <?= $stage >= 2 ? 'completed' : '' ?> <?= $stage === 2 ? 'current' : '' ?>">
                                    <div class="step-circle">
                                        <i class="bi <?= $stage > 2 ? 'bi-check-lg' : 'bi-check2-circle' ?>"></i>
                                    </div>
                                    <div class="step-title">Confirmed</div>
                                    <div class="step-sub">Accepted</div>
                                </div>

                                <!-- Step 3 -->
                                <div class="stepper-step <?= $stage >= 3 ? 'completed' : '' ?> <?= $stage === 3 ? 'current' : '' ?>">
                                    <div class="step-circle">
                                        <i class="bi <?= $stage > 3 ? 'bi-check-lg' : 'bi-fire' ?>"></i>
                                    </div>
                                    <div class="step-title">Preparing</div>
                                    <div class="step-sub">Fresh bakes</div>
                                </div>

                                <!-- Step 4 -->
                                <div class="stepper-step <?= $stage >= 4 ? 'completed' : '' ?> <?= $stage === 4 ? 'current' : '' ?>">
                                    <div class="step-circle">
                                        <i class="bi <?= $stage > 4 ? 'bi-check-lg' : ($isDelivery ? 'bi-bicycle' : 'bi-bag-check') ?>"></i>
                                    </div>
                                    <div class="step-title"><?= $step4Label ?></div>
                                    <div class="step-sub"><?= $step4Desc ?></div>
                                </div>

                                <!-- Step 5 -->
                                <div class="stepper-step <?= $stage >= 5 ? 'completed' : '' ?>">
                                    <div class="step-circle">
                                        <i class="bi bi-star-fill"></i>
                                    </div>
                                    <div class="step-title">Completed</div>
                                    <div class="step-sub">Enjoy meal</div>
                                </div>
                            </div>
                        </div>

                        <!-- Real-time Status Guidance Box -->
                        <div class="p-3 bg-light rounded-3 border mt-4">
                            <?php if ($stage === 1): ?>
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-hourglass-split fs-2 text-warning"></i>
                                    <div>
                                        <strong class="d-block text-dark">We have received your order!</strong>
                                        <span class="small text-muted">The bakery is reviewing your items and will begin preparation shortly.</span>
                                    </div>
                                </div>
                            <?php elseif ($stage === 2): ?>
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-check-circle-fill fs-2 text-success"></i>
                                    <div>
                                        <strong class="d-block text-dark">Order Confirmed by Bakery!</strong>
                                        <span class="small text-muted">Your order is locked in. The kitchen team is getting the eco-friendly boxes ready.</span>
                                    </div>
                                </div>
                            <?php elseif ($stage === 3): ?>
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-fire fs-2 text-warning"></i>
                                    <div>
                                        <strong class="d-block text-dark">Kitchen is Packing Your Fresh Bakes!</strong>
                                        <span class="small text-muted">Pastries, croissants, and artisan breads are being sorted and carefully packaged.</span>
                                    </div>
                                </div>
                            <?php elseif ($stage === 4): ?>
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-bell-fill fs-2 text-success"></i>
                                    <div>
                                        <strong class="d-block text-dark">
                                            <?= $isDelivery ? 'Rider is Out for Delivery!' : 'Your Food is Ready for Pickup!' ?>
                                        </strong>
                                        <span class="small text-muted">
                                            <?= $isDelivery ? 'Our courier will call your phone upon arriving at your building lobby.' : 'Show your Order Code at the bakery counter for instant 60-second collection.' ?>
                                        </span>
                                    </div>
                                </div>
                            <?php elseif ($stage === 5): ?>
                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="bi bi-emoji-smile-fill fs-2 text-success"></i>
                                        <div>
                                            <strong class="d-block text-dark">Order Completed. Bon Appétit!</strong>
                                            <span class="small text-muted">Thank you for ordering with NomReNom and preventing food waste in your community!</span>
                                        </div>
                                    </div>
                                    <a href="<?= BASE_URL ?>/feedback.php?code=<?= urlencode($order['reservation_code']) ?>" class="btn btn-warning text-dark fw-bold btn-sm text-nowrap rounded-pill px-3 shadow-sm">
                                        <i class="bi bi-star-fill me-1"></i> Leave a Review
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Items Ordered Details Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark mb-3">Items in This Order</h5>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-3 py-2">Item</th>
                                    <th class="py-2 text-center">Qty</th>
                                    <th class="py-2 text-end">Price</th>
                                    <th class="pe-3 py-2 text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td class="ps-3 py-3">
                                            <strong class="text-dark d-block"><?= htmlspecialchars($item['box_name']) ?></strong>
                                            <span class="small text-muted"><?= htmlspecialchars($order['bakery_name']) ?></span>
                                        </td>
                                        <td class="py-3 text-center">
                                            <span class="badge bg-light text-dark border"><?= $item['quantity'] ?></span>
                                        </td>
                                        <td class="py-3 text-end text-muted small">
                                            <?= formatVnd($item['unit_price']) ?>
                                        </td>
                                        <td class="pe-3 py-3 text-end fw-bold text-dark">
                                            <?= formatVnd($item['subtotal']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="border-top">
                                <tr>
                                    <td colspan="3" class="ps-3 py-2 text-end text-muted small">Total Paid:</td>
                                    <td class="pe-3 py-2 text-end fw-bold text-success fs-5"><?= formatVnd($order['total_amount']) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Action Controls (Print Receipt, Cancel Order) -->
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <a href="<?= BASE_URL ?>/confirmation.php?code=<?= urlencode($order['reservation_code']) ?>" class="btn btn-outline-dark">
                        <i class="bi bi-printer me-1"></i> View Full Printable Receipt
                    </a>

                    <?php if ($stage === 1 || $stage === 2): ?>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                            <i class="bi bi-x-circle me-1"></i> Cancel Order
                        </button>

                        <!-- Cancel Modal -->
                        <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content rounded-4 border-0">
                                    <form method="POST" action="<?= BASE_URL ?>/track.php">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="cancel_order">
                                        <input type="hidden" name="cancel_order_code" value="<?= htmlspecialchars($order['reservation_code']) ?>">
                                        <div class="modal-header border-bottom">
                                            <h5 class="modal-title fw-bold text-danger">Cancel Order #<?= htmlspecialchars($order['reservation_code']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="text-muted small">Are you sure you want to cancel this order? If you paid online, a full refund will be processed to your account.</p>
                                            <label class="form-label small fw-bold">Reason for Cancellation</label>
                                            <select name="cancel_reason" class="form-select mb-2">
                                                <option value="Changed my mind">Changed my mind</option>
                                                <option value="Ordered by mistake">Ordered by mistake</option>
                                                <option value="Need to change pickup time">Need to change pickup time</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div class="modal-footer border-top">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Order</button>
                                            <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Fulfillment Location & Customer Info -->
            <div class="col-lg-4">
                <!-- Fulfillment Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="bi <?= $isDelivery ? 'bi-bicycle text-primary' : 'bi-shop text-success' ?> me-2"></i>
                        <?= $isDelivery ? 'Delivery Information' : 'Store Pickup Location' ?>
                    </h5>

                    <?php if ($isDelivery): ?>
                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <span class="text-muted small d-block">Delivery Destination:</span>
                            <strong class="text-dark fs-6"><?= htmlspecialchars($order['delivery_address'] ?? 'Dormitory Campus') ?></strong>
                        </div>
                        <div class="small text-muted mb-2">
                            <i class="bi bi-clock me-1 text-warning"></i> Estimated Arrival: <strong>Within 30–45 mins</strong>
                        </div>
                    <?php else: ?>
                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <span class="text-muted small d-block">Partner Bakery:</span>
                            <strong class="text-dark fs-6 d-block"><?= htmlspecialchars($order['bakery_name']) ?></strong>
                            <div class="small text-muted mt-1">
                                <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                <?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['district']) ?>
                            </div>
                            <div class="small text-muted mt-1">
                                <i class="bi bi-telephone-fill text-success me-1"></i>
                                <?= htmlspecialchars($order['bakery_phone']) ?>
                            </div>
                        </div>
                        <?php if (!empty($order['pickup_date'])): ?>
                            <div class="small text-muted mb-2">
                                <i class="bi bi-calendar-event me-1 text-success"></i> 
                                Date: <strong><?= formatDateTime($order['pickup_date'], 'D, M d, Y') ?></strong>
                            </div>
                            <div class="small text-muted mb-2">
                                <i class="bi bi-clock me-1 text-warning"></i> 
                                Time Window: <strong><?= formatTime($order['start_time']) ?> – <?= formatTime($order['end_time']) ?></strong>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
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
                        <span class="badge <?= $order['payment_status'] === 'Paid' ? 'bg-success' : 'bg-warning text-dark' ?> ms-1">
                            <?= htmlspecialchars($order['payment_status']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
/* Visual Stepper Styles (GrabFood / Foodpanda style) */
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
