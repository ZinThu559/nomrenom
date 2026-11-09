<?php
/**
 * NomReNom - Terms and Conditions
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Terms and Conditions';
require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-cream-dark py-5 mb-5 border-bottom">
    <div class="container text-center">
        <span class="badge bg-green-dark text-white px-3 py-2 text-uppercase mb-2">Legal & Policies</span>
        <h1 class="display-5 font-serif text-green-dark mb-3">NomReNom Terms of Service</h1>
        <p class="fs-5 text-muted max-w-700 mx-auto">
            Guidelines and policies governing the surplus-food rescue marketplace for customers and partner bakeries.
        </p>
    </div>
</div>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <div class="small text-muted mb-4 border-bottom pb-3">
                    <strong>Effective Date:</strong> November 1, 2026 | <strong>Version:</strong> 1.0 (University Capstone Project)
                </div>

                <h4 class="font-serif text-green-dark mb-3" id="general">1. About the Platform</h4>
                <p class="text-muted small">
                    NomReNom is an intermediary digital marketplace connecting local artisan bakeries with customers (with priority for university students) to rescue daily surplus baked goods. Our core missions are food waste reduction, student budget affordability, and local bakery support.
                </p>

                <h4 class="font-serif text-green-dark mb-3 mt-4" id="pricing">2. Food Box Categories & Standardized Pricing</h4>
                <p class="text-muted small">
                    To maintain radical simplicity and transparency for students, NomReNom enforces two standardized price points:
                </p>
                <ul class="text-muted small ps-3">
                    <li><strong>Snack Box:</strong> 40,000 VND (minimum retail bakery value of 80,000 VND).</li>
                    <li><strong>Meal Box:</strong> 70,000 VND (minimum retail bakery value of 140,000 VND).</li>
                </ul>
                <p class="text-muted small">
                    Customers acknowledge and accept that exact pastry contents inside each surprise box vary each day based on surplus stock available at the bakery.
                </p>

                <h4 class="font-serif text-green-dark mb-3 mt-4" id="food-safety">3. Food Quality & Safety Standards</h4>
                <p class="text-muted small">
                    All partner bakeries participating in the NomReNom network must hold valid food hygiene and safety certificates. Surplus food boxes contain items baked fresh on the same day, preserved under sanitary conditions. Expired or spoiled goods are strictly prohibited.
                </p>

                <h4 class="font-serif text-green-dark mb-3 mt-4" id="pickup">4. Collection Policy & Pickup Windows</h4>
                <p class="text-muted small">
                    Customers are responsible for arriving at the designated bakery address and presenting their <strong>Reservation Code</strong> during their selected pickup window. If a customer arrives more than 30 minutes after the window closes without prior notice, the bakery reserves the right to release or discard the item.
                </p>

                <h4 class="font-serif text-green-dark mb-3 mt-4" id="cancellation">5. Cancellation & Refund Policy</h4>
                <p class="text-muted small">
                    Customers may cancel active reservations online through their account portal up to 30 minutes before the pickup window begins. For online payments processed via VNPay or MoMo, refunds are handled according to payment gateway processing timelines (typically 3–5 business days).
                </p>

                <h4 class="font-serif text-green-dark mb-3 mt-4" id="liability">6. Limitation of Liability</h4>
                <p class="text-muted small">
                    NomReNom operates as an online marketplace. Partner bakeries remain directly responsible for food preparation, allergen disclosures, and physical packaging.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
