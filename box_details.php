<?php
/**
 * NomReNom - Food Box Details Page
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

// Fetch box details along with bakery details
$stmt = $pdo->prepare("SELECT f.*, b.name AS bakery_name, b.address, b.district, b.city, b.phone AS bakery_phone, 
                              b.opening_time, b.closing_time, b.logo_url AS bakery_logo 
                       FROM food_boxes f 
                       JOIN bakeries b ON f.bakery_id = b.id 
                       WHERE f.id = ? AND f.is_active = 1 LIMIT 1");
$stmt->execute([$boxId]);
$box = $stmt->fetch();

if (!$box) {
    setFlash('danger', 'The requested food box was not found or has been discontinued.');
    header('Location: ' . BASE_URL . '/boxes.php');
    exit;
}

// Fetch available upcoming pickup slots for this bakery
$slotsStmt = $pdo->prepare("SELECT * FROM pickup_slots 
                            WHERE bakery_id = ? 
                              AND is_active = 1 
                              AND pickup_date >= CURDATE() 
                              AND (max_capacity - reserved_count) > 0 
                            ORDER BY pickup_date ASC, start_time ASC");
$slotsStmt->execute([$box['bakery_id']]);
$pickupSlots = $slotsStmt->fetchAll();

// Fetch reviews for this box or bakery
$feedbackStmt = $pdo->prepare("SELECT customer_name, rating, comment, created_at 
                               FROM feedback 
                               WHERE (food_box_id = ? OR food_box_id IS NULL) AND is_public = 1 
                               ORDER BY created_at DESC LIMIT 5");
$feedbackStmt->execute([$box['id']]);
$reviews = $feedbackStmt->fetchAll();

$savings = calculateSavingsPercent((float)$box['original_value'], (float)$box['price']);
$isSnack = ($box['box_type'] === 'snack_box');
$isAvailable = ($box['available_qty'] > 0);

$pageTitle = htmlspecialchars($box['name']);
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/boxes.php" class="text-decoration-none text-muted">Food Boxes</a></li>
            <li class="breadcrumb-item active text-green-dark fw-bold" aria-current="page"><?= htmlspecialchars($box['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- Left Column: Product Image & Highlights -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="position-relative">
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($box['image_url'] ?? 'assets/img/food_boxes/snack-box-1.jpg') ?>" alt="<?= htmlspecialchars($box['name']) ?>" class="w-100 object-fit-cover" style="max-height: 420px;">
                    <span class="badge-box-type <?= $isSnack ? 'badge-snack' : 'badge-meal' ?> m-3">
                        <?= $isSnack ? 'SNACK BOX' : 'MEAL BOX' ?>
                    </span>
                    <?php if ($savings > 0): ?>
                        <span class="badge-savings m-3">-<?= $savings ?>% SAVINGS</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bakery Partner Information Card -->
            <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($box['bakery_logo'] ?? 'assets/img/bakeries/bakery-logo-1.svg') ?>" alt="<?= htmlspecialchars($box['bakery_name']) ?>" class="rounded-circle" style="width: 55px; height: 55px;">
                    <div>
                        <h6 class="m-0 text-green-dark"><?= htmlspecialchars($box['bakery_name']) ?></h6>
                        <small class="text-muted"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($box['district']) ?>, <?= htmlspecialchars($box['city']) ?></small>
                    </div>
                </div>
                <div class="small text-muted border-top pt-3">
                    <div class="mb-1"><strong><i class="bi bi-pin-map me-2 text-success"></i>Pickup Address:</strong> <?= htmlspecialchars($box['address']) ?>, <?= htmlspecialchars($box['district']) ?></div>
                    <div class="mb-1"><strong><i class="bi bi-clock me-2 text-warning"></i>Hours:</strong> <?= formatTime($box['opening_time']) ?> – <?= formatTime($box['closing_time']) ?></div>
                    <div><strong><i class="bi bi-telephone me-2 text-primary"></i>Bakery Phone:</strong> <?= htmlspecialchars($box['bakery_phone']) ?></div>
                </div>
            </div>
        </div>

        <!-- Right Column: Product Info & Reservation Form -->
        <div class="col-lg-6">
            <div class="form-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-green-light text-green-dark border px-3 py-1">
                        <i class="bi bi-shield-check me-1"></i> Fresh daily guarantee
                    </span>
                    <?php if ($isAvailable): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1">
                            <i class="bi bi-check-circle me-1"></i> <?= (int)$box['available_qty'] ?> boxes left today
                        </span>
                    <?php else: ?>
                        <span class="badge bg-danger">Sold Out</span>
                    <?php endif; ?>
                </div>

                <h1 class="h2 fw-bold text-dark mb-2"><?= htmlspecialchars($box['name']) ?></h1>
                
                <!-- Pricing Block -->
                <div class="p-3 bg-light rounded-3 mb-4 d-flex align-items-baseline gap-3 border">
                    <span class="fs-2 fw-bold text-dark"><?= formatVnd($box['price']) ?></span>
                    <span class="text-muted text-decoration-line-through fs-5"><?= formatVnd($box['original_value']) ?></span>
                    <span class="badge bg-warning text-dark ms-auto">Save <?= $savings ?>%</span>
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <h6 class="text-uppercase small text-muted letter-spacing-1">Product Description</h6>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($box['description'])) ?></p>
                </div>

                <!-- Contents Sample -->
                <?php if (!empty($box['contents_sample'])): ?>
                    <div class="p-3 bg-light rounded-3 mb-4 border-start border-3 border-success">
                        <h6 class="small text-green-dark fw-bold mb-1"><i class="bi bi-basket me-1"></i> Sample Assortment Example:</h6>
                        <p class="small text-muted m-0"><?= htmlspecialchars($box['contents_sample']) ?></p>
                        <small class="text-muted fst-italic mt-1 d-block">* Box contents may vary flexibly depending on the daily fresh bakery surplus.</small>
                    </div>
                <?php endif; ?>

                <!-- Reservation Form -->
                <?php if (!$isAvailable): ?>
                    <div class="alert alert-secondary text-center p-4">
                        <i class="bi bi-calendar-x fs-2 text-muted d-block mb-2"></i>
                        <h5>This Food Box Is Sold Out</h5>
                        <p class="small text-muted mb-3">Today's surplus bakery boxes have all been claimed. Please check back tomorrow or browse other partner bakeries.</p>
                        <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-nrn-primary">Find Other Food Boxes</a>
                    </div>
                <?php elseif (empty($pickupSlots)): ?>
                    <div class="alert alert-warning text-center p-4">
                        <i class="bi bi-clock-history fs-2 text-warning d-block mb-2"></i>
                        <h5>No Pickup Slots Available</h5>
                        <p class="small text-muted mb-0">The bakery is currently updating their next collection schedule. Please check back shortly.</p>
                    </div>
                <?php else: ?>
                    <form id="reservationForm" action="<?= BASE_URL ?>/checkout.php" method="GET">
                        <input type="hidden" name="box_id" value="<?= $box['id'] ?>">
                        <input type="hidden" id="priceUnit" value="<?= (float)$box['price'] ?>">

                        <!-- Pickup Slot Selection -->
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="bi bi-calendar2-event me-1 text-success"></i> Select Pickup Time Window: <span class="text-danger">*</span></label>
                            <select name="slot_id" class="form-select form-select-lg" required>
                                <option value="">-- Choose Date & Collection Time Slot --</option>
                                <?php foreach ($pickupSlots as $slot): 
                                    $remainingCapacity = $slot['max_capacity'] - $slot['reserved_count'];
                                    $dateFormatted = formatDateTime($slot['pickup_date'], 'M d, Y');
                                    $timeRange = formatTime($slot['start_time']) . ' - ' . formatTime($slot['end_time']);
                                ?>
                                    <option value="<?= $slot['id'] ?>">
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
                            <small class="text-muted d-block mt-1">Maximum <?= min(5, (int)$box['available_qty']) ?> boxes per order to share surplus food fairly.</small>
                        </div>

                        <!-- Dynamic Subtotal Preview -->
                        <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded-3">
                            <span class="fw-bold">Estimated Total:</span>
                            <span class="fs-4 fw-bold text-green-dark" id="displayTotal"><?= formatVnd($box['price']) ?></span>
                        </div>

                        <!-- Submit CTA -->
                        <button type="submit" class="btn btn-nrn-primary btn-lg w-100 py-3 shadow">
                            <i class="bi bi-bag-check-fill me-2"></i> Proceed To Checkout
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Customer Reviews & Feedback Section -->
    <div class="mt-5 pt-4 border-top">
        <h4 class="mb-4 font-serif text-green-dark"><i class="bi bi-star-fill text-warning me-2"></i> Customer Reviews & Ratings</h4>
        <div class="row g-4">
            <?php if (empty($reviews)): ?>
                <div class="col-12 text-muted fst-italic">
                    No reviews yet for this product. Be the first to taste and share your review!
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $rev): ?>
                    <div class="col-md-6">
                        <div class="card border-0 bg-white p-3 rounded-3 shadow-sm h-100">
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
    <!-- Mobile Sticky Reservation Bar (Visible only on Mobile <= 767px) -->
    <div class="mobile-sticky-action d-md-none">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <span class="fs-5 fw-bold text-green-dark"><?= formatVnd($box['price']) ?></span>
                <span class="text-muted text-decoration-line-through small ms-1"><?= formatVnd($box['original_value']) ?></span>
                <div class="badge bg-warning text-dark small py-0 px-1">-<?= $savings ?>%</div>
            </div>
            <button type="button" class="btn btn-nrn-primary btn-sm px-3 py-2" onclick="document.getElementById('reservationForm').scrollIntoView({behavior: 'smooth', block: 'center'});">
                <i class="bi bi-bag-check-fill me-1"></i> Reserve Now
            </button>
        </div>
    </div>
<?php endif; ?>

<?php 
$extraScripts = '<script src="' . BASE_URL . '/assets/js/checkout.js"></script>';
require_once __DIR__ . '/includes/footer.php'; 
?>
