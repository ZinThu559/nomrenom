<?php
/**
 * NomReNom - Commercial Snack Marketplace Checkout
 * 100% Store Pickup Marketplace (Zero Shipping)
 * Primary Payment: VietQR Instant Transfer
 * Guaranteed Counter Inspection & Instant Refund
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/mailer.php';

$pdo = getDbConnection();
$loggedInUser = currentUser();

// Direct Buy Now support: if ?box_id is provided, ensure it's in the cart
if (isset($_GET['box_id']) && (int)$_GET['box_id'] > 0) {
    $directBoxId = (int)$_GET['box_id'];
    $directQty = max(1, (int)($_GET['qty'] ?? 1));
    addToCart($directBoxId, $directQty);
}

$cartItems = getCart();
$cartCount = getCartCount();
$cartTotal = getCartTotal();

// If cart is empty, redirect to menu
if (empty($cartItems)) {
    setFlash('info', 'Your snack cart is empty. Please select snack items to place a reservation.');
    header('Location: ' . BASE_URL . '/boxes.php');
    exit;
}

// Fetch available partner stores
$bakeriesStmt = $pdo->query("SELECT * FROM bakeries WHERE is_active = 1");
$bakeries = $bakeriesStmt->fetchAll();

// Determine primary partner store for the cart
$firstItem = reset($cartItems);
$primaryBakeryId = (int)($firstItem['bakery_id'] ?? 1);

// Fetch store details
$storeStmt = $pdo->prepare("SELECT * FROM bakeries WHERE id = ? LIMIT 1");
$storeStmt->execute([$primaryBakeryId]);
$primaryStore = $storeStmt->fetch() ?: $bakeries[0];

// Fetch pickup slots for this store
$slotsStmt = $pdo->prepare("SELECT * FROM pickup_slots WHERE bakery_id = ? AND is_active = 1 AND pickup_date >= CURDATE() ORDER BY pickup_date ASC, start_time ASC LIMIT 8");
$slotsStmt->execute([$primaryBakeryId]);
$pickupSlots = $slotsStmt->fetchAll();

// If no pickup slots in database, auto-seed an active slot in DB to guarantee foreign key integrity
if (empty($pickupSlots)) {
    $anySlotStmt = $pdo->prepare("SELECT * FROM pickup_slots WHERE bakery_id = ? ORDER BY id DESC LIMIT 1");
    $anySlotStmt->execute([$primaryBakeryId]);
    $existingSlot = $anySlotStmt->fetch();
    if ($existingSlot) {
        $pickupSlots = [$existingSlot];
    } else {
        $insSlot = $pdo->prepare("INSERT INTO pickup_slots (bakery_id, pickup_date, start_time, end_time, max_capacity, is_active) VALUES (?, CURDATE(), '17:00:00', '21:00:00', 50, 1)");
        $insSlot->execute([$primaryBakeryId]);
        $newSlotId = (int)$pdo->lastInsertId();
        $pickupSlots = [
            [
                'id' => $newSlotId,
                'bakery_id' => $primaryBakeryId,
                'pickup_date' => date('Y-m-d'),
                'start_time' => '17:00:00',
                'end_time' => '21:00:00',
                'max_capacity' => 50,
                'reserved_count' => 0,
                'is_active' => 1
            ]
        ];
    }
}

// Handle Order Placement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    // 100% Store Pickup - No delivery shipping
    $fulfillmentType = 'pickup';
    $deliveryAddress = null;
    $slotId = (int)($_POST['slot_id'] ?? $pickupSlots[0]['id']);
    $paymentMethod = clean($_POST['payment_method'] ?? 'VietQR'); 
    $specialInstructions = clean($_POST['special_instructions'] ?? '');

    // Contact info handling
    if ($loggedInUser) {
        $fullName = clean($_POST['full_name'] ?? $loggedInUser['full_name']);
        $email = clean($_POST['email'] ?? $loggedInUser['email']);
        $phone = clean($_POST['phone'] ?? $loggedInUser['phone']);
        $userId = $loggedInUser['id'];
        $guestId = null;
    } else {
        $fullName = clean($_POST['guest_name'] ?? '');
        $email = clean($_POST['guest_email'] ?? '');
        $phone = clean($_POST['guest_phone'] ?? '');
        $userId = null;
    }

    // Validation
    $errors = [];
    if (empty($fullName) || mb_strlen($fullName) < 3) {
        $errors[] = 'Please enter your full name (at least 3 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address to receive your 6-digit pickup code.';
    }
    if (empty($phone) || !preg_match('/^[0-9+() -]{8,20}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number for SMS pickup notifications.';
    }

    if (!empty($errors)) {
        setFlash('danger', implode('<br>', $errors));
    } else {
        // --- ATOMIC DATABASE TRANSACTION ---
        try {
            $pdo->beginTransaction();

            // 1. Verify stock for each cart item
            foreach ($cartItems as $cId => $cItem) {
                $chkStmt = $pdo->prepare("SELECT id, name, available_qty, is_active FROM food_boxes WHERE id = ? FOR UPDATE");
                $chkStmt->execute([$cId]);
                $boxDb = $chkStmt->fetch();
                if (!$boxDb || !$boxDb['is_active']) {
                    throw new Exception("Snack item '{$cItem['name']}' is currently not available.");
                }
                if ($boxDb['available_qty'] < $cItem['qty']) {
                    throw new Exception("Sorry, only {$boxDb['available_qty']} units of '{$cItem['name']}' are left at this store.");
                }
            }

            // 2. Handle Guest Record if guest checkout
            if ($userId === null) {
                $guestStmt = $pdo->prepare("INSERT INTO guest_customers (full_name, email, phone) VALUES (?, ?, ?)");
                $guestStmt->execute([$fullName, $email, $phone]);
                $guestId = (int)$pdo->lastInsertId();
            }

            // 3. Generate human-readable unique order/pickup code
            $orderCode = generateReservationCode();

            // Initial statuses based on payment method
            $isPaidOnline = in_array($paymentMethod, ['VietQR', 'MoMo', 'VNPay', 'Bank Transfer']);
            $orderStatus = 'Pending';
            $paymentStatus = $isPaidOnline ? 'Paid' : 'Pending';

            // 4. Insert into reservations table
            $resStmt = $pdo->prepare("INSERT INTO reservations 
                (reservation_code, user_id, guest_id, customer_name, customer_email, customer_phone, 
                 bakery_id, pickup_slot_id, fulfillment_type, delivery_address, total_amount, 
                 reservation_status, payment_method, payment_status, special_instructions, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            
            $resStmt->execute([
                $orderCode,
                $userId,
                $guestId,
                $fullName,
                $email,
                $phone,
                $primaryBakeryId,
                $slotId,
                $fulfillmentType,
                null,
                $cartTotal,
                $orderStatus,
                $paymentMethod,
                $paymentStatus,
                $specialInstructions
            ]);
            $reservationId = (int)$pdo->lastInsertId();

            // 5. Insert line items & deduct stock
            $itemStmt = $pdo->prepare("INSERT INTO reservation_items 
                (reservation_id, food_box_id, box_name, unit_price, quantity, subtotal) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $deductStmt = $pdo->prepare("UPDATE food_boxes SET available_qty = available_qty - ? WHERE id = ?");

            $itemsData = [];
            foreach ($cartItems as $cId => $cItem) {
                $subtotal = $cItem['price'] * $cItem['qty'];
                $itemStmt->execute([
                    $reservationId,
                    $cId,
                    $cItem['name'],
                    $cItem['price'],
                    $cItem['qty'],
                    $subtotal
                ]);
                $deductStmt->execute([$cItem['qty'], $cId]);

                $itemsData[] = [
                    'box_name' => $cItem['name'],
                    'quantity' => $cItem['qty'],
                    'subtotal' => $subtotal
                ];
            }

            // 6. Record payment transaction
            $payStmt = $pdo->prepare("INSERT INTO payments 
                (reservation_id, payment_method, gateway_provider, transaction_reference, amount, payment_status, payment_date, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
            
            $gatewayProvider = match($paymentMethod) {
                'VietQR' => 'vietqr',
                'MoMo' => 'momo',
                'VNPay' => 'vnpay',
                'Bank Transfer' => 'vietqr',
                default => 'pickup'
            };
            $txRef = $isPaidOnline ? 'QR-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)) : 'COD-' . $orderCode;
            
            $payStmt->execute([
                $reservationId,
                $paymentMethod,
                $gatewayProvider,
                $txRef,
                $cartTotal,
                $paymentStatus
            ]);

            // 7. Commit database changes atomically
            $pdo->commit();

            // 8. Clear the shopping cart
            clearCart();

            // Fetch store & slot details for confirmation email
            $bakery = $pdo->query("SELECT * FROM bakeries WHERE id = $primaryBakeryId")->fetch();
            $slot = $pdo->query("SELECT * FROM pickup_slots WHERE id = $slotId")->fetch() ?: $pickupSlots[0];

            $orderData = [
                'reservation_code' => $orderCode,
                'total_amount' => $cartTotal,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus
            ];
            @sendReservationConfirmationEmail($orderData, $itemsData, $bakery, $slot, $email, $fullName);

            setFlash('success', "Snack reservation confirmed! Your pickup code is <strong>{$orderCode}</strong>. Please inspect your item at the store counter.");
            header("Location: " . BASE_URL . "/track.php?code=" . urlencode($orderCode));
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            setFlash('danger', 'Error placing reservation: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Checkout & Reserve Snacks – NomReNom';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb & Header -->
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/cart.php" class="text-decoration-none text-muted">Snack Cart</a></li>
                <li class="breadcrumb-item active text-dark" aria-current="page">Reserve & Checkout</li>
            </ol>
        </nav>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h3 fw-bold text-dark m-0 d-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock-fill text-success"></i> Reserve Snacks & Pay by VietQR
                </h1>
                <p class="text-muted small mb-0 mt-1">100% Store Counter Pickup • Inspect before taking home • Money-back guarantee</p>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill">
                <i class="bi bi-shop me-1"></i> Store Pickup Only • No Shipping
            </span>
        </div>
    </div>

    <!-- Process Trust Banner -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white border-start border-4 border-success">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                <i class="bi bi-patch-check-fill fs-4"></i>
            </div>
            <div>
                <strong class="d-block text-dark">Zero Risk Counter Inspection Guarantee</strong>
                <span class="small text-muted">
                    Pay securely by VietQR and receive your 6-digit pickup code. When you collect at the store counter, inspect your snack against the photo. If it doesn't match, refuse it on your phone for an immediate full refund!
                </span>
            </div>
        </div>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/checkout.php" id="checkoutForm">
        <?= csrfField() ?>

        <div class="row g-4">
            <!-- Left Column: Customer Details, Pickup Location, Payment -->
            <div class="col-lg-8">
                <!-- 1. Customer Information Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                            <span class="step-badge">1</span> Your Contact Information
                        </h5>
                        <?php if ($loggedInUser): ?>
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-person-check-fill text-success me-1"></i> Signed In as <?= htmlspecialchars($loggedInUser['full_name']) ?>
                            </span>
                        <?php else: ?>
                            <span class="small text-muted">
                                Have an account? <a href="<?= BASE_URL ?>/login.php" class="text-success fw-bold text-decoration-none">Sign In</a>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($loggedInUser): ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control rounded-3" value="<?= htmlspecialchars($loggedInUser['full_name']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control rounded-3" value="<?= htmlspecialchars($loggedInUser['email']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control rounded-3" value="<?= htmlspecialchars($loggedInUser['phone'] ?? '') ?>" placeholder="0901234567" required>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <span class="badge bg-success mb-1">Instant Guest Reservation</span>
                            <p class="small text-muted mb-0">No registration required! Enter your phone and email to receive your pickup voucher.</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Your Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="guest_name" class="form-control rounded-3" placeholder="e.g. Alex Nguyen" value="<?= htmlspecialchars($_POST['guest_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="guest_email" class="form-control rounded-3" placeholder="alex@gmail.com" value="<?= htmlspecialchars($_POST['guest_email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="guest_phone" class="form-control rounded-3" placeholder="0901234567" value="<?= htmlspecialchars($_POST['guest_phone'] ?? '') ?>" required>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 2. Store Counter Pickup Selection Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <span class="step-badge">2</span> Partner Store Pickup Location
                    </h5>

                    <!-- Store Highlight Card -->
                    <div class="p-3 bg-light rounded-4 border mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 46px; height: 46px;">
                                <i class="bi bi-shop fs-4"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-green-dark mb-1"><?= htmlspecialchars($primaryStore['name']) ?></h6>
                                <div class="small text-muted">
                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                    <?= htmlspecialchars($primaryStore['address']) ?>, <?= htmlspecialchars($primaryStore['district']) ?>
                                </div>
                                <div class="small text-muted mt-1">
                                    <i class="bi bi-clock-fill text-warning me-1"></i> Counter Hours: <?= formatTime($primaryStore['opening_time']) ?> – <?= formatTime($primaryStore['closing_time']) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Select Your Collection Window <span class="text-danger">*</span></label>
                            <select name="slot_id" class="form-select rounded-3">
                                <?php foreach ($pickupSlots as $slot): ?>
                                    <option value="<?= $slot['id'] ?>">
                                        <?= formatDateTime($slot['pickup_date'], 'D, M d') ?> (<?= formatTime($slot['start_time']) ?> – <?= formatTime($slot['end_time']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted mt-1 d-block"><i class="bi bi-info-circle me-1"></i> Show your 6-digit pickup code at the cashier counter during this window.</small>
                        </div>
                    </div>
                </div>

                <!-- 3. Payment Method Selection Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <span class="step-badge">3</span> Select Payment Method
                    </h5>

                    <div class="row g-3">
                        <!-- VietQR (Recommended) -->
                        <div class="col-12">
                            <label class="payment-card d-block p-3 rounded-4 border cursor-pointer active" id="label-vietqr">
                                <div class="form-check d-flex align-items-center gap-3">
                                    <input class="form-check-input fs-5" type="radio" name="payment_method" value="VietQR" id="pay_vietqr" checked>
                                    <div class="form-check-label w-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-green-dark fs-6">
                                                <i class="bi bi-qr-code-scan text-success me-1"></i> VietQR Instant Transfer (Recommended)
                                            </strong>
                                            <span class="badge bg-success">Zero Fee • Instant</span>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            Scan QR using any Vietnamese banking app (MBBank, Vietcombank, Vietinbank, Techcombank, MoMo).
                                        </small>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- MoMo -->
                        <div class="col-sm-6">
                            <label class="payment-card d-block p-3 rounded-3 border cursor-pointer" id="label-momo">
                                <div class="form-check d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="radio" name="payment_method" value="MoMo" id="pay_momo">
                                    <div class="form-check-label w-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-dark">MoMo E-Wallet</strong>
                                            <span class="badge bg-danger">App Scan</span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Pay with MoMo app or scan QR</small>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Cash on Store Pickup -->
                        <div class="col-sm-6">
                            <label class="payment-card d-block p-3 rounded-3 border cursor-pointer" id="label-cash">
                                <div class="form-check d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="radio" name="payment_method" value="Cash on Pickup" id="pay_cash">
                                    <div class="form-check-label w-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-dark">Pay at Store Counter</strong>
                                            <span class="badge bg-secondary">Cash / Card</span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Pay at cashier counter after inspection</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- VietQR Information Box -->
                    <div id="vietqr-info-box" class="mt-3 p-3 bg-light rounded-4 border">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-qr-code text-success fs-5"></i>
                            <h6 class="fw-bold text-dark m-0">VietQR Transfer Preview</h6>
                        </div>
                        <div class="row g-2 small text-muted">
                            <div class="col-6">Bank: <strong>Military Bank (MB Bank)</strong></div>
                            <div class="col-6">Account: <strong>8888-NOMRENOM-VN</strong></div>
                            <div class="col-6">Beneficiary: <strong>NOMRENOM SNACK MARKETPLACE</strong></div>
                            <div class="col-6">Transfer Memo: <strong>[Generated on next step]</strong></div>
                        </div>
                        <div class="mt-2 pt-2 border-top small text-success">
                            <i class="bi bi-check-circle-fill me-1"></i> QR code will be dynamically generated with exact amount upon clicking "Reserve & Get Pickup Code".
                        </div>
                    </div>
                </div>

                <!-- 4. Special Instructions -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square text-muted"></i> Pickup Notes (Optional)
                    </h5>
                    <textarea name="special_instructions" class="form-control rounded-3" rows="2" placeholder="e.g. Picking up during afternoon break, please hold until 5:30 PM."></textarea>
                </div>
            </div>

            <!-- Right Column: Order Summary & Reserve CTA -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 90px;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark m-0">Reserved Snacks</h5>
                        <a href="<?= BASE_URL ?>/cart.php" class="small text-success fw-bold text-decoration-none">Edit</a>
                    </div>

                    <!-- Items List -->
                    <div class="cart-summary-items mb-3" style="max-height: 280px; overflow-y: auto;">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div class="d-flex align-items-center gap-2 pe-2">
                                    <span class="badge bg-light text-dark border"><?= $item['qty'] ?>x</span>
                                    <div>
                                        <div class="small fw-bold text-dark text-truncate" style="max-width: 170px;">
                                            <?= htmlspecialchars($item['name']) ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            <?= formatVnd($item['price']) ?> each
                                        </div>
                                    </div>
                                </div>
                                <span class="small fw-bold text-dark">
                                    <?= formatVnd($item['price'] * $item['qty']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Breakdown -->
                    <div class="d-flex justify-content-between text-muted small mb-2">
                        <span>Items Subtotal (<?= $cartCount ?> items)</span>
                        <span class="fw-semibold text-dark"><?= formatVnd($cartTotal) ?></span>
                    </div>

                    <div class="d-flex justify-content-between text-muted small mb-2">
                        <span>Store Pickup Fee</span>
                        <span class="text-success fw-semibold">FREE (0 ₫)</span>
                    </div>

                    <div class="d-flex justify-content-between text-muted small mb-3">
                        <span>Shipping</span>
                        <span class="text-success fw-semibold">Zero (Counter Pickup)</span>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fw-bold text-dark fs-5">Total To Pay</span>
                        <span class="fw-bold text-green-dark fs-3"><?= formatVnd($cartTotal) ?></span>
                    </div>

                    <button type="submit" class="btn btn-nrn-primary w-100 py-3 fs-5 fw-bold d-flex justify-content-center align-items-center gap-2 rounded-pill shadow">
                        <i class="bi bi-qr-code-scan"></i> Reserve & Get Pickup Code
                    </button>

                    <div class="text-center mt-3 small text-muted">
                        <i class="bi bi-shield-check text-success"></i> 100% Refundable if item doesn't match photo
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Interactive Payment Selection Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const payRadios = document.querySelectorAll('input[name="payment_method"]');
    const vietqrBox = document.getElementById('vietqr-info-box');

    payRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.payment-card').forEach(c => c.classList.remove('active'));
            this.closest('.payment-card').classList.add('active');

            if (this.value === 'VietQR') {
                vietqrBox.style.display = 'block';
            } else {
                vietqrBox.style.display = 'none';
            }
        });
    });
});
</script>

<style>
.step-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background-color: #0F4C3A;
    color: #FFFFFF;
    font-size: 0.85rem;
    font-weight: 700;
    border-radius: 50%;
}
.payment-card {
    transition: all 0.2s ease;
    border: 1px solid #E2E8F0 !important;
}
.payment-card:hover {
    border-color: #0F4C3A !important;
}
.payment-card.active {
    border-color: #0F4C3A !important;
    background-color: #F0FDF4;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
