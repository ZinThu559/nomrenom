<?php
/**
 * NomReNom - Commercial Online Food Ordering Website
 * Modern GrabFood / Foodpanda style design with Cream, White, and Dark Green palette
 * Customers can browse categories, view photos/prices, add to cart, and track live orders
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$pageTitle = 'NomReNom – Online Food Ordering & Surplus Bakery Marketplace';
$pdo = getDbConnection();

// Fetch active food categories
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

// Fetch featured food items
$boxesStmt = $pdo->query("SELECT f.*, b.name AS bakery_name, b.district, b.city, c.name AS category_name 
                          FROM food_boxes f 
                          JOIN bakeries b ON f.bakery_id = b.id 
                          LEFT JOIN categories c ON f.category_id = c.id 
                          WHERE f.is_active = 1 
                          ORDER BY f.available_qty DESC, f.id ASC 
                          LIMIT 6");
$featuredBoxes = $boxesStmt->fetchAll();

// Fetch partner bakeries
$bakeriesStmt = $pdo->query("SELECT * FROM bakeries WHERE is_active = 1 LIMIT 3");
$partnerBakeries = $bakeriesStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. COMMERCIAL HERO SECTION (GrabFood / Foodpanda Aesthetic: Cream, Dark Green & White) -->
<section class="hero-commercial py-5" style="background: linear-gradient(180deg, #FAF7F2 0%, #FFFFFF 100%); border-bottom: 1px solid #EADBCC;">
    <div class="container-xl">
        <div class="row align-items-center g-4 g-lg-5">
            <!-- Left Hero Column -->
            <div class="col-lg-7">
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 fw-bold text-uppercase mb-3">
                    <i class="bi bi-fire text-danger me-1"></i> Fresh Daily Bakes • Up to 60% Off
                </span>
                
                <h1 class="display-4 fw-bold text-dark mb-3" style="letter-spacing: -0.02em;">
                    Good Food, Better Prices,<br>
                    <span style="color: #0F4C3A;">Zero Food Waste.</span>
                </h1>
                
                <p class="fs-5 text-muted mb-4" style="max-width: 580px; line-height: 1.6;">
                    Order handcrafted pastries, sourdough loaves, and wholesome meal boxes from premier local bakeries. Standard flat pricing: 
                    <strong class="text-dark">40,000 ₫ for Snack Box</strong> and <strong class="text-dark">70,000 ₫ for Meal Box</strong>.
                </p>

                <!-- Search & Quick Filter Toolbar -->
                <form action="<?= BASE_URL ?>/boxes.php" method="GET" class="card border-0 shadow-sm rounded-4 p-2 p-md-3 bg-white mb-4">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" name="q" class="form-control border-0 ps-0" placeholder="Search croissants, sourdough, cakes...">
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <select name="cat" class="form-select border-0 bg-light">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <button type="submit" class="btn btn-nrn-primary w-100 py-2 fw-bold">
                                <i class="bi bi-funnel me-1"></i> Search Food
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Category Shortcut Chips -->
                <div class="d-flex flex-wrap gap-2 align-items-center text-muted small">
                    <span class="fw-bold text-dark"><i class="bi bi-tags-fill text-success me-1"></i> Popular:</span>
                    <a href="<?= BASE_URL ?>/boxes.php?type=snack_box" class="badge bg-light text-dark border text-decoration-none px-2 py-1 rounded-pill">Snack Box (40k)</a>
                    <a href="<?= BASE_URL ?>/boxes.php?type=meal_box" class="badge bg-light text-dark border text-decoration-none px-2 py-1 rounded-pill">Meal Box (70k)</a>
                    <a href="<?= BASE_URL ?>/boxes.php?q=croissant" class="badge bg-light text-dark border text-decoration-none px-2 py-1 rounded-pill">Croissants</a>
                    <a href="<?= BASE_URL ?>/boxes.php?q=sourdough" class="badge bg-light text-dark border text-decoration-none px-2 py-1 rounded-pill">Sourdough</a>
                </div>
            </div>

            <!-- Right Hero Column: Showcase Board with Floating Deal Pill -->
            <div class="col-lg-5">
                <div class="position-relative rounded-4 overflow-hidden shadow-lg border">
                    <img src="<?= BASE_URL ?>/assets/img/bakery_pastries_1793872097849.jpg" 
                         alt="Fresh Artisan Bakes at NomReNom" 
                         class="w-100 object-fit-cover" 
                         style="height: clamp(300px, 36vw, 420px);">
                    
                    <div class="position-absolute bottom-0 start-0 m-3 p-3 rounded-3 shadow-sm bg-white bg-opacity-95 border" style="backdrop-filter: blur(8px); max-width: 90%;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2 rounded-circle bg-success text-white">
                                <i class="bi bi-bag-check-fill fs-5"></i>
                            </div>
                            <div>
                                <strong class="d-block text-dark small">Today's Daily Bakery Rescue</strong>
                                <span class="text-success fw-bold">Snack Box 40k • Meal Box 70k</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. EXPLORE FOOD CATEGORIES (Foodpanda / GrabFood style interactive grid) -->
<section class="py-5 bg-white">
    <div class="container-xl">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="text-success fw-bold text-uppercase small letter-spacing-1">Browse By Taste</span>
                <h2 class="fw-bold text-dark m-0">Explore Food Categories</h2>
            </div>
            <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-outline-success btn-sm rounded-pill px-3">
                View Full Menu <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-3 g-md-4">
            <?php foreach ($categories as $cat): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="<?= BASE_URL ?>/boxes.php?cat=<?= $cat['id'] ?>" class="text-decoration-none">
                        <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100 category-box-card bg-cream-light">
                            <div class="d-inline-flex p-3 rounded-circle bg-white text-success shadow-xs mx-auto mb-2 category-icon-wrap">
                                <i class="bi <?= htmlspecialchars($cat['icon'] ?? 'bi-cup-hot') ?> fs-3"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1 small"><?= htmlspecialchars($cat['name']) ?></h6>
                            <span class="text-muted" style="font-size: 0.75rem;">Explore &rarr;</span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 3. FEATURED PRODUCTS & DAILY SPECIALS (Top Products Grid with Live Add to Cart) -->
<section class="py-5" style="background-color: #FAF7F2;">
    <div class="container-xl">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <span class="text-success fw-bold text-uppercase small letter-spacing-1">Today's Hot Bakes</span>
                <h2 class="fw-bold text-dark m-0">Popular Food Boxes & Bakes</h2>
            </div>
            <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-outline-dark btn-sm rounded-pill px-3 mt-2 mt-md-0 d-none d-md-inline-flex">
                See All <?= count($featuredBoxes) ?>+ Items <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredBoxes as $box): 
                $savings = calculateSavingsPercent((float)$box['original_value'], (float)$box['price']);
                $isOutOfStock = ($box['available_qty'] <= 0);
            ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 bg-white product-food-card">
                        <div class="position-relative overflow-hidden" style="height: 220px; background-color: #121316;">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($box['image_url'] ?? 'assets/img/food_boxes/snack-box-1.jpg') ?>" 
                                 alt="<?= htmlspecialchars($box['name']) ?>" 
                                 class="w-100 h-100 object-fit-cover product-img" 
                                 loading="lazy">
                            
                            <span class="badge bg-dark bg-opacity-75 text-white position-absolute top-0 start-0 m-3 px-2 py-1 small rounded-2">
                                <?= htmlspecialchars($box['category_name'] ?? 'Bakery Special') ?>
                            </span>

                            <?php if ($savings > 0): ?>
                                <span class="badge bg-warning text-dark fw-bold position-absolute top-0 end-0 m-3 px-2 py-1 small rounded-2">
                                    -<?= $savings ?>%
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1 text-muted small">
                                    <span><i class="bi bi-shop text-success me-1"></i><?= htmlspecialchars($box['bakery_name']) ?></span>
                                    <span><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($box['district']) ?></span>
                                </div>

                                <h5 class="fw-bold text-dark mb-2">
                                    <a href="<?= BASE_URL ?>/box_details.php?id=<?= $box['id'] ?>" class="text-decoration-none text-dark hover-green">
                                        <?= htmlspecialchars($box['name']) ?>
                                    </a>
                                </h5>

                                <p class="text-muted small mb-3">
                                    <?= htmlspecialchars(mb_strimwidth($box['description'] ?? '', 0, 95, '...')) ?>
                                </p>
                            </div>

                            <div>
                                <!-- Stock indicator -->
                                <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded-3 small">
                                    <span class="text-muted"><i class="bi bi-box-seam me-1 text-success"></i> Stock Available:</span>
                                    <?php if ($isOutOfStock): ?>
                                        <span class="badge bg-secondary">Sold Out</span>
                                    <?php else: ?>
                                        <span class="badge <?= $box['available_qty'] <= 3 ? 'bg-danger' : 'bg-success' ?>">
                                            <?= (int)$box['available_qty'] ?> left today
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
                                                    title="Add 1 to Cart">
                                                <i class="bi bi-cart-plus"></i> Add
                                            </button>
                                            <a href="<?= BASE_URL ?>/box_details.php?id=<?= $box['id'] ?>" class="btn btn-nrn-primary btn-sm px-3">
                                                Order
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

        <div class="text-center mt-4 d-md-none">
            <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-outline-dark w-100 py-3 rounded-pill fw-bold">
                View Full Menu <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</section>

<!-- 4. MIDDLE PROMO BANNER (Cream & Dark Green Palette) -->
<section class="py-5 bg-white">
    <div class="container-xl">
        <div class="rounded-4 p-4 p-md-5 text-center text-white" style="background-color: #0F4C3A; background: linear-gradient(135deg, #0F4C3A 0%, #0B3B2D 100%);">
            <span class="badge bg-warning text-dark text-uppercase fw-bold px-3 py-1 mb-2">Sustainable Food Tech</span>
            <h3 class="display-6 fw-bold mb-3">Save Up to 60% On Fresh Artisan Bakes</h3>
            <p class="text-light-50 max-w-600 mx-auto mb-4 fs-6">
                Every box ordered prevents fresh food from going to waste while providing you with buttery croissants, crisp baguettes, and hearty sandwiches at guaranteed student prices.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-warning px-4 py-3 fw-bold rounded-pill text-dark">
                    <i class="bi bi-bag-check-fill me-1"></i> Order Food Boxes Now
                </a>
                <a href="<?= BASE_URL ?>/track.php" class="btn btn-outline-light px-4 py-3 fw-bold rounded-pill">
                    <i class="bi bi-radar me-1"></i> Live Order Tracking
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 5. HOW NOMRENOM WORKS (Simple Commercial 3-Step Process) -->
<section class="py-5" style="background-color: #FAF7F2;">
    <div class="container-xl">
        <div class="text-center max-w-700 mx-auto mb-4 mb-md-5">
            <span class="text-success fw-bold text-uppercase small letter-spacing-1">Order in 3 Simple Steps</span>
            <h2 class="fw-bold text-dark mb-2">How NomReNom Works</h2>
            <p class="text-muted small">Just like GrabFood or Foodpanda, with an eco-friendly twist that saves you money.</p>
        </div>

        <div class="row g-3 g-md-4">
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white text-center h-100">
                    <div class="d-inline-flex p-3 rounded-circle bg-success bg-opacity-10 text-success mx-auto mb-3" style="width: 60px; height: 60px; align-items: center; justify-content: center;">
                        <i class="bi bi-grid-fill fs-4"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">1. Browse & Add to Cart</h5>
                    <p class="text-muted small m-0">
                        Choose your favorite bakery surprise boxes, flaky croissants, and rustic loaves at 40,000 ₫ and 70,000 ₫.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white text-center h-100">
                    <div class="d-inline-flex p-3 rounded-circle bg-warning bg-opacity-10 text-warning mx-auto mb-3" style="width: 60px; height: 60px; align-items: center; justify-content: center;">
                        <i class="bi bi-credit-card-fill fs-4"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">2. Choose Fulfillment & Pay</h5>
                    <p class="text-muted small m-0">
                        Select Store Pickup or Campus Delivery. Pay seamlessly via MoMo, VNPay, Bank Transfer, or Cash upon receiving your food.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white text-center h-100">
                    <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-10 text-primary mx-auto mb-3" style="width: 60px; height: 60px; align-items: center; justify-content: center;">
                        <i class="bi bi-bicycle fs-4"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">3. Live Track & Enjoy</h5>
                    <p class="text-muted small m-0">
                        Watch your order move from Pending to Preparing to Ready for Pickup / Delivery in real time with your unique order number.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 6. PARTNER BAKERIES (Commercial Storefronts) -->
<section class="py-5 bg-white">
    <div class="container-xl">
        <div class="text-center max-w-700 mx-auto mb-4 mb-md-5">
            <span class="text-success fw-bold text-uppercase small letter-spacing-1">Verified Partners</span>
            <h2 class="fw-bold text-dark mb-2">Participating Artisan Bakeries</h2>
            <p class="text-muted small">Local craft bakeries partnering with NomReNom to bring you fresh bakes every single day.</p>
        </div>

        <div class="row g-3 g-md-4">
            <?php foreach ($partnerBakeries as $bakery): ?>
                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 bg-white">
                        <div class="p-4 text-center border-bottom bg-cream-light">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($bakery['logo_url'] ?? 'assets/img/bakeries/bakery-logo-1.svg') ?>" 
                                 alt="<?= htmlspecialchars($bakery['name']) ?>" 
                                 style="width: 76px; height: 76px;" 
                                 class="rounded-circle mb-3 shadow-sm object-fit-cover">
                            <h5 class="mb-1 text-dark fw-bold"><?= htmlspecialchars($bakery['name']) ?></h5>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Verified Bakery</span>
                        </div>
                        <div class="p-4">
                            <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($bakery['address']) ?>, <?= htmlspecialchars($bakery['district']) ?></p>
                            <p class="text-muted small mb-3"><i class="bi bi-clock-fill text-warning me-1"></i>Hours: <?= formatTime($bakery['opening_time']) ?> - <?= formatTime($bakery['closing_time']) ?></p>
                            <a href="<?= BASE_URL ?>/boxes.php?bakery=<?= $bakery['id'] ?>" class="btn btn-sm btn-outline-success w-100 py-2">
                                Browse Bakery Items <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Add to Cart AJAX Notification Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="cartToast" class="toast align-items-center text-white bg-success border-0 rounded-3 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <span id="toastMessage">Added to cart!</span>
            </div>
            <a href="<?= BASE_URL ?>/cart.php" class="btn btn-sm btn-light text-success fw-bold align-self-center me-2">View Cart</a>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toastEl = document.getElementById('cartToast');
    const toastMsg = document.getElementById('toastMessage');
    const bsToast = new bootstrap.Toast(toastEl, { delay: 3500 });

    document.querySelectorAll('.btn-quick-add').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const productName = this.getAttribute('data-name');
            const originalHtml = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            this.disabled = true;

            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('qty', 1);

            fetch('<?= BASE_URL ?>/api/cart.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                this.innerHTML = '<i class="bi bi-check"></i> Added';
                setTimeout(() => {
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                }, 1500);

                if (data.success) {
                    toastMsg.textContent = `Added "${productName}" to your cart!`;
                    bsToast.show();

                    // Update navbar cart badge
                    const navBadge = document.getElementById('nav-cart-badge');
                    if (navBadge) {
                        navBadge.textContent = data.cart_count;
                        navBadge.style.display = 'inline-block';
                    }
                } else {
                    alert(data.message || 'Error adding item to cart');
                }
            })
            .catch(err => {
                console.error(err);
                this.innerHTML = originalHtml;
                this.disabled = false;
            });
        });
    });
});
</script>

<style>
.bg-cream-light {
    background-color: #FAF7F2 !important;
}
.category-box-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.category-box-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 24px rgba(15, 76, 58, 0.08) !important;
}
.category-box-card:hover .category-icon-wrap {
    background-color: #0F4C3A !important;
    color: #FFFFFF !important;
}
.product-food-card {
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.product-food-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 32px rgba(0, 0, 0, 0.08) !important;
}
.product-img {
    transition: transform 0.4s ease;
}
.product-food-card:hover .product-img {
    transform: scale(1.05);
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
