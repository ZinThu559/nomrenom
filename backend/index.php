<?php
/**
 * NomReNom - Commercial Snack Marketplace Platform
 * Pickup marketplace for near-expiry and damaged snacks (dented boxes, crumbled biscuits, melted-and-set chocolate)
 * Real photos, printed expiry dates, VietQR reservation, counter inspection, and zero shipping fees.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$pageTitle = 'NomReNom – Near-Expiry & Imperfect Snacks Marketplace';
$pdo = getDbConnection();

// Fetch active snack categories
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

// Fetch featured snack products
$boxesStmt = $pdo->query("SELECT f.*, b.name AS bakery_name, b.district, b.city, b.address, c.name AS category_name 
                          FROM food_boxes f 
                          JOIN bakeries b ON f.bakery_id = b.id 
                          LEFT JOIN categories c ON f.category_id = c.id 
                          WHERE f.is_active = 1 
                          ORDER BY f.id ASC 
                          LIMIT 6");
$featuredBoxes = $boxesStmt->fetchAll();

// Fetch partner stores
$bakeriesStmt = $pdo->query("SELECT * FROM bakeries WHERE is_active = 1 LIMIT 3");
$partnerBakeries = $bakeriesStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. HERO SECTION -->
<section class="py-5" style="background: linear-gradient(180deg, #FAF7F2 0%, #FFFFFF 100%); border-bottom: 1px solid #EADBCC;">
    <div class="container-xl">
        <div class="row align-items-center g-4 g-lg-5">
            <!-- Left Hero Column -->
            <div class="col-lg-7">
                <div class="d-inline-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 fw-bold text-uppercase">
                        <i class="bi bi-shield-check me-1"></i> Store Pickup Only • Zero Shipping
                    </span>
                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning px-3 py-2 fw-bold text-uppercase">
                        Up to 70% Off
                    </span>
                </div>
                
                <h1 class="display-4 fw-bold text-dark mb-3" style="letter-spacing: -0.02em;">
                    Delicious Snacks,<br>
                    <span style="color: #0F4C3A;">Student-Friendly Prices!</span>
                </h1>
                
                <!-- Teddy Bear Mascot Sticker & Short Letter -->
                <div class="mb-4">
                    <div class="d-flex align-items-end gap-3 mb-2">
                        <img src="<?= BASE_URL ?>/assets/img/teddy_nomrenom.svg" alt="NomReNom Teddy Bear Mascot" style="width: 165px; height: auto; filter: drop-shadow(0 12px 24px rgba(15,76,58,0.20));" class="img-fluid">
                        <div class="d-none d-sm-block mb-3">
                            <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 fw-bold">
                                <i class="bi bi-chat-heart-fill text-danger me-1"></i> Hi! I'm Nommy
                            </span>
                        </div>
                    </div>
                    <!-- Short Letter under the Teddy Bear -->
                    <p class="fs-5 text-dark fw-medium mb-1" style="max-width: 540px; line-height: 1.5;">
                        Enjoy your favorite chips, cookies &amp; chocolates at up to 70% off.<br>
                        <span class="text-success fw-bold"><i class="bi bi-bag-check-fill me-1"></i> Reserve with VietQR, pick up at your local store &amp; save!</span>
                    </p>
                </div>

                <!-- Search & Filter Box -->
                <form action="<?= BASE_URL ?>/boxes.php" method="GET" class="card border-0 shadow-sm rounded-4 p-2 p-md-3 bg-white mb-4">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" name="q" class="form-control border-0 ps-0" placeholder="Search Pocky, Oreo, KitKat, chips...">
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <select name="grade" class="form-select border-0 bg-light">
                                <option value="">All Condition Grades</option>
                                <option value="dented_box">📦 Dented Packaging</option>
                                <option value="crumbled_biscuits">🍪 Crumbled / Broken</option>
                                <option value="melted_reset">🍫 Melted &amp; Re-set</option>
                                <option value="near_expiry">⏳ Near-Expiry (7-30d)</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <button type="submit" class="btn btn-nrn-primary w-100 py-2 fw-bold">
                                <i class="bi bi-funnel me-1"></i> Browse Snacks
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Popular Condition Chips -->
                <div class="d-flex flex-wrap gap-2 align-items-center text-muted small">
                    <span class="fw-bold text-dark"><i class="bi bi-tag-fill text-success me-1"></i> Quick Filters:</span>
                    <a href="<?= BASE_URL ?>/boxes.php?grade=dented_box" class="badge bg-light text-dark border text-decoration-none px-2 py-1 rounded-pill">📦 Dented Box</a>
                    <a href="<?= BASE_URL ?>/boxes.php?grade=crumbled_biscuits" class="badge bg-light text-dark border text-decoration-none px-2 py-1 rounded-pill">🍪 Crumbled Biscuits</a>
                    <a href="<?= BASE_URL ?>/boxes.php?grade=melted_reset" class="badge bg-light text-dark border text-decoration-none px-2 py-1 rounded-pill">🍫 Melted &amp; Re-set</a>
                    <a href="<?= BASE_URL ?>/boxes.php?grade=near_expiry" class="badge bg-light text-dark border text-decoration-none px-2 py-1 rounded-pill">⏳ 7–30 Days Left</a>
                </div>
            </div>

            <!-- Right Hero Column: Visual Showcase Card -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <div class="p-2 rounded-3 bg-success bg-opacity-10 text-success">
                                <i class="bi bi-camera-fill fs-5"></i>
                            </div>
                            <div>
                                <h6 class="m-0 fw-bold text-dark">Real Photo Guarantee</h6>
                                <small class="text-muted">What you see is what you inspect</small>
                            </div>
                        </div>
                        <span class="badge bg-danger rounded-pill px-3 py-2">-63% OFF</span>
                    </div>

                    <div class="position-relative mb-3 rounded-3 overflow-hidden bg-light" style="max-height: 280px;">
                        <img src="<?= BASE_URL ?>/assets/img/snacks/pocky_strawberry_dented.jpg" alt="Pocky Dented Box Preview" class="w-100 object-fit-cover" style="height: 260px;">
                        <div class="position-absolute top-0 start-0 m-2">
                            <span class="badge bg-dark bg-opacity-75 text-white">📦 Dented Corner on Outer Box</span>
                        </div>
                        <div class="position-absolute bottom-0 start-0 w-100 p-2 text-white small" style="background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.7) 100%);">
                            <i class="bi bi-check-circle-fill text-success me-1"></i> Inner Foil Packet 100% Airtight &amp; Crisp
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Glico Pocky Strawberry (Family Pack)</h6>
                            <small class="text-muted"><i class="bi bi-shop text-success me-1"></i> Circle K – Campus Hub</small>
                        </div>
                        <div class="text-end">
                            <div class="fs-5 fw-bold text-success">14.000 ₫</div>
                            <div class="small text-muted text-decoration-line-through">38.000 ₫</div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/box_details.php?id=1" class="btn btn-nrn-primary rounded-pill py-2 fw-bold">
                            <i class="bi bi-qr-code-scan me-2"></i> Inspect &amp; Reserve Deal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. WHAT STORES CAN LIST (Strict Standards Section) -->
<section class="py-5 bg-white border-bottom">
    <div class="container-xl">
        <div class="text-center max-w-700 mx-auto mb-4 mb-md-5">
            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 fw-bold text-uppercase mb-2">Our Standards</span>
            <h2 class="fw-bold text-dark mb-2">What Partner Stores Can List</h2>
            <p class="text-muted small">We only accept safe, delicious snacks. Zero expired food. Ever.</p>
        </div>

        <div class="row g-3 g-md-4">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card border shadow-sm rounded-4 p-4 h-100 bg-white">
                    <div class="d-inline-flex p-3 rounded-circle bg-warning bg-opacity-10 text-warning mb-3" style="width: 50px; height: 50px; align-items: center; justify-content: center;">
                        <i class="bi bi-hourglass-split fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">1. Near-Expiry (1 Month to 7 Days)</h6>
                    <p class="text-muted small m-0">
                        Food with 1 month down to 7 days left before the printed date. <strong>Strict rule: Nothing under 7 days, and nothing expired.</strong>
                    </p>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="card border shadow-sm rounded-4 p-4 h-100 bg-white">
                    <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-3" style="width: 50px; height: 50px; align-items: center; justify-content: center;">
                        <i class="bi bi-box-seam fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">2. Dented or Damaged Packaging</h6>
                    <p class="text-muted small m-0">
                        Boxes with creased corners, squashed canisters, or crumpled foil. The inner seal is 100% airtight and food is totally fresh.
                    </p>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="card border shadow-sm rounded-4 p-4 h-100 bg-white">
                    <div class="d-inline-flex p-3 rounded-circle bg-danger bg-opacity-10 text-danger mb-3" style="width: 50px; height: 50px; align-items: center; justify-content: center;">
                        <i class="bi bi-cookie fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">3. Imperfect Shape (Crumbled / Melted)</h6>
                    <p class="text-muted small m-0">
                        Biscuits cracked in transit, broken wafers, or chocolates that melted and set again. Taste is 100% prime.
                    </p>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="card border shadow-sm rounded-4 p-4 h-100 bg-white">
                    <div class="d-inline-flex p-3 rounded-circle bg-success bg-opacity-10 text-success mb-3" style="width: 50px; height: 50px; align-items: center; justify-content: center;">
                        <i class="bi bi-camera-fill fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">4. Counter Check &amp; Refund</h6>
                    <p class="text-muted small m-0">
                        Inspect your snacks at the store counter before taking them home. If anything does not match the real photo, refuse it for a full refund!
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 3. FEATURED SNACK LISTINGS -->
<section class="py-5" style="background-color: #FAF7F2;">
    <div class="container-xl">
        <!-- Category Discovery Bar (iOS Food App Style & Desktop Grid) -->
        <div class="mb-5 pb-2">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success bg-opacity-10 text-success p-2 rounded-3">
                        <i class="bi bi-grid-fill"></i>
                    </span>
                    <span class="fw-bold small text-dark text-uppercase tracking-wider">Browse by Category</span>
                </div>
                <a href="<?= BASE_URL ?>/boxes.php" class="text-success small fw-bold text-decoration-none">
                    See All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="category-strip-container">
                <a href="<?= BASE_URL ?>/boxes.php" class="category-card-pill">
                    <div class="category-card-icon" style="background: #F0FDF4; border: 1px solid #DCFCE7;">🏷️</div>
                    <span class="category-card-label">All Deals</span>
                    <span class="category-card-subtitle badge rounded-pill bg-success bg-opacity-10 text-success">Up to 70% Off</span>
                </a>
                <a href="<?= BASE_URL ?>/boxes.php?cat=1" class="category-card-pill">
                    <div class="category-card-icon" style="background: #FFFBEB; border: 1px solid #FEF3C7;">🍪</div>
                    <span class="category-card-label">Biscuits</span>
                    <span class="category-card-subtitle text-muted">From 12.000 ₫</span>
                </a>
                <a href="<?= BASE_URL ?>/boxes.php?cat=2" class="category-card-pill">
                    <div class="category-card-icon" style="background: #FDF2F8; border: 1px solid #FCE7F3;">🍫</div>
                    <span class="category-card-label">Chocolates</span>
                    <span class="category-card-subtitle text-muted">Melted &amp; Reset</span>
                </a>
                <a href="<?= BASE_URL ?>/boxes.php?cat=3" class="category-card-pill">
                    <div class="category-card-icon" style="background: #EFF6FF; border: 1px solid #DBEAFE;">🥨</div>
                    <span class="category-card-label">Chips</span>
                    <span class="category-card-subtitle text-muted">Dented Box</span>
                </a>
                <a href="<?= BASE_URL ?>/boxes.php?q=ramyun" class="category-card-pill">
                    <div class="category-card-icon" style="background: #FFF1F2; border: 1px solid #FFE4E6;">🍜</div>
                    <span class="category-card-label">Ramyun</span>
                    <span class="category-card-subtitle text-muted">Near Expiry</span>
                </a>
                <a href="<?= BASE_URL ?>/boxes.php?q=seaweed" class="category-card-pill">
                    <div class="category-card-icon" style="background: #F0FDF4; border: 1px solid #DCFCE7;">🍙</div>
                    <span class="category-card-label">Seaweed</span>
                    <span class="category-card-subtitle text-muted">Crispy Sheets</span>
                </a>
                <a href="<?= BASE_URL ?>/boxes.php?cat=5" class="category-card-pill">
                    <div class="category-card-icon" style="background: #FEF3C7; border: 1px solid #FDE68A;">🍞</div>
                    <span class="category-card-label">Breads</span>
                    <span class="category-card-subtitle text-muted">Bakery Items</span>
                </a>
            </div>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <span class="text-success fw-bold text-uppercase small letter-spacing-1">Today's Store Stock</span>
                <h2 class="fw-bold text-dark m-0">Featured Snack Deals</h2>
            </div>
            <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-outline-dark btn-sm rounded-pill px-3 mt-2 mt-md-0 d-none d-md-inline-flex">
                View All Snacks (<?= count($featuredBoxes) ?>) <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredBoxes as $box): 
                $savings = calculateSavingsPercent((float)$box['original_value'], (float)$box['price']);
                $isOutOfStock = ($box['available_qty'] <= 0);
                $exp = getExpiryCountdown($box['expiry_date'] ?? '2026-11-28');
            ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 bg-white product-food-card">
                        <!-- Top Image Area -->
                        <div class="position-relative overflow-hidden" style="height: 200px; background-color: #F8FAFC;">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($box['image_url'] ?? 'assets/img/food_boxes/snack-box-1.jpg') ?>" 
                                 alt="<?= htmlspecialchars($box['name']) ?>" 
                                 class="w-100 h-100 object-fit-cover product-img" 
                                 loading="lazy">
                            
                            <div class="position-absolute top-0 start-0 m-2 d-flex flex-column gap-1">
                                <?= getConditionGradeBadge($box['condition_grade'] ?? 'dented_box') ?>
                            </div>

                            <?php if ($savings > 0): ?>
                                <span class="badge bg-danger text-white fw-bold position-absolute top-0 end-0 m-2 px-2 py-1 small rounded-2">
                                    -<?= $savings ?>%
                                </span>
                            <?php endif; ?>

                            <div class="position-absolute bottom-0 start-0 m-2">
                                <?= $exp['badge'] ?>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1 text-muted small">
                                    <span><i class="bi bi-shop text-success me-1"></i><?= htmlspecialchars($box['bakery_name']) ?></span>
                                    <span><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($box['district']) ?></span>
                                </div>

                                <h6 class="fw-bold text-dark mb-1">
                                    <a href="<?= BASE_URL ?>/box_details.php?id=<?= $box['id'] ?>" class="text-decoration-none text-dark hover-green">
                                        <?= htmlspecialchars($box['name']) ?>
                                    </a>
                                </h6>

                                <!-- Defect note preview -->
                                <p class="text-muted small mb-2" style="font-size: 0.8rem; line-height: 1.4;">
                                    <i class="bi bi-info-circle text-primary me-1"></i><?= htmlspecialchars(mb_strimwidth($box['defect_notes'] ?? $box['description'], 0, 95, '...')) ?>
                                </p>
                            </div>

                            <div>
                                <!-- Available stock -->
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded-2 small" style="font-size: 0.78rem;">
                                    <span class="text-muted"><i class="bi bi-box-seam me-1 text-success"></i> Stock Available:</span>
                                    <?php if ($isOutOfStock): ?>
                                        <span class="badge bg-secondary">Sold Out</span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                            <?= (int)$box['available_qty'] ?> packs left
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Price & Action Buttons -->
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <div>
                                        <div class="fw-bold text-dark fs-5"><?= formatVnd($box['price']) ?></div>
                                        <?php if ((float)$box['original_value'] > (float)$box['price']): ?>
                                            <small class="text-muted text-decoration-line-through"><?= formatVnd($box['original_value']) ?></small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <?php if ($isOutOfStock): ?>
                                            <button class="btn btn-secondary btn-sm px-3" disabled>Sold Out</button>
                                        <?php else: ?>
                                            <button type="button" 
                                                    class="btn btn-outline-success btn-sm btn-quick-add" 
                                                    data-id="<?= $box['id'] ?>"
                                                    data-name="<?= htmlspecialchars($box['name']) ?>"
                                                    onclick="nomrenomAddToCart(event, this)"
                                                    title="Add to Cart">
                                                <i class="bi bi-cart-plus"></i> Add
                                            </button>
                                            <a href="<?= BASE_URL ?>/box_details.php?id=<?= $box['id'] ?>" class="btn btn-nrn-primary btn-sm px-3">
                                                Reserve
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4">
            <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-outline-dark px-4 py-2 rounded-pill fw-bold">
                View All Available Snacks <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</section>

<!-- 4. HOW IT WORKS: THE 4-STEP FLOW -->
<section class="py-5 bg-white border-top border-bottom">
    <div class="container-xl">
        <div class="text-center max-w-700 mx-auto mb-4 mb-md-5">
            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 fw-bold text-uppercase mb-2">Simple Process</span>
            <h2 class="fw-bold text-dark mb-2">How NomReNom Works</h2>
            <p class="text-muted small">Reserve online, inspect at store, take it home. No shipping fees.</p>
        </div>

        <div class="row g-3 g-md-4">
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 bg-cream-light">
                    <div class="d-inline-flex p-3 rounded-circle bg-white text-success shadow-xs mx-auto mb-3" style="width: 55px; height: 55px; align-items: center; justify-content: center;">
                        <span class="fw-bold fs-5">1</span>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Browse</h6>
                    <p class="text-muted small m-0">
                        Inspect real photos, printed expiry dates (7–30 days left), defect grade, and discounted prices.
                    </p>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 bg-cream-light">
                    <div class="d-inline-flex p-3 rounded-circle bg-white text-success shadow-xs mx-auto mb-3" style="width: 55px; height: 55px; align-items: center; justify-content: center;">
                        <span class="fw-bold fs-5">2</span>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Reserve &amp; Pay QR</h6>
                    <p class="text-muted small m-0">
                        Scan with VietQR or your banking app. Instantly receive your 6-digit pickup code.
                    </p>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 bg-cream-light">
                    <div class="d-inline-flex p-3 rounded-circle bg-white text-success shadow-xs mx-auto mb-3" style="width: 55px; height: 55px; align-items: center; justify-content: center;">
                        <span class="fw-bold fs-5">3</span>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Check at Store</h6>
                    <p class="text-muted small m-0">
                        Visit the store counter and inspect the snack. If it doesn't match the photo, refuse it for a full refund!
                    </p>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 bg-cream-light">
                    <div class="d-inline-flex p-3 rounded-circle bg-white text-success shadow-xs mx-auto mb-3" style="width: 55px; height: 55px; align-items: center; justify-content: center;">
                        <span class="fw-bold fs-5">4</span>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Pick Up &amp; Enjoy</h6>
                    <p class="text-muted small m-0">
                        Confirm pickup on your phone and take your cheap snacks home. No shipping or waiting.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 5. PARTNER CONVENIENCE STORES -->
<section class="py-5" style="background-color: #FAF7F2;">
    <div class="container-xl">
        <div class="text-center max-w-700 mx-auto mb-4">
            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 fw-bold text-uppercase mb-2">Partner Network</span>
            <h2 class="fw-bold text-dark mb-2">Partner Stores &amp; Campus Minimarts</h2>
            <p class="text-muted small">Convenient pickup counters near universities and dorms.</p>
        </div>

        <div class="row g-4">
            <?php foreach ($partnerBakeries as $store): ?>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="p-3 rounded-circle bg-success bg-opacity-10 text-success">
                                <i class="bi bi-shop fs-4"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark m-0"><?= htmlspecialchars($store['name']) ?></h6>
                                <small class="text-muted"><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($store['district']) ?></small>
                            </div>
                        </div>
                        <p class="text-muted small mb-3">
                            <?= htmlspecialchars($store['description']) ?>
                        </p>
                        <div class="mt-auto pt-3 border-top small text-muted">
                            <div class="mb-1"><i class="bi bi-pin-map text-success me-1"></i> <?= htmlspecialchars($store['address']) ?></div>
                            <div><i class="bi bi-clock text-warning me-1"></i> Counter Hours: <?= formatTime($store['opening_time']) ?> &ndash; <?= formatTime($store['closing_time']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
