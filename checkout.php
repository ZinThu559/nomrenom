<?php
/**
 * NomReNom - Commercial Food Ordering Checkout
 * Supports Registered Customer & Guest Checkout
 * Multiple Payment Methods (MoMo, VNPay, Bank Transfer, Cash on Pickup/Delivery)
 * Fulfillment Options: Store Pickup or Local Campus Delivery
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
    setFlash('info', 'Your food cart is empty. Please select food items to place an order.');
    header('Location: ' . BASE_URL . '/boxes.php');
    exit;
}

// Fetch available bakeries and pickup slots
$bakeriesStmt = $pdo->query("SELECT * FROM bakeries WHERE is_active = 1");
$bakeries = $bakeriesStmt->fetchAll();

// Determine primary bakery for the cart
$firstItem = reset($cartItems);
$primaryBakeryId = (int)($firstItem['bakery_id'] ?? 1);

// Fetch pickup slots for this bakery
$slotsStmt = $pdo->prepare("SELECT * FROM pickup_slots WHERE bakery_id = ? AND is_active = 1 AND pickup_date >= CURDATE() ORDER BY pickup_date ASC, start_time ASC LIMIT 8");
$slotsStmt->execute([$primaryBakeryId]);
$pickupSlots = $slotsStmt->fetchAll();

// If no pickup slots in database, generate a virtual slot for today
if (empty($pickupSlots)) {
    $pickupSlots = [
        [
            'id' => 1,
            'bakery_id' => $primaryBakeryId,
            'pickup_date' => date('Y-m-d'),
            'start_time' => '17:00:00',
            'end_time' => '19:00:00',
            'max_capacity' => 50,
            'reserved_count' => 0
        ]
    ];
}

// Handle Order Placement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $fulfillmentType = clean($_POST['fulfillment_type'] ?? 'pickup'); // 'pickup' or 'delivery'
    $deliveryAddress = clean($_POST['delivery_address'] ?? '');
    $slotId = (int)($_POST['slot_id'] ?? $pickupSlots[0]['id']);
    $paymentMethod = clean($_POST['payment_method'] ?? 'Cash on Pickup'); 
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
        $errors[] = 'Please enter your real full name (at least 3 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address for your order confirmation.';
    }
    if (empty($phone) || !preg_match('/^[0-9+() -]{8,20}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number so we can reach you when your food is ready.';
    }
    if ($fulfillmentType === 'delivery' && (empty($deliveryAddress) || mb_strlen($deliveryAddress) < 5)) {
        $errors[] = 'Please specify your complete delivery address (Building/Dorm, Room number, Street address).';
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
                    throw new Exception("Item '{$cItem['name']}' is currently not available.");
                }
                if ($boxDb['available_qty'] < $cItem['qty']) {
                    throw new Exception("Sorry, only {$boxDb['available_qty']} units of '{$cItem['name']}' are in stock today.");
                }
            }

            // 2. Handle Guest Record if guest checkout
            if ($userId === null) {
                $guestStmt = $pdo->prepare("INSERT INTO guest_customers (full_name, email, phone) VALUES (?, ?, ?)");
                $guestStmt->execute([$fullName, $email, $phone]);
                $guestId = (int)$pdo->lastInsertId();
            }

            // 3. Generate human-readable unique order code
            $orderCode = generateReservationCode();

            // Initial statuses based on payment method
            $isPaidOnline = in_array($paymentMethod, ['MoMo', 'VNPay', 'Bank Transfer']);
            $orderStatus = 'Pending';
            $paymentStatus = $isPaidOnline ? 'Paid' : 'Pending';

            // 4. Insert into reservations / orders table
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
                $fulfillmentType === 'delivery' ? $deliveryAddress : null,
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
                'MoMo' => 'momo',
                'VNPay' => 'vnpay',
                'Bank Transfer' => 'bank_transfer',
                default => 'pickup'
            };
            $txRef = $isPaidOnline ? 'PAY-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)) : 'COD-' . $orderCode;
            
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

            // Fetch bakery & slot details for confirmation email
            $bakery = $pdo->query("SELECT * FROM bakeries WHERE id = $primaryBakeryId")->fetch();
            $slot = $pdo->query("SELECT * FROM pickup_slots WHERE id = $slotId")->fetch() ?: $pickupSlots[0];

            $orderData = [
                'reservation_code' => $orderCode,
                'total_amount' => $cartTotal,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus
            ];
            @sendReservationConfirmationEmail($orderData, $itemsData, $bakery, $slot, $email, $fullName);

            setFlash('success', "Order placed successfully! Your unique order number is <strong>{$orderCode}</strong>.");
            header("Location: " . BASE_URL . "/confirmation.php?code=" . urlencode($orderCode));
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            setFlash('danger', 'Error placing order: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Checkout – NomReNom';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb & Header -->
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/cart.php" class="text-decoration-none text-muted">Shopping Cart</a></li>
                <li class="breadcrumb-item active text-dark" aria-current="page">Checkout</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="bi bi-shield-lock-fill text-success"></i> Secure Checkout
        </h1>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/checkout.php" id="checkoutForm">
        <?= csrfField() ?>

        <div class="row g-4">
            <!-- Left Column: Customer Details, Fulfillment, Payment -->
            <div class="col-lg-8">
                <!-- 1. Customer Information Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                            <span class="step-badge">1</span> Customer Contact Information
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
                                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($loggedInUser['full_name']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($loggedInUser['email']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($loggedInUser['phone'] ?? '') ?>" placeholder="0901234567" required>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <span class="badge bg-success mb-2">Guest Checkout Available</span>
                            <p class="small text-muted mb-0">You do not need to register to order! Enter your details below to receive your order confirmation code.</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Your Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="guest_name" class="form-control" placeholder="e.g. Alex Nguyen" value="<?= htmlspecialchars($_POST['guest_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="guest_email" class="form-control" placeholder="alex@gmail.com" value="<?= htmlspecialchars($_POST['guest_email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="guest_phone" class="form-control" placeholder="0901234567" value="<?= htmlspecialchars($_POST['guest_phone'] ?? '') ?>" required>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 2. Preferred Fulfillment Option Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <span class="step-badge">2</span> Fulfillment Method
                    </h5>

                    <!-- Pickup vs Delivery Toggle Radios -->
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="fulfillment-card d-block p-3 rounded-3 border cursor-pointer active" id="label-pickup">
                                <div class="form-check d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="radio" name="fulfillment_type" id="type_pickup" value="pickup" checked>
                                    <div class="form-check-label w-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-dark"><i class="bi bi-shop text-success me-1"></i> Store Pickup</strong>
                                            <span class="badge bg-success">FREE</span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Collect directly at partner bakery counter in 60s</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="fulfillment-card d-block p-3 rounded-3 border cursor-pointer" id="label-delivery">
                                <div class="form-check d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="radio" name="fulfillment_type" id="type_delivery" value="delivery">
                                    <div class="form-check-label w-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-dark"><i class="bi bi-bicycle text-primary me-1"></i> Local Delivery</strong>
                                            <span class="badge bg-primary">FREE PROMO</span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Hand-delivered to your dorm, office, or door</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Pickup Details Block -->
                    <div id="pickup-details-block" class="p-3 bg-light rounded-3 border">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Bakery Location</label>
                                <div class="form-control bg-white">
                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i> 
                                    <strong><?= htmlspecialchars($firstItem['bakery_name'] ?? 'Dreamy Bakes') ?></strong>
                                    <div class="small text-muted"><?= htmlspecialchars($firstItem['district'] ?? 'District 1, Ho Chi Minh City') ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Preferred Collection Time Window</label>
                                <select name="slot_id" class="form-select">
                                    <?php foreach ($pickupSlots as $slot): ?>
                                        <option value="<?= $slot['id'] ?>">
                                            <?= formatDateTime($slot['pickup_date'], 'D, M d') ?> (<?= formatTime($slot['start_time']) ?> - <?= formatTime($slot['end_time']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Details Block (hidden by default) -->
                    <div id="delivery-details-block" class="p-3 bg-light rounded-3 border" style="display: none;">
                        <label class="form-label small fw-bold">Delivery Address & Room / Building Number <span class="text-danger">*</span></label>
                        <input type="text" name="delivery_address" id="delivery_address_input" class="form-control mb-2" placeholder="e.g. Dormitory B, Room 402, Thu Duc University Campus">
                        <div class="small text-muted"><i class="bi bi-info-circle me-1"></i> Our delivery rider will call you upon arrival at your building lobby.</div>
                    </div>
                </div>

                <!-- 3. Payment Method Selection Card -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <span class="step-badge">3</span> Select Payment Method
                    </h5>

                    <div class="row g-3">
                        <!-- MoMo -->
                        <div class="col-sm-6">
                            <label class="payment-card d-block p-3 rounded-3 border cursor-pointer">
                                <div class="form-check d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="radio" name="payment_method" value="MoMo" id="pay_momo">
                                    <div class="form-check-label w-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-dark">MoMo E-Wallet</strong>
                                            <span class="badge bg-danger">Instant QR</span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Pay with MoMo app or scan QR code</small>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- VNPay -->
                        <div class="col-sm-6">
                            <label class="payment-card d-block p-3 rounded-3 border cursor-pointer">
                                <div class="form-check d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="radio" name="payment_method" value="VNPay" id="pay_vnpay">
                                    <div class="form-check-label w-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-dark">VNPay Portal</strong>
                                            <span class="badge bg-primary">Bank & Card</span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Vietnamese ATM cards, QR, Visa / Mastercard</small>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Bank Transfer (VietQR) -->
                        <div class="col-sm-6">
                            <label class="payment-card d-block p-3 rounded-3 border cursor-pointer">
                                <div class="form-check d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="radio" name="payment_method" value="Bank Transfer" id="pay_bank">
                                    <div class="form-check-label w-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-dark">Bank Transfer (VietQR)</strong>
                                            <span class="badge bg-info text-dark">Zero Fee</span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Instant 24/7 transfer via mobile banking app</small>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Cash on Pickup / Delivery -->
                        <div class="col-sm-6">
                            <label class="payment-card d-block p-3 rounded-3 border cursor-pointer active">
                                <div class="form-check d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="radio" name="payment_method" value="Cash on Pickup" id="pay_cash" checked>
                                    <div class="form-check-label w-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-dark" id="cash-method-title">Cash on Pickup</strong>
                                            <span class="badge bg-success">Pay at Counter</span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Pay with cash when you receive your food</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Bank Transfer Details Simulation Info -->
                    <div id="bank-info-box" class="mt-3 p-3 bg-light rounded-3 border" style="display: none;">
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-bank text-info me-1"></i> NomReNom Bank Transfer Details</h6>
                        <div class="row g-2 small text-muted">
                            <div class="col-6">Bank: <strong>Military Bank (MB Bank)</strong></div>
                            <div class="col-6">Account No: <strong>8888-NOMRENOM-VN</strong></div>
                            <div class="col-6">Account Holder: <strong>NOMRENOM FOOD TECH</strong></div>
                            <div class="col-6">Transfer Memo: <strong>[Your Order Code]</strong></div>
                        </div>
                    </div>
                </div>

                <!-- 4. Special Instructions -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square text-muted"></i> Special Instructions / Notes (Optional)
                    </h5>
                    <textarea name="special_instructions" class="form-control" rows="2" placeholder="e.g. Please pack sweet and savory items separately, or extra paper napkins."></textarea>
                </div>
            </div>

            <!-- Right Column: Order Summary & Place Order CTA -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 90px;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark m-0">Your Order</h5>
                        <a href="<?= BASE_URL ?>/cart.php" class="small text-success fw-bold text-decoration-none">Edit Cart</a>
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
                        <span>Fulfillment Fee</span>
                        <span class="text-success fw-semibold">FREE (0 ₫)</span>
                    </div>

                    <div class="d-flex justify-content-between text-muted small mb-3">
                        <span>Eco-Packaging</span>
                        <span class="text-success fw-semibold">Included</span>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fw-bold text-dark fs-5">Total To Pay</span>
                        <span class="fw-bold text-success fs-3"><?= formatVnd($cartTotal) ?></span>
                    </div>

                    <button type="submit" class="btn btn-nrn-primary w-100 py-3 fs-5 fw-bold d-flex justify-content-center align-items-center gap-2 rounded-3 shadow-sm">
                        <i class="bi bi-bag-check-fill"></i> Place Order Now
                    </button>

                    <div class="text-center mt-3 small text-muted">
                        <i class="bi bi-lock-fill text-success"></i> 256-Bit SSL Encrypted & Guaranteed Fresh
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Interactive Checkout Behavior Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fulfillment Type Switch
    const typePickup = document.getElementById('type_pickup');
    const typeDelivery = document.getElementById('type_delivery');
    const pickupBlock = document.getElementById('pickup-details-block');
    const deliveryBlock = document.getElementById('delivery-details-block');
    const deliveryInput = document.getElementById('delivery_address_input');
    const labelPickup = document.getElementById('label-pickup');
    const labelDelivery = document.getElementById('label-delivery');
    const cashTitle = document.getElementById('cash-method-title');

    function syncFulfillment() {
        if (typeDelivery.checked) {
            pickupBlock.style.display = 'none';
            deliveryBlock.style.display = 'block';
            deliveryInput.setAttribute('required', 'required');
            labelDelivery.classList.add('active');
            labelPickup.classList.remove('active');
            cashTitle.textContent = 'Cash on Delivery';
        } else {
            pickupBlock.style.display = 'block';
            deliveryBlock.style.display = 'none';
            deliveryInput.removeAttribute('required');
            labelPickup.classList.add('active');
            labelDelivery.classList.remove('active');
            cashTitle.textContent = 'Cash on Pickup';
        }
    }

    typePickup.addEventListener('change', syncFulfillment);
    typeDelivery.addEventListener('change', syncFulfillment);

    // Payment Cards Styling & Bank Details Toggle
    const payRadios = document.querySelectorAll('input[name="payment_method"]');
    const bankInfoBox = document.getElementById('bank-info-box');

    payRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.payment-card').forEach(c => c.classList.remove('active'));
            this.closest('.payment-card').classList.add('active');

            if (this.value === 'Bank Transfer') {
                bankInfoBox.style.display = 'block';
            } else {
                bankInfoBox.style.display = 'none';
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
.fulfillment-card, .payment-card {
    transition: all 0.2s ease;
    border: 1px solid #E2E8F0 !important;
}
.fulfillment-card:hover, .payment-card:hover {
    border-color: #0F4C3A !important;
}
.fulfillment-card.active, .payment-card.active {
    border-color: #0F4C3A !important;
    background-color: #F0FDF4;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
