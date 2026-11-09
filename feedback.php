<?php
/**
 * NomReNom - Customer Feedback & Review System
 * Allows customers and guests to rate bakery experiences, food box freshness, and service quality
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDbConnection();
$user = currentUser();
$error = null;

// Support various URL parameters for 1-click review access from orders, receipts, and tracking
$prefilledCode = clean($_GET['code'] ?? $_GET['reservation_code'] ?? '');
$resIdParam = (int)($_GET['reservation_id'] ?? 0);

if (empty($prefilledCode) && $resIdParam > 0) {
    $cStmt = $pdo->prepare("SELECT reservation_code FROM reservations WHERE id = ?");
    $cStmt->execute([$resIdParam]);
    $prefilledCode = $cStmt->fetchColumn() ?: '';
}

// Fetch active bakeries and food products for dropdown
$bakeries = $pdo->query("SELECT id, name FROM bakeries WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
$foodBoxes = $pdo->query("SELECT id, name, bakery_id FROM food_boxes WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

// Fetch user's recent orders if logged in
$myRecentOrders = [];
if ($user) {
    $myOrdersStmt = $pdo->prepare("SELECT r.id, r.reservation_code, r.created_at, b.name AS bakery_name 
                                  FROM reservations r 
                                  JOIN bakeries b ON r.bakery_id = b.id 
                                  WHERE r.user_id = ? 
                                  ORDER BY r.created_at DESC LIMIT 6");
    $myOrdersStmt->execute([$user['id']]);
    $myRecentOrders = $myOrdersStmt->fetchAll();
}

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $reservationCode = clean($_POST['reservation_code'] ?? '');
    $bakeryId = (int)($_POST['bakery_id'] ?? 0);
    $foodBoxId = (int)($_POST['food_box_id'] ?? 0);
    $customerName = clean($_POST['customer_name'] ?? '');
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = clean($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a valid rating between 1 and 5 stars.';
    } elseif (empty($comment) || mb_strlen($comment) < 5) {
        $error = 'Review comments must be at least 5 characters.';
    } else {
        $reservationId = null;
        $matchedUserId = $user ? $user['id'] : null;
        $matchedGuestId = null;

        // If reservation code entered, verify and link it
        if (!empty($reservationCode)) {
            $resStmt = $pdo->prepare("SELECT id, user_id, guest_id, bakery_id FROM reservations WHERE reservation_code = ? LIMIT 1");
            $resStmt->execute([$reservationCode]);
            $foundRes = $resStmt->fetch();

            if ($foundRes) {
                $reservationId = $foundRes['id'];
                if (!empty($foundRes['user_id'])) $matchedUserId = $foundRes['user_id'];
                if (!empty($foundRes['guest_id'])) $matchedGuestId = $foundRes['guest_id'];

                // Automatically link primary food box if not manually selected
                if ($foodBoxId <= 0) {
                    $boxStmt = $pdo->prepare("SELECT food_box_id FROM reservation_items WHERE reservation_id = ? LIMIT 1");
                    $boxStmt->execute([$reservationId]);
                    $foodBoxId = (int)$boxStmt->fetchColumn();
                }
            }
        }

        // If foodBoxId is still 0, convert to null
        $finalBoxId = ($foodBoxId > 0) ? $foodBoxId : null;
        $finalName = !empty($customerName) ? $customerName : ($user['full_name'] ?? 'Verified Customer');

        $insertStmt = $pdo->prepare("INSERT INTO feedback 
            (reservation_id, user_id, guest_id, food_box_id, customer_name, rating, comment, is_public, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())");
        $insertStmt->execute([
            $reservationId,
            $matchedUserId,
            $matchedGuestId,
            $finalBoxId,
            $finalName,
            $rating,
            $comment
        ]);

        setFlash('success', 'Thank you for your review! Your feedback has been published and helps fellow customers discover great surplus bakes.');
        header('Location: ' . BASE_URL . '/feedback.php');
        exit;
    }
}

// Fetch all public reviews
$reviewsStmt = $pdo->query("SELECT fb.*, b.name AS bakery_name, bx.name AS box_name 
                            FROM feedback fb 
                            LEFT JOIN reservations r ON fb.reservation_id = r.id 
                            LEFT JOIN food_boxes bx ON fb.food_box_id = bx.id 
                            LEFT JOIN bakeries b ON (bx.bakery_id = b.id OR r.bakery_id = b.id) 
                            WHERE fb.is_public = 1 
                            ORDER BY fb.created_at DESC LIMIT 30");
$reviews = $reviewsStmt->fetchAll();

$pageTitle = 'Customer Reviews & Ratings';
require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-cream-dark py-5 mb-5 border-bottom">
    <div class="container text-center">
        <span class="badge bg-green-dark text-white px-3 py-2 text-uppercase mb-2">Community Experiences</span>
        <h1 class="display-5 font-serif text-green-dark mb-3">Customer Reviews & Ratings</h1>
        <p class="fs-5 text-muted max-w-700 mx-auto">
            Discover real experiences from customers rescuing fresh surplus bakery boxes, pastries, and breads across Ho Chi Minh City.
        </p>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-5">
        <!-- Review Submission Form -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 100px;">
                <h4 class="font-serif text-green-dark mb-2">
                    <i class="bi bi-star-fill text-warning me-2"></i>Write a Customer Review
                </h4>
                <p class="text-muted small mb-4">Share your feedback on pastry taste, freshness, and bakery pickup experience.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger small mb-3"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (!empty($myRecentOrders) && empty($prefilledCode)): ?>
                    <!-- Quick 1-Click Order Selector for Logged In Customer -->
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <small class="fw-bold text-dark d-block mb-2"><i class="bi bi-receipt me-1 text-success"></i>Select one of your recent orders:</small>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($myRecentOrders as $myOrd): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('inputOrderCode').value='<?= htmlspecialchars($myOrd['reservation_code']) ?>';">
                                    #<?= htmlspecialchars($myOrd['reservation_code']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/feedback.php" method="POST">
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Order Reference Code (Optional / Recommended)</label>
                        <input type="text" id="inputOrderCode" name="reservation_code" value="<?= htmlspecialchars($prefilledCode ?: ($_POST['reservation_code'] ?? '')) ?>" class="form-control text-uppercase" placeholder="e.g. NRN-20261107-XXXX">
                        <div class="form-text small">Enter your order code to display a "Verified Order" badge on your review.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Food Product / Box Reviewed (Optional)</label>
                        <select name="food_box_id" class="form-select">
                            <option value="">Select a Food Box or Pastry (Optional)</option>
                            <?php foreach ($foodBoxes as $box): ?>
                                <option value="<?= $box['id'] ?>" <?= (isset($_POST['food_box_id']) && (int)$_POST['food_box_id'] === $box['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($box['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Your Display Name <span class="text-danger">*</span></label>
                        <input type="text" name="customer_name" value="<?= htmlspecialchars($user['full_name'] ?? ($_POST['customer_name'] ?? '')) ?>" class="form-control" placeholder="e.g. Alex Nguyen" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Star Rating <span class="text-danger">*</span></label>
                        <select name="rating" class="form-select" required>
                            <option value="5" selected>⭐⭐⭐⭐⭐ (5 Stars – Excellent, very fresh & delicious)</option>
                            <option value="4">⭐⭐⭐⭐ (4 Stars – Very satisfied, great value)</option>
                            <option value="3">⭐⭐⭐ (3 Stars – Good / Satisfactory)</option>
                            <option value="2">⭐⭐ (2 Stars – Needs improvement)</option>
                            <option value="1">⭐ (1 Star – Disappointed)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Your Review & Comments <span class="text-danger">*</span></label>
                        <textarea name="comment" rows="4" class="form-control" placeholder="Tell us about the freshness, variety, taste, and bakery staff service..." required><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-nrn-primary btn-lg w-100 py-3 shadow-sm">
                        <i class="bi bi-send-check-fill me-2"></i> Submit Customer Review
                    </button>
                </form>
            </div>
        </div>

        <!-- Public Reviews Showcase -->
        <div class="col-lg-7">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="font-serif text-green-dark m-0">Recent Customer Reviews (<?= count($reviews) ?>)</h4>
                <span class="badge bg-green-light text-green-dark px-3 py-2 rounded-pill"><i class="bi bi-patch-check-fill me-1"></i>Verified Feedback</span>
            </div>

            <?php if (empty($reviews)): ?>
                <div class="p-5 text-center bg-white rounded-4 border">
                    <i class="bi bi-chat-heart fs-1 text-muted"></i>
                    <h5 class="fw-bold text-dark mt-3 mb-1">No reviews yet</h5>
                    <p class="text-muted mb-0">Be the first to share your NomReNom food box experience with the community!</p>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($reviews as $rev): ?>
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <div>
                                    <strong class="text-green-dark fs-6">
                                        <i class="bi bi-person-circle text-success me-1"></i><?= htmlspecialchars($rev['customer_name']) ?>
                                    </strong>
                                    <?php if (!empty($rev['reservation_code'])): ?>
                                        <span class="badge bg-light text-success border ms-2">
                                            <i class="bi bi-check-circle-fill me-1"></i>Order #<?= htmlspecialchars($rev['reservation_code']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-warning fs-6">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi <?= $i <= $rev['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <?php if (!empty($rev['box_name']) || !empty($rev['bakery_name'])): ?>
                                <div class="small text-muted mb-2">
                                    <?php if (!empty($rev['box_name'])): ?>
                                        <span class="text-dark fw-semibold"><i class="bi bi-bag-check me-1 text-muted"></i><?= htmlspecialchars($rev['box_name']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($rev['bakery_name'])): ?>
                                        <span class="text-muted ms-1">• <?= htmlspecialchars($rev['bakery_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <p class="text-dark small mb-2" style="line-height: 1.6;">
                                <?= nl2br(htmlspecialchars($rev['comment'])) ?>
                            </p>
                            <small class="text-muted"><i class="bi bi-clock me-1"></i><?= formatDateTime($rev['created_at']) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
