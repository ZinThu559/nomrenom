<?php
/**
 * NomReNom - Near-Expiry & Imperfect Snacks Catalog
 * Browse real photos, condition grades, expiry dates, and reserve snacks for store pickup
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$pageTitle = 'Browse Near-Expiry & Imperfect Snacks – NomReNom';
$pdo = getDbConnection();

// Filters
$search = clean($_GET['q'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);
$gradeFilter = clean($_GET['grade'] ?? '');
$bakeryFilter = (int)($_GET['bakery'] ?? 0);
$districtFilter = clean($_GET['district'] ?? '');

// Fetch active categories
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

// Fetch partner stores
$bakeries = $pdo->query("SELECT id, name FROM bakeries WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
$districts = $pdo->query("SELECT DISTINCT district FROM bakeries WHERE is_active = 1 ORDER BY district ASC")->fetchAll(PDO::FETCH_COLUMN);

// Build dynamic query
$sql = "SELECT f.*, b.name AS bakery_name, b.district, b.city, b.address, c.name AS category_name, c.icon AS category_icon 
        FROM food_boxes f 
        JOIN bakeries b ON f.bakery_id = b.id 
        LEFT JOIN categories c ON f.category_id = c.id 
        WHERE f.is_active = 1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (f.name LIKE ? OR f.description LIKE ? OR f.defect_notes LIKE ? OR b.name LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($catFilter > 0) {
    $sql .= " AND f.category_id = ?";
    $params[] = $catFilter;
}

if (!empty($gradeFilter)) {
    $sql .= " AND f.condition_grade = ?";
    $params[] = $gradeFilter;
}

if ($bakeryFilter > 0) {
    $sql .= " AND f.bakery_id = ?";
    $params[] = $bakeryFilter;
}

if (!empty($districtFilter)) {
    $sql .= " AND b.district = ?";
    $params[] = $districtFilter;
}

$sql .= " ORDER BY f.available_qty DESC, f.id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$foodProducts = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4 bg-white">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-inline-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1 fw-bold">
                        <i class="bi bi-shield-check me-1"></i> Store Pickup Only • Zero Shipping
                    </span>
                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning px-3 py-1 fw-bold">
                        Real Photo Guarantee
                    </span>
                </div>
                <h1 class="h2 mb-2 fw-bold text-dark">Near-Expiry &amp; Imperfect Snacks</h1>
                <p class="text-muted m-0">
                    Dented boxes, crumbled biscuits, and melted-and-set chocolates at 50% to 70% off. 
                    Inspect your items in person at the store counter before taking them home. 
                    <strong>If anything does not look like the photo, you get an instant refund.</strong>
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <div class="d-inline-flex flex-column align-items-md-end">
                    <span class="badge bg-light text-dark border p-2 px-3 fs-6 mb-1">
                        <i class="bi bi-clock-history text-success me-1"></i> 7 to 30 Days Left
                    </span>
                    <small class="text-muted"><i class="bi bi-x-circle text-danger me-1"></i> Nothing expired, ever</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Condition Grade Quick Filters -->
    <div class="d-flex gap-2 overflow-auto text-nowrap pb-2 mb-3">
        <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-sm <?= empty($gradeFilter) ? 'btn-dark' : 'btn-light border' ?> rounded-pill px-3 py-2 fw-semibold">
            All Conditions
        </a>
        <a href="<?= BASE_URL ?>/boxes.php?grade=dented_box" class="btn btn-sm <?= $gradeFilter === 'dented_box' ? 'btn-dark' : 'btn-light border' ?> rounded-pill px-3 py-2 fw-semibold">
            📦 Dented Packaging
        </a>
        <a href="<?= BASE_URL ?>/boxes.php?grade=crumbled_biscuits" class="btn btn-sm <?= $gradeFilter === 'crumbled_biscuits' ? 'btn-dark' : 'btn-light border' ?> rounded-pill px-3 py-2 fw-semibold">
            🍪 Crumbled / Broken Shapes
        </a>
        <a href="<?= BASE_URL ?>/boxes.php?grade=melted_reset" class="btn btn-sm <?= $gradeFilter === 'melted_reset' ? 'btn-dark' : 'btn-light border' ?> rounded-pill px-3 py-2 fw-semibold">
            🍫 Melted &amp; Re-set
        </a>
        <a href="<?= BASE_URL ?>/boxes.php?grade=near_expiry" class="btn btn-sm <?= $gradeFilter === 'near_expiry' ? 'btn-dark' : 'btn-light border' ?> rounded-pill px-3 py-2 fw-semibold">
            ⏳ Near-Expiry (7–30 Days)
        </a>
    </div>

    <!-- Category Filter Strip (Mobile Touch Scroller + Laptop 7-Col Grid) -->
    <div class="mb-4">
        <div class="category-strip-container">
            <a href="<?= BASE_URL ?>/boxes.php<?= !empty($gradeFilter) ? '?grade=' . urlencode($gradeFilter) : '' ?>" class="category-card-pill <?= $catFilter === 0 && empty($search) ? 'active' : '' ?>">
                <div class="category-card-icon" style="background: #F0FDF4; border: 1px solid #DCFCE7;">🏷️</div>
                <span class="category-card-label">All Deals</span>
                <span class="category-card-subtitle badge rounded-pill bg-success bg-opacity-10 text-success">Up to 70% Off</span>
            </a>
            <a href="<?= BASE_URL ?>/boxes.php?cat=1<?= !empty($gradeFilter) ? '&grade=' . urlencode($gradeFilter) : '' ?>" class="category-card-pill <?= $catFilter === 1 ? 'active' : '' ?>">
                <div class="category-card-icon" style="background: #FFFBEB; border: 1px solid #FEF3C7;">🍪</div>
                <span class="category-card-label">Biscuits</span>
                <span class="category-card-subtitle text-muted">From 12.000 ₫</span>
            </a>
            <a href="<?= BASE_URL ?>/boxes.php?cat=2<?= !empty($gradeFilter) ? '&grade=' . urlencode($gradeFilter) : '' ?>" class="category-card-pill <?= $catFilter === 2 ? 'active' : '' ?>">
                <div class="category-card-icon" style="background: #FDF2F8; border: 1px solid #FCE7F3;">🍫</div>
                <span class="category-card-label">Chocolates</span>
                <span class="category-card-subtitle text-muted">Melted &amp; Reset</span>
            </a>
            <a href="<?= BASE_URL ?>/boxes.php?cat=3<?= !empty($gradeFilter) ? '&grade=' . urlencode($gradeFilter) : '' ?>" class="category-card-pill <?= $catFilter === 3 ? 'active' : '' ?>">
                <div class="category-card-icon" style="background: #EFF6FF; border: 1px solid #DBEAFE;">🥨</div>
                <span class="category-card-label">Chips</span>
                <span class="category-card-subtitle text-muted">Dented Box</span>
            </a>
            <a href="<?= BASE_URL ?>/boxes.php?q=ramyun<?= !empty($gradeFilter) ? '&grade=' . urlencode($gradeFilter) : '' ?>" class="category-card-pill <?= strtolower($search) === 'ramyun' ? 'active' : '' ?>">
                <div class="category-card-icon" style="background: #FFF1F2; border: 1px solid #FFE4E6;">🍜</div>
                <span class="category-card-label">Ramyun</span>
                <span class="category-card-subtitle text-muted">Near Expiry</span>
            </a>
            <a href="<?= BASE_URL ?>/boxes.php?q=seaweed<?= !empty($gradeFilter) ? '&grade=' . urlencode($gradeFilter) : '' ?>" class="category-card-pill <?= strtolower($search) === 'seaweed' ? 'active' : '' ?>">
                <div class="category-card-icon" style="background: #F0FDF4; border: 1px solid #DCFCE7;">🍙</div>
                <span class="category-card-label">Seaweed</span>
                <span class="category-card-subtitle text-muted">Crispy Sheets</span>
            </a>
            <a href="<?= BASE_URL ?>/boxes.php?cat=5<?= !empty($gradeFilter) ? '&grade=' . urlencode($gradeFilter) : '' ?>" class="category-card-pill <?= $catFilter === 5 ? 'active' : '' ?>">
                <div class="category-card-icon" style="background: #FEF3C7; border: 1px solid #FDE68A;">🍞</div>
                <span class="category-card-label">Breads</span>
                <span class="category-card-subtitle text-muted">Bakery Items</span>
            </a>
        </div>
    </div>

    <!-- Search & Filter Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <form action="<?= BASE_URL ?>/boxes.php" method="GET" class="row g-2 align-items-center">
            <?php if ($catFilter > 0): ?>
                <input type="hidden" name="cat" value="<?= $catFilter ?>">
            <?php endif; ?>
            <?php if (!empty($gradeFilter)): ?>
                <input type="hidden" name="grade" value="<?= htmlspecialchars($gradeFilter) ?>">
            <?php endif; ?>

            <div class="col-lg-5 col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control border-start-0" placeholder="Search Pocky, Oreo, KitKat, chips...">
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-6">
                <select name="bakery" class="form-select">
                    <option value="">All Partner Stores</option>
                    <?php foreach ($bakeries as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $bakeryFilter == $b['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <select name="district" class="form-select">
                    <option value="">All Locations</option>
                    <?php foreach ($districts as $d): ?>
                        <option value="<?= htmlspecialchars($d) ?>" <?= $districtFilter === $d ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-12">
                <button type="submit" class="btn btn-nrn-primary w-100 fw-bold">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Active Filter Indicators -->
    <?php if (!empty($search) || $catFilter > 0 || !empty($gradeFilter) || $bakeryFilter > 0 || !empty($districtFilter)): ?>
        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap small">
            <span class="text-muted">Active filters:</span>
            <?php if (!empty($search)): ?>
                <span class="badge bg-light text-dark border">Keyword: "<?= htmlspecialchars($search) ?>"</span>
            <?php endif; ?>
            <?php if (!empty($gradeFilter)): ?>
                <span class="badge bg-light text-dark border">Grade: <?= getConditionGradeName($gradeFilter) ?></span>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/boxes.php" class="text-danger text-decoration-none ms-2"><i class="bi bi-x-circle"></i> Clear all</a>
        </div>
    <?php endif; ?>

    <!-- Product Grid -->
    <?php if (empty($foodProducts)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white my-4">
            <div class="mb-3 text-muted">
                <i class="bi bi-search fs-1"></i>
            </div>
            <h4 class="fw-bold text-dark">No Snacks Found</h4>
            <p class="text-muted max-w-500 mx-auto mb-4">
                We couldn't find any snacks matching your filters. Try clearing your search or choosing another category.
            </p>
            <div>
                <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-nrn-primary px-4 py-2 rounded-pill">
                    Reset All Filters
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($foodProducts as $product): 
                $savings = calculateSavingsPercent((float)$product['original_value'], (float)$product['price']);
                $isOutOfStock = ($product['available_qty'] <= 0);
                $exp = getExpiryCountdown($product['expiry_date'] ?? '2026-11-28');
            ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 bg-white product-food-card">
                        <!-- Product Image Area -->
                        <div class="position-relative overflow-hidden" style="height: 200px; background-color: #F8FAFC;">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($product['image_url'] ?? 'assets/img/food_boxes/snack-box-1.jpg') ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>" 
                                 class="w-100 h-100 object-fit-cover product-img" 
                                 loading="lazy">
                            
                            <div class="position-absolute top-0 start-0 m-2">
                                <?= getConditionGradeBadge($product['condition_grade'] ?? 'dented_box') ?>
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
                                    <span class="text-truncate" style="max-width: 60%;"><i class="bi bi-shop text-success me-1"></i><?= htmlspecialchars($product['bakery_name']) ?></span>
                                    <span><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($product['district']) ?></span>
                                </div>

                                <h6 class="fw-bold text-dark mb-1">
                                    <a href="<?= BASE_URL ?>/box_details.php?id=<?= $product['id'] ?>" class="text-decoration-none text-dark hover-green">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </a>
                                </h6>

                                <!-- Defect notes -->
                                <p class="text-muted small mb-2" style="font-size: 0.8rem; line-height: 1.4;">
                                    <i class="bi bi-info-circle text-primary me-1"></i><?= htmlspecialchars(mb_strimwidth($product['defect_notes'] ?? $product['description'], 0, 95, '...')) ?>
                                </p>
                            </div>

                            <div>
                                <!-- Stock Available -->
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded-2 small" style="font-size: 0.78rem;">
                                    <span class="text-muted"><i class="bi bi-box-seam me-1 text-success"></i> Stock:</span>
                                    <?php if ($isOutOfStock): ?>
                                        <span class="badge bg-secondary">Sold Out</span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                            <?= (int)$product['available_qty'] ?> packs available
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Price & Action Buttons -->
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <div>
                                        <div class="fw-bold text-dark fs-5"><?= formatVnd($product['price']) ?></div>
                                        <?php if ((float)$product['original_value'] > (float)$product['price']): ?>
                                            <small class="text-muted text-decoration-line-through"><?= formatVnd($product['original_value']) ?></small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <?php if ($isOutOfStock): ?>
                                            <button class="btn btn-secondary btn-sm px-3" disabled>Sold Out</button>
                                        <?php else: ?>
                                            <button type="button" 
                                                    class="btn btn-outline-success btn-sm btn-quick-add" 
                                                    data-id="<?= $product['id'] ?>"
                                                    data-name="<?= htmlspecialchars($product['name']) ?>"
                                                    onclick="nomrenomAddToCart(event, this)"
                                                    title="Add to Cart">
                                                <i class="bi bi-cart-plus"></i> Add
                                            </button>
                                            <a href="<?= BASE_URL ?>/box_details.php?id=<?= $product['id'] ?>" class="btn btn-nrn-primary btn-sm px-3">
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
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
