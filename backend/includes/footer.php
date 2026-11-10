<?php
/**
 * NomReNom - Customer Footer Component & Mobile Bottom Navigation Bar
 */

$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
?>
    </main>

    <!-- Mobile Bottom Navigation Bar (Visible only on Mobile & Small Tablets <= 768px) -->
    <nav class="mobile-bottom-nav">
        <a href="<?= BASE_URL ?>/index.php" class="mobile-nav-item <?= $currentScript === 'index.php' ? 'active' : '' ?>">
            <i class="bi <?= $currentScript === 'index.php' ? 'bi-house-door-fill' : 'bi-house-door' ?>"></i>
            <span>Home</span>
        </a>
        <a href="<?= BASE_URL ?>/boxes.php" class="mobile-nav-item <?= ($currentScript === 'boxes.php' || $currentScript === 'box_details.php') ? 'active' : '' ?>">
            <i class="bi <?= ($currentScript === 'boxes.php' || $currentScript === 'box_details.php') ? 'bi-grid-fill' : 'bi-grid' ?>"></i>
            <span>Menu</span>
        </a>
        <a href="<?= BASE_URL ?>/cart.php" class="mobile-nav-item <?= $currentScript === 'cart.php' ? 'active' : '' ?> position-relative">
            <i class="bi <?= $currentScript === 'cart.php' ? 'bi-cart-fill' : 'bi-cart' ?>"></i>
            <span>Cart</span>
            <?php $mCartCount = function_exists('getCartCount') ? getCartCount() : 0; ?>
            <?php if ($mCartCount > 0): ?>
                <span class="position-absolute top-0 end-0 badge rounded-pill bg-danger" style="font-size: 0.65rem; transform: translate(-30%, 20%);"><?= $mCartCount ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/track.php" class="mobile-nav-item <?= ($currentScript === 'track.php' || $currentScript === 'reservation_lookup.php') ? 'active' : '' ?>">
            <i class="bi <?= ($currentScript === 'track.php' || $currentScript === 'reservation_lookup.php') ? 'bi-radar' : 'bi-geo-alt' ?>"></i>
            <span>Track</span>
        </a>
        <?php if (isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>/profile.php" class="mobile-nav-item <?= $currentScript === 'profile.php' ? 'active' : '' ?>">
                <i class="bi <?= $currentScript === 'profile.php' ? 'bi-person-circle' : 'bi-person' ?>"></i>
                <span>Account</span>
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/login.php" class="mobile-nav-item <?= ($currentScript === 'login.php' || $currentScript === 'register.php') ? 'active' : '' ?>">
                <i class="bi <?= $currentScript === 'login.php' ? 'bi-person-fill-lock' : 'bi-person-lock' ?>"></i>
                <span>Sign In</span>
            </a>
        <?php endif; ?>
    </nav>

    <footer class="footer-nomrenom">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-basket2-fill text-warning fs-3"></i>
                        <h4 class="text-white m-0 fw-bold">NomReNom</h4>
                    </div>
                    <p class="text-light-50 small mb-3">
                        A pickup marketplace for near-expiry and damaged snacks (dented packs, crumbled biscuits, melted-and-set chocolate). Reserve by VietQR, inspect at partner store counters, and take home with zero shipping!
                    </p>
                    <p class="fst-italic text-warning small mb-3">
                        "Inspect at Counter • Zero Shipping • Full Money-Back Guarantee"
                    </p>
                    <div class="d-flex gap-3 fs-5">
                        <a href="#" class="text-light" title="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-light" title="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-light" title="TikTok"><i class="bi bi-tiktok"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6 col-6">
                    <h5>Quick Menu</h5>
                    <ul class="list-unstyled d-flex flex-column gap-2 small">
                        <li><a href="<?= BASE_URL ?>/boxes.php"><i class="bi bi-chevron-right me-1"></i> Snacks Marketplace</a></li>
                        <li><a href="<?= BASE_URL ?>/cart.php"><i class="bi bi-chevron-right me-1"></i> Shopping Cart</a></li>
                        <li><a href="<?= BASE_URL ?>/track.php"><i class="bi bi-chevron-right me-1"></i> Live Order Tracking</a></li>
                        <li><a href="<?= BASE_URL ?>/how_it_works.php"><i class="bi bi-chevron-right me-1"></i> How It Works</a></li>
                        <li><a href="<?= BASE_URL ?>/about.php"><i class="bi bi-chevron-right me-1"></i> About Us</a></li>
                        <li><a href="<?= BASE_URL ?>/feedback.php"><i class="bi bi-chevron-right me-1"></i> Customer Reviews</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6 col-6">
                    <h5>Policies & Trust</h5>
                    <ul class="list-unstyled d-flex flex-column gap-2 small">
                        <li><a href="<?= BASE_URL ?>/terms.php"><i class="bi bi-shield-check me-1"></i> Terms of Service</a></li>
                        <li><a href="<?= BASE_URL ?>/privacy.php"><i class="bi bi-lock me-1"></i> Privacy Policy</a></li>
                        <li><a href="<?= BASE_URL ?>/terms.php#food-safety"><i class="bi bi-check2-circle me-1"></i> Food Safety Standards</a></li>
                        <li><a href="<?= BASE_URL ?>/terms.php#cancellation"><i class="bi bi-arrow-repeat me-1"></i> Cancellation Policy</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h5>Customer Support</h5>
                    <p class="small text-light-50 mb-2">
                        <i class="bi bi-geo-alt-fill text-warning me-2"></i> Ho Chi Minh City, Vietnam
                    </p>
                    <p class="small text-light-50 mb-2">
                        <i class="bi bi-envelope-fill text-warning me-2"></i> support@nomrenom.vn
                    </p>
                    <p class="small text-light-50 mb-0">
                        <i class="bi bi-telephone-fill text-warning me-2"></i> 0901 234 567 (8:00 AM – 9:30 PM Daily)
                    </p>
                </div>
            </div>

            <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <p class="m-0 text-center text-md-start small text-light-50">
                    &copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong> – Online Food Ordering Platform. All rights reserved.
                </p>
                <div class="d-flex gap-3 small">
                    <a href="<?= BASE_URL ?>/terms.php">Terms</a>
                    <span>•</span>
                    <a href="<?= BASE_URL ?>/privacy.php">Privacy</a>
                    <span>•</span>
                    <a href="<?= BASE_URL ?>/admin/login.php" class="text-warning"><i class="bi bi-shield-lock-fill me-1"></i>Admin Portal</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/main.js?v=<?= time() ?>"></script>
    
    <!-- Direct Robust Cart Handlers (Guaranteed inline execution) -->
    <script>
    window.nomrenomAddToCart = function(event, btn) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        if (!btn) return;
        var productId = btn.getAttribute('data-id');
        var productName = btn.getAttribute('data-name') || 'Snack Item';
        var originalHtml = btn.getAttribute('data-orig-html') || btn.innerHTML;
        btn.setAttribute('data-orig-html', originalHtml);

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Adding...';

        var baseUrl = '<?= BASE_URL ?>';
        var formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('qty', 1);

        fetch(baseUrl + '/api/cart.php', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data && data.success) {
                // Update nav badge counters
                var badges = document.querySelectorAll('#nav-cart-badge, .nav-cart-badge, .mobile-bottom-nav .badge');
                badges.forEach(function(b) {
                    b.textContent = data.cart_count;
                    b.style.display = data.cart_count > 0 ? 'inline-block' : 'none';
                });

                btn.classList.remove('btn-outline-success');
                btn.classList.add('btn-success', 'text-white');
                btn.innerHTML = '<i class="bi bi-check2"></i> Added!';

                // Floating Toast
                var container = document.getElementById('cart-toast-container');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'cart-toast-container';
                    container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
                    document.body.appendChild(container);
                }
                var toast = document.createElement('div');
                toast.className = 'cart-toast shadow-lg rounded-4 p-3 d-flex align-items-center gap-3 bg-white border border-success';
                toast.style.cssText = 'pointer-events:auto;min-width:320px;max-width:420px;box-shadow:0 12px 32px rgba(15,76,58,0.18);transition:all 0.3s cubic-bezier(0.16,1,0.3,1);transform:translateY(20px);opacity:0;';
                toast.innerHTML = '<div class="p-2 rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;"><i class="bi bi-cart-check-fill fs-5"></i></div><div class="flex-grow-1" style="min-width:0;"><strong class="d-block text-dark small fw-bold mb-0">Added to Cart! 🛒</strong><span class="text-muted small d-block text-truncate">' + productName + '</span></div><a href="' + baseUrl + '/cart.php" class="btn btn-sm btn-nrn-primary rounded-pill px-3 py-1 text-nowrap fw-bold">View Cart</a>';
                container.appendChild(toast);
                requestAnimationFrame(function() {
                    toast.style.transform = 'translateY(0)';
                    toast.style.opacity = '1';
                });
                setTimeout(function() {
                    toast.style.transform = 'translateY(20px)';
                    toast.style.opacity = '0';
                    setTimeout(function() { toast.remove(); }, 300);
                }, 4000);

                setTimeout(function() {
                    btn.classList.remove('btn-success', 'text-white');
                    btn.classList.add('btn-outline-success');
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }, 1600);
            } else {
                alert((data && data.message) ? data.message : 'Could not add snack to cart.');
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        })
        .catch(function(err) {
            console.error('Cart add error:', err);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            alert('Could not connect to server. Please try again.');
        });
    };

    window.nomrenomAddDetailToCart = function(event, btn) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        if (!btn) return;
        var productId = btn.getAttribute('data-id');
        var productName = btn.getAttribute('data-name') || 'Snack Item';
        var qtyInput = document.getElementById('reservationQty');
        var qty = qtyInput ? (parseInt(qtyInput.value, 10) || 1) : 1;
        var originalHtml = btn.getAttribute('data-orig-html') || btn.innerHTML;
        btn.setAttribute('data-orig-html', originalHtml);

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Adding to Cart...';

        var baseUrl = '<?= BASE_URL ?>';
        var formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('qty', qty);

        fetch(baseUrl + '/api/cart.php', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data && data.success) {
                var badges = document.querySelectorAll('#nav-cart-badge, .nav-cart-badge, .mobile-bottom-nav .badge');
                badges.forEach(function(b) {
                    b.textContent = data.cart_count;
                    b.style.display = data.cart_count > 0 ? 'inline-block' : 'none';
                });

                btn.classList.remove('btn-outline-success');
                btn.classList.add('btn-success', 'text-white');
                btn.innerHTML = '<i class="bi bi-check2-circle me-2"></i> Added ' + qty + 'x to Cart!';

                // Floating Toast
                var container = document.getElementById('cart-toast-container');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'cart-toast-container';
                    container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
                    document.body.appendChild(container);
                }
                var toast = document.createElement('div');
                toast.className = 'cart-toast shadow-lg rounded-4 p-3 d-flex align-items-center gap-3 bg-white border border-success';
                toast.style.cssText = 'pointer-events:auto;min-width:320px;max-width:420px;box-shadow:0 12px 32px rgba(15,76,58,0.18);transition:all 0.3s cubic-bezier(0.16,1,0.3,1);transform:translateY(20px);opacity:0;';
                toast.innerHTML = '<div class="p-2 rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;"><i class="bi bi-cart-check-fill fs-5"></i></div><div class="flex-grow-1" style="min-width:0;"><strong class="d-block text-dark small fw-bold mb-0">Added ' + qty + 'x to Cart! 🛒</strong><span class="text-muted small d-block text-truncate">' + productName + '</span></div><a href="' + baseUrl + '/cart.php" class="btn btn-sm btn-nrn-primary rounded-pill px-3 py-1 text-nowrap fw-bold">View Cart</a>';
                container.appendChild(toast);
                requestAnimationFrame(function() {
                    toast.style.transform = 'translateY(0)';
                    toast.style.opacity = '1';
                });
                setTimeout(function() {
                    toast.style.transform = 'translateY(20px)';
                    toast.style.opacity = '0';
                    setTimeout(function() { toast.remove(); }, 300);
                }, 4000);

                setTimeout(function() {
                    btn.classList.remove('btn-success', 'text-white');
                    btn.classList.add('btn-outline-success');
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }, 1800);
            } else {
                alert((data && data.message) ? data.message : 'Could not add to cart.');
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        })
        .catch(function(err) {
            console.error('Detail add to cart error:', err);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            alert('Could not connect to server. Please try again.');
        });
    };
    </script>
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
