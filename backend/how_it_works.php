<?php
/**
 * NomReNom - How It Works & Snack Marketplace Quality Standards
 * 4-Step Process: Browse -> Reserve (VietQR) -> Check at Store -> Pick Up
 * What Stores Can List Standards & FAQ
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'How It Works & Snack Standards – NomReNom';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5 mb-4">
    <div class="container text-center">
        <span class="badge bg-green-light text-green-dark px-3 py-2 fw-bold text-uppercase mb-2">Zero Shipping • 100% Counter Pickup</span>
        <h1 class="display-5 fw-bold text-dark mb-3">How NomReNom Works</h1>
        <p class="fs-5 text-muted max-w-700 mx-auto">
            A pickup marketplace for near-expiry and damaged snacks (dented boxes, crumbled biscuits, melted-and-set chocolate). Browse real photos, reserve with VietQR, inspect at the store counter, and take it home!
        </p>
    </div>
</div>

<div class="container pb-5">
    <!-- 4 Steps Detailed -->
    <div class="row g-4 mb-5">
        <!-- Step 1 -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white border-top border-4 border-success">
                <div class="step-num mb-3">1</div>
                <h4 class="fw-bold text-dark mb-2">1. Browse Real Photos</h4>
                <p class="text-muted small mb-3">
                    Partner convenience stores upload real photos of each specific snack item, showing the exact packaging condition, printed expiry date, condition grade, and steep discount.
                </p>
                <div class="p-2 bg-light rounded-3 small text-muted border">
                    <i class="bi bi-camera-fill text-warning me-1"></i> No generic stock photos: you see the exact defect before ordering.
                </div>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white border-top border-4 border-success">
                <div class="step-num mb-3">2</div>
                <h4 class="fw-bold text-dark mb-2">2. Reserve & Pay VietQR</h4>
                <p class="text-muted small mb-3">
                    Lock in your snack item instantly by scanning the dynamic VietQR code with any Vietnamese banking app. You immediately receive a 6-digit digital pickup code.
                </p>
                <div class="p-2 bg-light rounded-3 small text-muted border">
                    <i class="bi bi-qr-code-scan text-success me-1"></i> Instant verification, zero payment fees, no double booking.
                </div>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white border-top border-4 border-warning">
                <div class="step-num mb-3">3</div>
                <h4 class="fw-bold text-dark mb-2">3. Check at the Store</h4>
                <p class="text-muted small mb-3">
                    Walk into the partner store counter near your campus. Show your pickup code and physically inspect the snack before accepting it.
                </p>
                <div class="p-2 bg-warning bg-opacity-10 text-dark rounded-3 small border border-warning">
                    <i class="bi bi-shield-check text-warning me-1"></i> <strong>Doesn't match photo?</strong> Tap "Refuse" on your phone for an immediate full refund!
                </div>
            </div>
        </div>

        <!-- Step 4 -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white border-top border-4 border-success">
                <div class="step-num mb-3">4</div>
                <h4 class="fw-bold text-dark mb-2">4. Pick Up & Enjoy</h4>
                <p class="text-muted small mb-3">
                    Satisfied with the condition? Confirm the pickup and take your snack home! You just saved 40%–70% and helped eliminate food waste in Ho Chi Minh City.
                </p>
                <div class="p-2 bg-light rounded-3 small text-muted border">
                    <i class="bi bi-bicycle text-muted me-1"></i> <strong>No shipping:</strong> 100% store pickup means zero delivery delays and zero courier fees.
                </div>
            </div>
        </div>
    </div>

    <!-- What Stores Can List Standards Section -->
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <span class="badge bg-warning text-dark px-3 py-2 fw-bold text-uppercase mb-2">Our Strict Quality Code</span>
                <h2 class="display-6 fw-bold text-dark mb-3">What Stores Can List</h2>
                <p class="text-muted">
                    We strictly distinguish between <em>cosmetically imperfect snacks</em> and <em>unsafe food</em>. Every item listed on NomReNom must meet these rigid criteria:
                </p>

                <div class="d-flex flex-column gap-3 mt-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-calendar-check fs-5"></i>
                        </div>
                        <div>
                            <strong class="text-dark d-block">Near-Expiry Snacks (7 to 30 Days Left)</strong>
                            <small class="text-muted">Between 1 month down to 7 days before the printed best-before date. Ample time for safe consumption.</small>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-box-seam fs-5"></i>
                        </div>
                        <div>
                            <strong class="text-dark d-block">Dented or Damaged Outer Packaging</strong>
                            <small class="text-muted">Outer cartons with crushed corners, dented cans, or creased packaging, but with 100% intact inner food seals.</small>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-cookie fs-5"></i>
                        </div>
                        <div>
                            <strong class="text-dark d-block">Imperfect Shape (Crumbled or Melted & Re-set)</strong>
                            <small class="text-muted">Cookies broken during transit or chocolates that softened in tropical heat and set again. Completely edible and delicious!</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border border-danger border-opacity-25 bg-danger bg-opacity-10 rounded-4 p-4">
                    <h5 class="fw-bold text-danger mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-x-octagon-fill fs-4"></i> What Stores Can NEVER List
                    </h5>
                    <ul class="text-danger small mb-4 ps-3 lh-lg">
                        <li><strong>Nothing with fewer than 7 days left</strong> before the printed date.</li>
                        <li><strong>Nothing expired</strong> under any circumstances.</li>
                        <li><strong>No broken inner seals</strong> or punctured foil bags.</li>
                        <li><strong>No altered, obscured, or retaped</strong> expiration dates.</li>
                        <li><strong>No recalled products</strong> or items without official import labels.</li>
                    </ul>

                    <div class="p-3 bg-white rounded-3 border small">
                        <strong class="text-dark d-block mb-1"><i class="bi bi-shield-lock-fill text-success me-1"></i> Zero-Risk Policy:</strong>
                        <span class="text-muted">If a store ever hands you an item violating these standards, simply hit "Refuse at Counter" on your phone for an instant refund.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Accordion -->
    <div class="mt-5 pt-3">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-green-light text-green-dark px-3 py-2 text-uppercase fw-bold mb-2">Got Questions?</span>
            <h2 class="fw-bold text-dark mb-2">Frequently Asked Questions (FAQ)</h2>
            <p class="text-muted small">Everything you need to know about reserving and inspecting snacks at NomReNom.</p>
        </div>

        <div class="accordion shadow-sm rounded-4 overflow-hidden border-0" id="faqNomReNom">
            <!-- FAQ 1 -->
            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="faqOne">
                    <button class="accordion-button fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        1. Why does NomReNom only offer Store Pickup with zero shipping?
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="faqOne" data-bs-parent="#faqNomReNom">
                    <div class="accordion-body text-muted small">
                        Store pickup protects you in two major ways:
                        <ol class="mt-2 mb-0 ps-3">
                            <li><strong>Zero Courier Fees:</strong> Shipping a 25,000₫ snack often costs 30,000₫ in delivery fees. By picking up at partner convenience stores next to campus dorms, you pay only for the food.</li>
                            <li><strong>Instant Physical Inspection:</strong> You inspect the item in person before taking it home. If it doesn't match the photo shown on the site, you refuse it on the spot and leave it at the counter for an immediate refund.</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- FAQ 2 -->
            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="faqTwo">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        2. How does the "Inspect at Store & Refund" guarantee work?
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#faqNomReNom">
                    <div class="accordion-body text-muted small">
                        When you arrive at the store counter, open your <strong>Order Tracking Voucher</strong> on your smartphone. When the cashier hands you the snack, check its packaging and date against the real photo. If you see any unlisted damage or discrepancy, simply tap the red <strong>"Refuse Item (Doesn't Match Photo)"</strong> button on your screen. The order is automatically cancelled and your VietQR payment is refunded immediately.
                    </div>
                </div>
            </div>

            <!-- FAQ 3 -->
            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="faqThree">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        3. What is the minimum remaining shelf life on listed items?
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#faqNomReNom">
                    <div class="accordion-body text-muted small">
                        Every single item listed on NomReNom must have at least <strong>7 days left</strong> before the printed best-before date. Items with 6 days or fewer, as well as expired products, are automatically rejected by our platform algorithms.
                    </div>
                </div>
            </div>

            <!-- FAQ 4 -->
            <div class="accordion-item border-0">
                <h2 class="accordion-header" id="faqFour">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        4. What payment methods can I use?
                    </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="faqFour" data-bs-parent="#faqNomReNom">
                    <div class="accordion-body text-muted small">
                        We recommend <strong>VietQR</strong>: scan the instant QR code using any Vietnamese mobile banking app (Vietcombank, MB Bank, Techcombank, Vietinbank, etc.) or MoMo. You can also opt for Cash at Counter if you prefer paying in person after inspection.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
