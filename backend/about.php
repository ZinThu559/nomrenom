<?php
/**
 * NomReNom - About Us (Near-Expiry & Imperfect Snacks Marketplace)
 * Features Company Story, Core Values, Quality Standards, and Four Team Member Profiles
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'About Us – NomReNom Snack Marketplace';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Brand Hero -->
<div class="py-5 mb-5 border-bottom" style="background: linear-gradient(180deg, #FAF7F2 0%, #FFFFFF 100%);">
    <div class="container text-center">
        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 text-uppercase fw-bold mb-3">
            <i class="bi bi-box-seam me-1"></i> About NomReNom
        </span>
        <h1 class="display-4 fw-bold text-dark mb-3">Rescue Imperfect Snacks, Save Money, Zero Waste</h1>
        <p class="fs-5 text-muted max-w-700 mx-auto">
            A pickup marketplace for near-expiry and damaged snacks (dented boxes, crumbled biscuits, melted-and-set chocolate). Partner stores list their stock, students reserve with VietQR, inspect at the store counter, and take it home — with zero shipping fees!
        </p>
    </div>
</div>

<div class="container pb-5">
    <!-- 1. OUR STORY & PURPOSE -->
    <div class="row g-5 align-items-center mb-5">
        <div class="col-lg-6">
            <span class="text-uppercase fw-bold text-success small letter-spacing-1">Our Story & Purpose</span>
            <h2 class="fw-bold text-dark mb-3">Eliminating Retail Snack Waste Across Campus Hubs</h2>
            <p class="text-muted">
                Every month, convenience stores and import snack retailers discard crates of perfectly edible snacks because of a crushed outer carton, crumbled biscuits, or a best-before date approaching in 2–4 weeks. These items are still factory-sealed, delicious, and safe — yet traditional retail systems cannot sell them at full price.
            </p>
            <p class="text-muted">
                <strong>NomReNom</strong> bridges this gap. We created a transparent, student-centric marketplace where partner stores list their imperfect snacks at <strong>40% to 70% off</strong>. Students reserve and pay securely via VietQR, visit the store counter near their university campus, inspect the item in person, and take it home.
            </p>
            <div class="row g-3 mt-2">
                <div class="col-6">
                    <div class="p-3 bg-light rounded-4 border">
                        <strong class="d-block text-dark fs-5">50%–70% Off</strong>
                        <small class="text-muted">Student discounts on premium branded snacks</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-light rounded-4 border">
                        <strong class="d-block text-dark fs-5">100% Pickup</strong>
                        <small class="text-muted">Zero shipping fees &amp; zero courier delays</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white p-3">
                <img src="<?= BASE_URL ?>/assets/img/bakery_pastries_1793872097849.jpg" alt="Snacks Marketplace" class="img-fluid rounded-3">
                <div class="p-3 text-center text-muted small fst-italic">
                    "Every snack has a real photo, condition grade, and counter inspection guarantee."
                </div>
            </div>
        </div>
    </div>

    <!-- 2. CORE VALUES / OPERATING PILLARS -->
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-5">
        <div class="text-center max-w-700 mx-auto mb-4">
            <h3 class="fw-bold text-dark mb-2">Our Operating Pillars</h3>
            <p class="text-muted small">Built on radical transparency, food safety standards, and counter inspection trust.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-3 col-sm-6">
                <div class="p-3 bg-light rounded-4 h-100 border-start border-4 border-success text-center">
                    <div class="d-inline-flex p-3 rounded-circle bg-white text-success shadow-xs mb-3">
                        <i class="bi bi-camera-fill fs-3"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Real Stock Photos</h6>
                    <p class="small text-muted m-0">No generic marketing renders. Every listing features actual photos of the specific item and packaging condition.</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="p-3 bg-light rounded-4 h-100 border-start border-4 border-warning text-center">
                    <div class="d-inline-flex p-3 rounded-circle bg-white text-warning shadow-xs mb-3">
                        <i class="bi bi-calendar-check fs-3"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">7 to 30 Days Left</h6>
                    <p class="small text-muted m-0">Strict policy: nothing with fewer than 7 days left, and absolutely nothing expired. Inner seals must be 100% intact.</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="p-3 bg-light rounded-4 h-100 border-start border-4 border-primary text-center">
                    <div class="d-inline-flex p-3 rounded-circle bg-white text-primary shadow-xs mb-3">
                        <i class="bi bi-shop fs-3"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Zero Shipping</h6>
                    <p class="small text-muted m-0">100% store pickup at convenience counters next to campus dorms. Collect in under 60 seconds with no delivery fees.</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="p-3 bg-light rounded-4 h-100 border-start border-4 border-danger text-center">
                    <div class="d-inline-flex p-3 rounded-circle bg-white text-danger shadow-xs mb-3">
                        <i class="bi bi-shield-check fs-3"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Inspect &amp; Refund</h6>
                    <p class="small text-muted m-0">Inspect at the counter before taking it home. If it doesn't match the photo, refuse it on your phone for a full refund.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. MEET OUR TEAM -->
    <div class="mt-5 pt-3">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 text-uppercase fw-bold mb-2">Leadership &amp; Team</span>
            <h2 class="fw-bold text-dark mb-2">Meet Our Team</h2>
            <p class="text-muted small">
                The four founders driving technology, operations, and convenience store partnerships at NomReNom.
            </p>
        </div>

        <div class="row g-3">
            <!-- MEMBER 1 -->
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm text-center bg-white h-100 team-profile-card">
                    <div class="position-relative d-inline-block mx-auto mb-2">
                        <img src="<?= BASE_URL ?>/assets/img/team/member-1.png?v=<?= filemtime(__DIR__ . '/assets/img/team/member-1.png') ?>" alt="Nguyen Hoang Huyen Tram" class="rounded-circle team-avatar-img">
                        <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-success p-1" style="font-size: 0.65rem;" title="Active Founder"><i class="bi bi-check-lg"></i></span>
                    </div>
                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">Nguyen Hoang Huyen Tram</h6>
                    <span class="team-role-badge mb-2">Founder &amp; CEO</span>
                    <p class="text-muted mb-2" style="font-size: 0.8rem; line-height: 1.45;">
                        Leads overall company strategy, brand vision, and partner store acquisition across university campus hubs.
                    </p>
                    <div class="mt-auto pt-2 border-top d-flex justify-content-center gap-3 text-muted">
                        <a href="#" class="text-muted hover-green"><i class="bi bi-linkedin fs-6"></i></a>
                        <a href="#" class="text-muted hover-green"><i class="bi bi-envelope-fill fs-6"></i></a>
                    </div>
                </div>
            </div>

            <!-- MEMBER 2 -->
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm text-center bg-white h-100 team-profile-card">
                    <div class="position-relative d-inline-block mx-auto mb-2">
                        <img src="<?= BASE_URL ?>/assets/img/team/member-2.png?v=<?= filemtime(__DIR__ . '/assets/img/team/member-2.png') ?>" alt="Nguyen Duc Tan Loc" class="rounded-circle team-avatar-img">
                        <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-success p-1" style="font-size: 0.65rem;" title="Active Founder"><i class="bi bi-check-lg"></i></span>
                    </div>
                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">Nguyen Duc Tan Loc</h6>
                    <span class="team-role-badge mb-2">Co-Founder &amp; Operations</span>
                    <p class="text-muted mb-2" style="font-size: 0.8rem; line-height: 1.45;">
                        Manages store pickup logistics, cashier counter integration, and customer support. Ensures seamless 60-second pickups.
                    </p>
                    <div class="mt-auto pt-2 border-top d-flex justify-content-center gap-3 text-muted">
                        <a href="#" class="text-muted hover-green"><i class="bi bi-linkedin fs-6"></i></a>
                        <a href="#" class="text-muted hover-green"><i class="bi bi-envelope-fill fs-6"></i></a>
                    </div>
                </div>
            </div>

            <!-- MEMBER 3 -->
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm text-center bg-white h-100 team-profile-card">
                    <div class="position-relative d-inline-block mx-auto mb-2">
                        <img src="<?= BASE_URL ?>/assets/img/team/member-3.png?v=<?= filemtime(__DIR__ . '/assets/img/team/member-3.png') ?>" alt="Thet Htar San" class="rounded-circle team-avatar-img">
                        <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-success p-1" style="font-size: 0.65rem;" title="Active Founder"><i class="bi bi-check-lg"></i></span>
                    </div>
                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">Thet Htar San</h6>
                    <span class="team-role-badge mb-2">Store Partnerships &amp; Safety</span>
                    <p class="text-muted mb-2" style="font-size: 0.8rem; line-height: 1.45;">
                        Partners with convenience store networks. Enforces strict 7–30 day expiry rules, inner seal integrity, and condition verification.
                    </p>
                    <div class="mt-auto pt-2 border-top d-flex justify-content-center gap-3 text-muted">
                        <a href="#" class="text-muted hover-green"><i class="bi bi-linkedin fs-6"></i></a>
                        <a href="#" class="text-muted hover-green"><i class="bi bi-envelope-fill fs-6"></i></a>
                    </div>
                </div>
            </div>

            <!-- MEMBER 4 -->
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm text-center bg-white h-100 team-profile-card">
                    <div class="position-relative d-inline-block mx-auto mb-2">
                        <img src="<?= BASE_URL ?>/assets/img/team/member-4.png?v=<?= filemtime(__DIR__ . '/assets/img/team/member-4.png') ?>" alt="Zin Thu Hein Htet" class="rounded-circle team-avatar-img">
                        <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-success p-1" style="font-size: 0.65rem;" title="Active Founder"><i class="bi bi-check-lg"></i></span>
                    </div>
                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">Zin Thu Hein Htet</h6>
                    <span class="team-role-badge mb-2">Lead Platform &amp; UI/UX</span>
                    <p class="text-muted mb-2" style="font-size: 0.8rem; line-height: 1.45;">
                        Engineered the VietQR reservation engine, digital counter voucher system, instant inspection refund flow, and mobile UI.
                    </p>
                    <div class="mt-auto pt-2 border-top d-flex justify-content-center gap-3 text-muted">
                        <a href="#" class="text-muted hover-green"><i class="bi bi-linkedin fs-6"></i></a>
                        <a href="#" class="text-muted hover-green"><i class="bi bi-envelope-fill fs-6"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
