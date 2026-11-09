<?php
/**
 * NomReNom - Commercial Food Shopping Cart Page
 * Customers can review items, change quantities, remove products, and proceed to checkout
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

// Handle POST actions if JavaScript is disabled or direct form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);

    if ($action === 'update' && $productId > 0) {
        $qty = (int)($_POST['qty'] ?? 1);
        $res = updateCartQty($productId, $qty);
        setFlash('success', $res['message']);
        header('Location: ' . BASE_URL . '/cart.php');
        exit;
    } elseif ($action === 'remove' && $productId > 0) {
        $res = removeFromCart($productId);
        setFlash('info', $res['message']);
        header('Location: ' . BASE_URL . '/cart.php');
        exit;
    } elseif ($action === 'clear') {
        clearCart();
        setFlash('info', 'Shopping cart cleared.');
        header('Location: ' . BASE_URL . '/cart.php');
        exit;
    }
}

$pageTitle = 'Shopping Cart – NomReNom';
$cartItems = getCart();
$cartTotal = getCartTotal();
$cartCount = getCartCount();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb & Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-decoration-none text-muted">Home</a></li>
                    <li class="breadcrumb-item active text-dark" aria-current="page">Shopping Cart</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-dark m-0 d-flex align-items-center gap-2">
                <i class="bi bi-cart3 text-success"></i> Your Food Cart
                <span class="badge bg-light text-dark border fs-6 fw-normal ms-2"><?= $cartCount ?> items</span>
            </h1>
        </div>
        <?php if (!empty($cartItems)): ?>
            <form method="POST" action="<?= BASE_URL ?>/cart.php" onsubmit="return confirm('Clear all items from your cart?');">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash3 me-1"></i> Clear Cart
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($cartItems)): ?>
        <!-- Empty Cart State -->
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4 bg-white">
            <div class="py-4">
                <div class="d-inline-flex p-4 rounded-circle bg-light text-success mb-3">
                    <i class="bi bi-cart-x fs-1"></i>
                </div>
                <h3 class="fw-bold text-dark mb-2">Your Food Cart is Empty</h3>
                <p class="text-muted max-w-500 mx-auto mb-4">
                    Explore our fresh daily bakery boxes starting at 40,000 ₫ and delicious handcrafted pastries!
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-nrn-primary px-4 py-2">
                        <i class="bi bi-grid me-2"></i> Browse Food Menu
                    </a>
                    <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-dark px-4 py-2">
                        Back to Homepage
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Left: Cart Items List -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-3" style="min-width: 260px;">Food Item</th>
                                    <th class="py-3 text-center" style="width: 140px;">Quantity</th>
                                    <th class="py-3 text-end" style="width: 130px;">Price</th>
                                    <th class="py-3 text-end" style="width: 140px;">Subtotal</th>
                                    <th class="pe-4 py-3 text-center" style="width: 60px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item): 
                                    $subtotal = $item['price'] * $item['qty'];
                                ?>
                                    <tr id="cart-row-<?= $item['id'] ?>">
                                        <td class="ps-4 py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($item['image_url'] ?? 'assets/img/food_boxes/snack-box-1.jpg') ?>" 
                                                     alt="<?= htmlspecialchars($item['name']) ?>" 
                                                     class="rounded-3 object-fit-cover shadow-xs" 
                                                     style="width: 68px; height: 68px;">
                                                <div>
                                                    <h6 class="mb-1 fw-bold text-dark">
                                                        <a href="<?= BASE_URL ?>/box_details.php?id=<?= $item['id'] ?>" class="text-decoration-none text-dark hover-green">
                                                            <?= htmlspecialchars($item['name']) ?>
                                                        </a>
                                                    </h6>
                                                    <div class="small text-muted">
                                                        <i class="bi bi-shop text-success me-1"></i><?= htmlspecialchars($item['bakery_name'] ?? 'NomReNom Partner') ?> 
                                                        • <?= htmlspecialchars($item['district'] ?? '') ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 text-center">
                                            <div class="input-group input-group-sm justify-content-center" style="max-width: 110px; margin: 0 auto;">
                                                <button class="btn btn-outline-secondary btn-qty-minus" type="button" data-id="<?= $item['id'] ?>">-</button>
                                                <input type="number" 
                                                       class="form-control text-center input-cart-qty" 
                                                       value="<?= (int)$item['qty'] ?>" 
                                                       min="1" 
                                                       max="<?= (int)($item['max_qty'] ?? 99) ?>"
                                                       data-id="<?= $item['id'] ?>"
                                                       style="max-width: 50px;">
                                                <button class="btn btn-outline-secondary btn-qty-plus" type="button" data-id="<?= $item['id'] ?>">+</button>
                                            </div>
                                        </td>
                                        <td class="py-3 text-end fw-semibold text-muted">
                                            <?= formatVnd($item['price']) ?>
                                        </td>
                                        <td class="py-3 text-end fw-bold text-dark fs-6 item-subtotal-display" id="subtotal-<?= $item['id'] ?>">
                                            <?= formatVnd($subtotal) ?>
                                        </td>
                                        <td class="pe-4 py-3 text-center">
                                            <form method="POST" action="<?= BASE_URL ?>/cart.php" class="d-inline">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn btn-link text-danger p-0" title="Remove item">
                                                    <i class="bi bi-trash3 fs-5"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Continual Shopping Link -->
                <div class="d-flex justify-content-between align-items-center">
                    <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Add More Food Items
                    </a>
                </div>
            </div>

            <!-- Right: Order Summary Card -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 90px;">
                    <h5 class="fw-bold text-dark mb-3">Order Summary</h5>
                    
                    <div class="d-flex justify-content-between text-muted mb-2">
                        <span>Items Subtotal (<?= $cartCount ?>)</span>
                        <span class="fw-semibold text-dark" id="summary-subtotal"><?= formatVnd($cartTotal) ?></span>
                    </div>

                    <div class="d-flex justify-content-between text-muted mb-2">
                        <span>Pickup Fee</span>
                        <span class="text-success fw-semibold">FREE</span>
                    </div>

                    <div class="d-flex justify-content-between text-muted mb-3">
                        <span>Eco-Packaging</span>
                        <span class="text-success fw-semibold">Included</span>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fw-bold text-dark fs-5">Total Amount</span>
                        <span class="fw-bold text-success fs-4" id="summary-total"><?= formatVnd($cartTotal) ?></span>
                    </div>

                    <a href="<?= BASE_URL ?>/checkout.php" class="btn btn-nrn-primary w-100 py-3 fs-5 fw-bold d-flex justify-content-center align-items-center gap-2 rounded-3 shadow-sm">
                        Proceed to Checkout <i class="bi bi-arrow-right"></i>
                    </a>

                    <!-- Safe Ordering Badges -->
                    <div class="mt-4 pt-3 border-top text-muted small">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-shield-check text-success fs-5"></i>
                            <span>Secure checkout with instant order confirmation</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-clock-history text-warning fs-5"></i>
                            <span>Freshly prepared and packed for pickup</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-cash-stack text-primary fs-5"></i>
                            <span>Supports MoMo, VNPay, Bank Transfer, or Cash</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Interactive Cart Quantity Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateCartAjax(productId, qty) {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('product_id', productId);
        formData.append('qty', qty);

        fetch('<?= BASE_URL ?>/api/cart.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (qty <= 0) {
                    const row = document.getElementById('cart-row-' + productId);
                    if (row) row.remove();
                } else {
                    const subtotalEl = document.getElementById('subtotal-' + productId);
                    if (subtotalEl) subtotalEl.textContent = data.item_subtotal;
                }
                const sumSubtotal = document.getElementById('summary-subtotal');
                const sumTotal = document.getElementById('summary-total');
                if (sumSubtotal) sumSubtotal.textContent = data.formatted_total;
                if (sumTotal) sumTotal.textContent = data.formatted_total;
                
                // Update nav badge if present
                const navBadge = document.getElementById('nav-cart-badge');
                if (navBadge) {
                    navBadge.textContent = data.cart_count;
                    navBadge.style.display = data.cart_count > 0 ? 'inline-block' : 'none';
                }

                if (data.cart_count === 0) {
                    window.location.reload();
                }
            } else {
                alert(data.message || 'Error updating cart');
            }
        })
        .catch(err => console.error(err));
    }

    document.querySelectorAll('.btn-qty-minus').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const input = document.querySelector(`.input-cart-qty[data-id="${id}"]`);
            let val = parseInt(input.value) - 1;
            if (val >= 1) {
                input.value = val;
                updateCartAjax(id, val);
            }
        });
    });

    document.querySelectorAll('.btn-qty-plus').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const input = document.querySelector(`.input-cart-qty[data-id="${id}"]`);
            const max = parseInt(input.getAttribute('max')) || 99;
            let val = parseInt(input.value) + 1;
            if (val <= max) {
                input.value = val;
                updateCartAjax(id, val);
            }
        });
    });

    document.querySelectorAll('.input-cart-qty').forEach(input => {
        input.addEventListener('change', function() {
            const id = this.getAttribute('data-id');
            let val = parseInt(this.value);
            const max = parseInt(this.getAttribute('max')) || 99;
            if (isNaN(val) || val < 1) val = 1;
            if (val > max) val = max;
            this.value = val;
            updateCartAjax(id, val);
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
