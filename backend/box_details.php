<?php
/**
 * NomReNom - Near-Expiry & Imperfect Snack Details Page
 * Real photo inspection, condition grade, expiry date, counter check & refund guarantee
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$boxId = (int)($_GET['id'] ?? 0);
if ($boxId <= 0) {
    header('Location: ' . BASE_URL . '/boxes.php');
    exit;
}

$pdo = getDbConnection();

// Fetch snack details along with store details
$stmt = $pdo->prepare("SELECT f.*, b.name AS bakery_name, b.address, b.district, b.city, b.phone AS bakery_phone, 
                              b.opening_time, b.closing_time, b.logo_url AS bakery_logo,
                              c.name AS category_name, c.icon AS category_icon
                       FROM food_boxes f 
                       JOIN bakeries b ON f.bakery_id = b.id 
                       LEFT JOIN categories c ON f.category_id = c.id
                       WHERE f.id = ? AND f.is_active = 1 LIMIT 1");
$stmt->execute([$boxId]);
$box = $stmt->fetch();

if (!$box) {
    setFlash('danger', 'The requested snack item was not found or has already been reserved.');
    header('Location: ' . BASE_URL . '/boxes.php');
    exit;
}

// Fetch available upcoming pickup slots for this store
$slotsStmt = $pdo->prepare("SELECT * FROM pickup_slots 
                            WHERE bakery_id = ? 
                              AND is_active = 1 
                              AND pickup_date >= CURDATE() 
                              AND (max_capacity - reserved_count) > 0 
                            ORDER BY pickup_date ASC, start_time ASC");
$slotsStmt->execute([$box['bakery_id']]);
$pickupSlots = $slotsStmt->fetchAll();

// Auto-seed pickup slots if none are active so reservation is never blocked
if (empty($pickupSlots)) {
    $defaultSlots = [
        ['date' => date('Y-m-d'), 'start' => '11:00:00', 'end' => '14:00:00'],
        ['date' => date('Y-m-d'), 'start' => '17:00:00', 'end' => '21:00:00'],
        ['date' => date('Y-m-d', strtotime('+1 day')), 'start' => '11:00:00', 'end' => '14:00:00'],
        ['date' => date('Y-m-d', strtotime('+1 day')), 'start' => '17:00:00', 'end' => '21:00:00'],
    ];
    $ins = $pdo->prepare("INSERT INTO pickup_slots (bakery_id, pickup_date, start_time, end_time, max_capacity, is_active) VALUES (?, ?, ?, ?, 50, 1)");
    foreach ($defaultSlots as $ds) {
        $ins->execute([$box['bakery_id'], $ds['date'], $ds['start'], $ds['end']]);
    }
    $slotsStmt->execute([$box['bakery_id']]);
    $pickupSlots = $slotsStmt->fetchAll();
}

// Fetch reviews/inspections for this snack or store
$feedbackStmt = $pdo->prepare("SELECT customer_name, rating, comment, created_at 
                               FROM feedback 
                               WHERE (food_box_id = ? OR food_box_id IS NULL) AND is_public = 1 
                               ORDER BY created_at DESC LIMIT 5");
$feedbackStmt->execute([$box['id']]);
$reviews = $feedbackStmt->fetchAll();

$savings = calculateSavingsPercent((float)$box['original_value'], (float)$box['price']);
$isAvailable = ((int)$box['available_qty'] > 0);
$gradeBadge = getConditionGradeBadge($box['condition_grade'] ?? '');
$expiryBadge = getExpiryCountdownBadge($box['expiry_date'] ?? null);

$pageTitle = htmlspecialchars($box['name']) . ' – NomReNom';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/boxes.php" class="text-decoration-none text-muted">Snacks Marketplace</a></li>
            <?php if (!empty($box['category_name'])): ?>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/boxes.php?cat=<?= (int)$box['category_id'] ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($box['category_name']) ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active text-green-dark fw-bold" aria-current="page"><?= htmlspecialchars($box['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- Left Column: Real Photo Inspection & Store Info -->
        <div class="col-lg-6">
            <!-- Real Item Photo Card with Zoom Badge -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white position-relative">
                <div class="position-relative bg-light text-center">
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($box['image_url'] ?? 'assets/img/food_boxes/snack-box-1.jpg') ?>" 
                         alt="<?= htmlspecialchars($box['name']) ?>" 
                         class="w-100 object-fit-cover" 
                         style="max-height: 440px; min-height: 320px;">
                    
                    <div class="position-absolute top-0 start-0 m-3 d-flex flex-column gap-2 align-items-start">
                        <?= $gradeBadge ?>
                        <?= $expiryBadge ?>
                    </div>

                    <?php if ($savings > 0): ?>
                        <span class="position-absolute top-0 end-0 m-3 badge bg-danger fs-6 px-3 py-2 shadow-sm">
                            -<?= $savings ?>% OFF
                        </span>
                    <?php endif; ?>

                    <div class="position-absolute bottom-0 start-0 end-0 p-2 bg-dark bg-opacity-75 text-white small d-flex justify-content-between align-items-center px-3">
                        <span><i class="bi bi-camera-fill me-1 text-warning"></i> Real Photo of Actual Stock Item</span>
                        <span class="badge bg-light text-dark"><i class="bi bi-check2-circle text-success me-1"></i>Factory Sealed Inside</span>
                    </div>
                </div>
            </div>

            <!-- Transparency & Defect Inspection Details -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white border-start border-4 border-warning">
                <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-search text-warning fs-4"></i> Condition & Defect Notes
                </h5>
                <div class="p-3 bg-light rounded-3 mb-3">
                    <div class="row g-2 small">
                        <div class="col-6">
                            <span class="text-muted d-block">Condition Grade:</span>
                            <strong class="text-dark"><?= getConditionGradeName($box['condition_grade'] ?? '') ?></strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block">Printed Expiry Date:</span>
                            <strong class="text-dark"><?= !empty($box['expiry_date']) ? date('M d, Y', strtotime($box['expiry_date'])) : 'Printed on package' ?></strong>
                        </div>
                    </div>
                </div>
                <p class="text-muted small mb-3">
                    <strong>Reported Condition: </strong> 
                    <?= nl2br(htmlspecialchars($box['defect_notes'] ?? 'Item inspected by partner store staff. Edible and factory-sealed.')) ?>
                </p>
                <div class="alert alert-success d-flex align-items-center gap-2 py-2 px-3 m-0 small rounded-3">
                    <i class="bi bi-shield-check text-success fs-5"></i>
                    <div>
                        <strong>Safety Guarantee:</strong> All items have at least 7 days before printed expiry date and have intact inner food protection seals.
                    </div>
                </div>
            </div>

            <!-- Partner Store Counter Information -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-shop fs-3"></i>
                    </div>
                    <div>
                        <h6 class="m-0 fw-bold text-green-dark"><?= htmlspecialchars($box['bakery_name']) ?></h6>
                        <small class="text-muted"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($box['district']) ?>, <?= htmlspecialchars($box['city']) ?></small>
                    </div>
                </div>
                <div class="small text-muted border-top pt-3">
                    <div class="mb-2"><strong><i class="bi bi-pin-map text-success me-2"></i>Pickup Counter:</strong> <?= htmlspecialchars($box['address']) ?>, <?= htmlspecialchars($box['district']) ?></div>
                    <div class="mb-2"><strong><i class="bi bi-clock text-warning me-2"></i>Counter Hours:</strong> <?= formatTime($box['opening_time']) ?> – <?= formatTime($box['closing_time']) ?> daily</div>
                    <div><strong><i class="bi bi-telephone text-primary me-2"></i>Store Hotline:</strong> <?= htmlspecialchars($box['bakery_phone']) ?></div>
                </div>
            </div>
        </div>

        <!-- Right Column: Pricing, Counter Inspection Guarantee & Reserve Form -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-light text-success border border-success border-opacity-25 px-3 py-2 fw-semibold">
                        <i class="bi bi-shop me-1"></i> 100% Store Counter Pickup
                    </span>
                    <?php if ($isAvailable): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2">
                            <i class="bi bi-check-circle-fill me-1"></i> <?= (int)$box['available_qty'] ?> in stock
                        </span>
                    <?php else: ?>
                        <span class="badge bg-danger px-3 py-2">Sold Out</span>
                    <?php endif; ?>
                </div>

                <h1 class="h2 fw-bold text-dark mb-2"><?= htmlspecialchars($box['name']) ?></h1>
                
                <!-- Pricing Block -->
                <div class="p-3 bg-light rounded-4 mb-4 d-flex align-items-baseline gap-3 border">
                    <span class="fs-2 fw-bold text-green-dark"><?= formatVnd($box['price']) ?></span>
                    <span class="text-muted text-decoration-line-through fs-5"><?= formatVnd($box['original_value']) ?></span>
                    <span class="badge bg-warning text-dark ms-auto py-2 px-3 fw-bold">Save <?= $savings ?>%</span>
                </div>

                <!-- 4-Step Trust Guarantee Box -->
                <div class="card border border-success border-opacity-25 bg-success bg-opacity-10 rounded-4 p-3 mb-4">
                    <h6 class="fw-bold text-green-dark mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-patch-check-fill text-success fs-5"></i> 
                        Our Check & Refund Guarantee: No Risk!
                    </h6>
                    <ol class="small text-muted mb-0 ps-3 lh-base">
                        <li><strong>Reserve & Pay:</strong> Pay via VietQR and receive your 6-digit pickup code.</li>
                        <li><strong>Inspect at Store:</strong> Show your code at the store counter and inspect the snack.</li>
                        <li><strong>Doesn't match photo?</strong> If it doesn't match the photo shown, tap <em>"Refuse at Counter"</em> on your phone for an instant refund.</li>
                        <li><strong>Happy?</strong> Take your rescued snack home and enjoy the savings!</li>
                    </ol>
                </div>

                <!-- Item Description -->
                <div class="mb-4">
                    <h6 class="text-uppercase small text-muted fw-bold">About This Snack</h6>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($box['description'])) ?></p>
                </div>

                <!-- Reservation Form -->
                <?php if (!$isAvailable): ?>
                    <div class="alert alert-secondary text-center p-4 rounded-4">
                        <i class="bi bi-box2-fill fs-2 text-muted d-block mb-2"></i>
                        <h5>This Snack Has Been Claimed</h5>
                        <p class="small text-muted mb-3">All available units at this store have been reserved. Please browse other rescued snacks near your campus!</p>
                        <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-nrn-primary rounded-pill px-4">Browse Available Snacks</a>
                    </div>
                <?php elseif (empty($pickupSlots)): ?>
                    <div class="alert alert-warning text-center p-4 rounded-4">
                        <i class="bi bi-clock-history fs-2 text-warning d-block mb-2"></i>
                        <h5>No Pickup Slots Available Today</h5>
                        <p class="small text-muted mb-0">The store counter schedule is currently updating. Please check back shortly.</p>
                    </div>
                <?php else: ?>
                    <form id="reservationForm" action="<?= BASE_URL ?>/checkout.php" method="GET">
                        <input type="hidden" name="box_id" value="<?= $box['id'] ?>">
                        <input type="hidden" id="priceUnit" value="<?= (float)$box['price'] ?>">

                        <!-- Pickup Slot Selection -->
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="bi bi-calendar2-check me-1 text-success"></i> Select Counter Pickup Window: <span class="text-danger">*</span></label>
                            <select name="slot_id" class="form-select form-select-lg rounded-3" required>
                                <option value="">-- Choose Day & Time Window --</option>
                                <?php foreach ($pickupSlots as $index => $slot): 
                                    $remainingCapacity = $slot['max_capacity'] - $slot['reserved_count'];
                                    $dateFormatted = formatDateTime($slot['pickup_date'], 'M d, Y');
                                    $timeRange = formatTime($slot['start_time']) . ' - ' . formatTime($slot['end_time']);
                                    $selected = ($index === 0) ? 'selected' : '';
                                ?>
                                    <option value="<?= $slot['id'] ?>" <?= $selected ?>>
                                        <?= $dateFormatted ?> (<?= $timeRange ?>) — <?= $remainingCapacity ?> spots left
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Quantity Selector -->
                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="bi bi-box2 me-1 text-success"></i> Quantity: <span class="text-danger">*</span></label>
                            <div class="input-group" style="max-width: 200px;">
                                <button type="button" class="btn btn-outline-secondary" id="btnQtyMinus"><i class="bi bi-dash"></i></button>
                                <input type="number" name="qty" id="reservationQty" class="form-control text-center fw-bold fs-5" value="1" min="1" max="<?= min(5, (int)$box['available_qty']) ?>" required>
                                <button type="button" class="btn btn-outline-secondary" id="btnQtyPlus"><i class="bi bi-plus"></i></button>
                            </div>
                            <small class="text-muted d-block mt-1">Maximum <?= min(5, (int)$box['available_qty']) ?> packs per order to keep deals fair for all students.</small>
                        </div>

                        <!-- Dynamic Subtotal Preview -->
                        <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded-3 border">
                            <div>
                                <span class="fw-bold d-block">Reservation Total:</span>
                                <small class="text-success"><i class="bi bi-check-circle me-1"></i>Pay via VietQR at Checkout</small>
                            </div>
                            <span class="fs-3 fw-bold text-green-dark" id="displayTotal"><?= formatVnd($box['price']) ?></span>
                        </div>

                        <!-- Submit CTA & Add to Cart Action Group -->
                        <div class="d-flex flex-column gap-2">
                            <button type="submit" class="btn btn-nrn-primary btn-lg w-100 py-3 shadow rounded-pill fw-bold">
                                <i class="bi bi-qr-code-scan me-2"></i> Reserve & Pay by VietQR (Buy Now)
                            </button>
                            <button type="button" id="btnDetailAddToCart" data-id="<?= $box['id'] ?>" data-name="<?= htmlspecialchars($box['name']) ?>" onclick="nomrenomAddDetailToCart(event, this)" class="btn btn-outline-success btn-lg w-100 py-2 rounded-pill fw-bold">
                                <i class="bi bi-cart-plus me-2"></i> Add to Cart
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Customer Reviews & Inspection Feedback Section -->
    <div class="mt-5 pt-4 border-top">
        <h4 class="mb-4 font-serif text-green-dark"><i class="bi bi-star-fill text-warning me-2"></i> Student Pickup Reviews & Ratings</h4>
        <div class="row g-4">
            <?php if (empty($reviews)): ?>
                <div class="col-12 text-muted fst-italic">
                    No reviews yet for this snack. Be the first to rescue, inspect, and enjoy!
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $rev): ?>
                    <div class="col-md-6">
                        <div class="card border-0 bg-white p-3 rounded-4 shadow-sm h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="fw-bold text-green-dark"><i class="bi bi-person-check-fill me-1 text-success"></i><?= htmlspecialchars($rev['customer_name']) ?></div>
                                <div class="text-warning small">
                                    <?php for ($i=1; $i<=5; $i++): ?>
                                        <i class="bi <?= $i <= $rev['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="text-muted small mb-2"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>
                            <small class="text-muted mt-auto"><?= formatDateTime($rev['created_at']) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isAvailable && !empty($pickupSlots)): ?>
    <!-- Mobile Sticky Reservation Bar -->
    <div class="mobile-sticky-action d-md-none">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <span class="fs-5 fw-bold text-green-dark"><?= formatVnd($box['price']) ?></span>
                <span class="text-muted text-decoration-line-through small ms-1"><?= formatVnd($box['original_value']) ?></span>
                <div class="badge bg-warning text-dark small py-0 px-1">-<?= $savings ?>%</div>
            </div>
            <button type="button" class="btn btn-nrn-primary btn-sm px-3 py-2 rounded-pill" onclick="document.getElementById('reservationForm').scrollIntoView({behavior: 'smooth', block: 'center'});">
                <i class="bi bi-qr-code me-1"></i> Reserve
            </button>
        </div>
    </div>
<?php endif; ?>

<?php 
$extraScripts = '<script src="' . BASE_URL . '/assets/js/checkout.js"></script>';
require_once __DIR__ . '/includes/footer.php'; 
?>
