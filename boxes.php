<?php
/**
 * NomReNom - Commercial Food Menu Catalog
 * Browse all food categories, view food photos and prices, search products, add directly to cart
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$pageTitle = 'Food Menu & Daily Bakery Specials – NomReNom';
$pdo = getDbConnection();

// Filters
$search = clean($_GET['q'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);
$typeFilter = clean($_GET['type'] ?? '');
$bakeryFilter = (int)($_GET['bakery'] ?? 0);
$districtFilter = clean($_GET['district'] ?? '');

// Fetch active categories
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

// Fetch bakeries and districts
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
    $sql .= " AND (f.name LIKE ? OR f.description LIKE ? OR b.name LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($catFilter > 0) {
    $sql .= " AND f.category_id = ?";
    $params[] = $catFilter;
}

if (!empty($typeFilter)) {
    $sql .= " AND f.box_type = ?";
    $params[] = $typeFilter;
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
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 fw-bold mb-2">
                    <i class="bi bi-basket2-fill me-1"></i> Fresh Food Menu
                </span>
                <h1 class="h2 mb-2 fw-bold text-dark">Fresh Daily Bakes & Food Boxes</h1>
                <p class="text-muted m-0">
                    Order handcrafted pastries, wholesome meal boxes, and fresh sourdough loaves. Standard flat prices: 
                    <strong>Snack Box 40,000 ₫</strong> and <strong>Meal Box 70,000 ₫</strong>.
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <div class="d-inline-flex flex-column align-items-md-end">
                    <span class="badge bg-light text-dark border p-2 px-3 fs-6 mb-1">
                        <i class="bi bi-clock-history text-success me-1"></i> Ready for Pickup / Delivery
                    </span>
                    <small class="text-muted"><i class="bi bi-shield-check text-success"></i> 100% Quality Freshness Guarantee</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Filter Tabs / Pills -->
    <div class="category-tabs-bar mb-4 pb-2 d-flex gap-2 overflow-auto text-nowrap">
        <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-sm <?= ($catFilter === 0 && empty($typeFilter)) ? 'btn-success' : 'btn-light border' ?> rounded-pill px-3 py-2 fw-semibold">
            <i class="bi bi-grid-fill me-1"></i> All Items
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="<?= BASE_URL ?>/boxes.php?cat=<?= $cat['id'] ?>" class="btn btn-sm <?= $catFilter === (int)$cat['id'] ? 'btn-success' : 'btn-light border' ?> rounded-pill px-3 py-2 fw-semibold">
                <i class="bi <?= htmlspecialchars($cat['icon'] ?? 'bi-cup-hot') ?> me-1"></i> <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Search & Filter Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <form action="<?= BASE_URL ?>/boxes.php" method="GET" class="row g-2 align-items-center">
            <?php if ($catFilter > 0): ?>
                <input type="hidden" name="cat" value="<?= $catFilter ?>">
            <?php endif; ?>

            <div class="col-lg-5 col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control border-start-0" placeholder="Search croissant, sourdough, sandwich, bakery...">
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-6">
                <select name="bakery" class="form-select">
                    <option value="">All Partner Bakeries</option>
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
            <div class="col-lg-2 col-md-12 d-flex gap-2">
                <button type="submit" class="btn btn-nrn-primary w-100 py-2">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <?php if (!empty($search) || $catFilter > 0 || $bakeryFilter > 0 || !empty($districtFilter)): ?>
                    <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-outline-secondary py-2" title="Reset Filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Product Grid -->
    <div class="row g-4">
        <?php if (empty($foodProducts)): ?>
            <div class="col-12 py-5 text-center">
                <div class="card border-0 shadow-sm rounded-4 p-5 bg-white max-w-500 mx-auto">
                    <i class="bi bi-basket3 fs-1 text-muted mb-3"></i>
                    <h4 class="fw-bold text-dark">No matching food items found</h4>
                    <p class="text-muted small">Try modifying your search keywords or resetting filters.</p>
                    <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-nrn-primary mt-2">View Full Menu</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($foodProducts as $product): 
                $savings = calculateSavingsPercent((float)$product['original_value'], (float)$product['price']);
                $isOutOfStock = ($product['available_qty'] <= 0);
            ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 bg-white product-catalog-card <?= $isOutOfStock ? 'opacity-75' : '' ?>">
                        <div class="position-relative overflow-hidden" style="height: 220px; background-color: #121316;">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($product['image_url'] ?? 'assets/img/food_boxes/snack-box-1.jpg') ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>" 
                                 class="w-100 h-100 object-fit-cover product-img" 
                                 loading="lazy">
                            
                            <!-- Badges -->
                            <span class="badge bg-dark bg-opacity-75 text-white position-absolute top-0 start-0 m-3 px-2 py-1 small rounded-2">
                                <?= htmlspecialchars($product['category_name'] ?? 'Bakery Special') ?>
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
                                    <span><i class="bi bi-shop text-success me-1"></i><?= htmlspecialchars($product['bakery_name']) ?></span>
                                    <span><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($product['district']) ?></span>
                                </div>

                                <h5 class="fw-bold text-dark mb-2">
                                    <a href="<?= BASE_URL ?>/box_details.php?id=<?= $product['id'] ?>" class="text-decoration-none text-dark hover-green">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </a>
                                </h5>

                                <p class="text-muted small mb-3">
                                    <?= htmlspecialchars(mb_strimwidth($product['description'] ?? '', 0, 95, '...')) ?>
                                </p>
                            </div>

                            <div>
                                <!-- Stock indicator -->
                                <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded-3 small">
                                    <span class="text-muted"><i class="bi bi-box-seam me-1 text-success"></i> Stock Available:</span>
                                    <?php if ($isOutOfStock): ?>
                                        <span class="badge bg-secondary">Sold Out Today</span>
                                    <?php else: ?>
                                        <span class="badge <?= $product['available_qty'] <= 3 ? 'bg-danger' : 'bg-success' ?>">
                                            <?= (int)$product['available_qty'] ?> left today
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
                                                    title="Add 1 to Cart">
                                                <i class="bi bi-cart-plus"></i> Add
                                            </button>
                                            <a href="<?= BASE_URL ?>/box_details.php?id=<?= $product['id'] ?>" class="btn btn-nrn-primary btn-sm px-3">
                                                Details
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add to Cart AJAX Script with Visual Toast Notification -->
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

                    // Update navbar badge
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
.product-catalog-card {
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.product-catalog-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08) !important;
}
.product-img {
    transition: transform 0.4s ease;
}
.product-catalog-card:hover .product-img {
    transform: scale(1.05);
}
.category-tabs-bar::-webkit-scrollbar {
    height: 4px;
}
.category-tabs-bar::-webkit-scrollbar-thumb {
    background-color: #CBD5E1;
    border-radius: 4px;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
