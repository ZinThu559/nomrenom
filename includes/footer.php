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
                        A real-life commercial food ordering platform and surplus-food marketplace connecting communities and university students with local artisan bakeries.
                    </p>
                    <p class="fst-italic text-warning small mb-3">
                        "Good Food, Better Prices, Zero Waste"
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
                        <li><a href="<?= BASE_URL ?>/boxes.php"><i class="bi bi-chevron-right me-1"></i> Food Menu</a></li>
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
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
