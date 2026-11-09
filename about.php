<?php
/**
 * NomReNom - About Us (Commercial Food Ordering Platform)
 * Features Company Story, Core Values, and Exactly Four Team Member Profiles with Editable Placeholders
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'About Us – NomReNom Food Ordering';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Commercial Brand Hero -->
<div class="py-5 mb-5 border-bottom" style="background: linear-gradient(180deg, #FAF7F2 0%, #FFFFFF 100%);">
    <div class="container text-center">
        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 text-uppercase fw-bold mb-3">
            <i class="bi bi-basket2-fill me-1"></i> About NomReNom
        </span>
        <h1 class="display-4 fw-bold text-dark mb-3">Good Food, Better Prices, Zero Waste</h1>
        <p class="fs-5 text-muted max-w-700 mx-auto">
            NomReNom is a sustainable food ordering marketplace connecting everyday food lovers with premier local artisan bakeries. We make fresh, handcrafted pastries and hearty meal boxes accessible to everyone at pocket-friendly prices.
        </p>
    </div>
</div>

<div class="container pb-5">
    <!-- 1. OUR MISSION & HOW WE BEGAN -->
    <div class="row g-5 align-items-center mb-5">
        <div class="col-lg-6">
            <span class="text-uppercase fw-bold text-success small letter-spacing-1">Our Story & Purpose</span>
            <h2 class="fw-bold text-dark mb-3">Turning Bakery Surplus into Daily Celebrations</h2>
            <p class="text-muted">
                Every evening, skilled bakers pull trays of golden croissants, sourdough loaves, and sweet pastries from their ovens. Too often, unsold baked goods are discarded simply because they cannot be kept until the next morning — despite being baked fresh that very day.
            </p>
            <p class="text-muted">
                <strong>NomReNom</strong> changes that reality. We built an intuitive, on-demand food ordering platform similar to GrabFood and Foodpanda, tailored specifically for surplus food rescue. Customers can browse delicious bakery items, add them to their shopping cart, choose between 60-second counter pickup or convenient local delivery, and track their order progress in real time.
            </p>
            <div class="row g-3 mt-2">
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3 border">
                        <strong class="d-block text-dark fs-5">60% Off</strong>
                        <small class="text-muted">Average customer savings on artisan bakes</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3 border">
                        <strong class="d-block text-dark fs-5">Zero Waste</strong>
                        <small class="text-muted">Direct positive environmental impact</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white p-3">
                <img src="<?= BASE_URL ?>/assets/img/bakery_pastries_1793872097849.jpg" alt="Artisan Pastries at NomReNom" class="img-fluid rounded-3">
                <div class="p-3 text-center text-muted small fst-italic">
                    "Delicious artisan bakery food rescued fresh every day at fair, accessible student prices."
                </div>
            </div>
        </div>
    </div>

    <!-- 2. CORE VALUES -->
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-5">
        <div class="text-center max-w-700 mx-auto mb-4">
            <h3 class="fw-bold text-dark mb-2">Our Operating Pillars</h3>
            <p class="text-muted small">Built on uncompromising quality, community care, and digital convenience.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-3 col-sm-6">
                <div class="p-3 bg-light rounded-3 h-100 border-start border-3 border-success text-center">
                    <div class="d-inline-flex p-3 rounded-circle bg-white text-success shadow-xs mb-3">
                        <i class="bi bi-clock-history fs-3"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Baked Fresh Daily</h6>
                    <p class="small text-muted m-0">All items are prepared and baked the same day by certified partner bakeries.</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="p-3 bg-light rounded-3 h-100 border-start border-3 border-warning text-center">
                    <div class="d-inline-flex p-3 rounded-circle bg-white text-warning shadow-xs mb-3">
                        <i class="bi bi-wallet2 fs-3"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Pocket-Friendly</h6>
                    <p class="small text-muted m-0">Flat starting prices from 40,000 ₫ for Snack Boxes and 70,000 ₫ for Meal Boxes.</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="p-3 bg-light rounded-3 h-100 border-start border-3 border-primary text-center">
                    <div class="d-inline-flex p-3 rounded-circle bg-white text-primary shadow-xs mb-3">
                        <i class="bi bi-bicycle fs-3"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Flexible Fulfillment</h6>
                    <p class="small text-muted m-0">Express 60-second pickup or doorstep delivery to your university dorm or apartment.</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="p-3 bg-light rounded-3 h-100 border-start border-3 border-danger text-center">
                    <div class="d-inline-flex p-3 rounded-circle bg-white text-danger shadow-xs mb-3">
                        <i class="bi bi-credit-card fs-3"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Multiple Payments</h6>
                    <p class="small text-muted m-0">Pay seamlessly with MoMo, VNPay, 24/7 Bank Transfer, or Cash upon receiving your food.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. MEET OUR TEAM (EXACTLY FOUR TEAM MEMBER PROFILES WITH EDITABLE PLACEHOLDERS) -->
    <div class="mt-5 pt-3">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 text-uppercase fw-bold mb-2">Leadership & Team</span>
            <h2 class="fw-bold text-dark mb-2">Meet Our Team</h2>
            <p class="text-muted small">
                The four founders driving technology, operations, and culinary partnerships at NomReNom.
            </p>
        </div>

        <div class="row g-4">
            <!-- MEMBER 1 -->
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm rounded-4 text-center p-4 bg-white h-100 team-profile-card">
                    <div class="position-relative d-inline-block mx-auto mb-3">
                        <!-- EDIT PLACEHOLDER: Replace src with your photo e.g. assets/img/team/my-photo.jpg -->
                        <img src="<?= BASE_URL ?>/assets/img/team/member-1.svg" alt="Member 1" class="rounded-circle shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                        <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-success p-2" title="Active Founder"><i class="bi bi-check-lg"></i></span>
                    </div>
                    <!-- EDIT PLACEHOLDER: Enter your real name below -->
                    <h5 class="fw-bold text-dark mb-1">[Your Name - Member 1]</h5>
                    <span class="badge bg-light text-success border mb-3">Founder & Chief Executive Officer</span>
                    <p class="small text-muted mb-3">
                        Leads overall company strategy, brand vision, and digital platform development. Passionate about applying food tech to eliminate campus and urban food waste.
                    </p>
                    <div class="mt-auto pt-3 border-top d-flex justify-content-center gap-3 text-muted">
                        <a href="#" class="text-muted hover-green"><i class="bi bi-linkedin fs-5"></i></a>
                        <a href="#" class="text-muted hover-green"><i class="bi bi-envelope-fill fs-5"></i></a>
                    </div>
                </div>
            </div>

            <!-- MEMBER 2 -->
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm rounded-4 text-center p-4 bg-white h-100 team-profile-card">
                    <div class="position-relative d-inline-block mx-auto mb-3">
                        <!-- EDIT PLACEHOLDER: Replace src with your photo e.g. assets/img/team/my-photo.jpg -->
                        <img src="<?= BASE_URL ?>/assets/img/team/member-2.svg" alt="Member 2" class="rounded-circle shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                        <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-success p-2" title="Active Founder"><i class="bi bi-check-lg"></i></span>
                    </div>
                    <!-- EDIT PLACEHOLDER: Enter your real name below -->
                    <h5 class="fw-bold text-dark mb-1">[Your Name - Member 2]</h5>
                    <span class="badge bg-light text-success border mb-3">Co-Founder & Head of Operations</span>
                    <p class="small text-muted mb-3">
                        Manages order fulfillment logistics, driver coordination, and customer experience. Ensures that every order is packed accurately and ready within seconds.
                    </p>
                    <div class="mt-auto pt-3 border-top d-flex justify-content-center gap-3 text-muted">
                        <a href="#" class="text-muted hover-green"><i class="bi bi-linkedin fs-5"></i></a>
                        <a href="#" class="text-muted hover-green"><i class="bi bi-envelope-fill fs-5"></i></a>
                    </div>
                </div>
            </div>

            <!-- MEMBER 3 -->
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm rounded-4 text-center p-4 bg-white h-100 team-profile-card">
                    <div class="position-relative d-inline-block mx-auto mb-3">
                        <!-- EDIT PLACEHOLDER: Replace src with your photo e.g. assets/img/team/my-photo.jpg -->
                        <img src="<?= BASE_URL ?>/assets/img/team/member-3.svg" alt="Member 3" class="rounded-circle shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                        <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-success p-2" title="Active Founder"><i class="bi bi-check-lg"></i></span>
                    </div>
                    <!-- EDIT PLACEHOLDER: Enter your real name below -->
                    <h5 class="fw-bold text-dark mb-1">[Your Name - Member 3]</h5>
                    <span class="badge bg-light text-success border mb-3">Head of Bakery Partnerships</span>
                    <p class="small text-muted mb-3">
                        Builds relationships with local artisan bakeries and patisseries. Oversees strict food safety standards, surplus packaging protocols, and daily menu selection.
                    </p>
                    <div class="mt-auto pt-3 border-top d-flex justify-content-center gap-3 text-muted">
                        <a href="#" class="text-muted hover-green"><i class="bi bi-linkedin fs-5"></i></a>
                        <a href="#" class="text-muted hover-green"><i class="bi bi-envelope-fill fs-5"></i></a>
                    </div>
                </div>
            </div>

            <!-- MEMBER 4 -->
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm rounded-4 text-center p-4 bg-white h-100 team-profile-card">
                    <div class="position-relative d-inline-block mx-auto mb-3">
                        <!-- EDIT PLACEHOLDER: Replace src with your photo e.g. assets/img/team/my-photo.jpg -->
                        <img src="<?= BASE_URL ?>/assets/img/team/member-4.svg" alt="Member 4" class="rounded-circle shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                        <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-success p-2" title="Active Founder"><i class="bi bi-check-lg"></i></span>
                    </div>
                    <!-- EDIT PLACEHOLDER: Enter your real name below -->
                    <h5 class="fw-bold text-dark mb-1">[Your Name - Member 4]</h5>
                    <span class="badge bg-light text-success border mb-3">Lead Platform Engineer & UI/UX</span>
                    <p class="small text-muted mb-3">
                        Engineered the full-stack transactional architecture, live multi-stage order tracking engine, secure admin dashboard, and responsive mobile interfaces.
                    </p>
                    <div class="mt-auto pt-3 border-top d-flex justify-content-center gap-3 text-muted">
                        <a href="#" class="text-muted hover-green"><i class="bi bi-linkedin fs-5"></i></a>
                        <a href="#" class="text-muted hover-green"><i class="bi bi-envelope-fill fs-5"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
