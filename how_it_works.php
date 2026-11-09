<?php
/**
 * NomReNom - How It Works & FAQ Guide
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'How It Works & FAQ';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5 mb-4">
    <div class="container text-center">
        <span class="badge bg-green-light text-green-dark px-3 py-2 fw-bold text-uppercase mb-2">Simple 3-Step Process</span>
        <h1 class="display-5 fw-bold text-dark mb-3">How NomReNom Works</h1>
        <p class="fs-5 text-muted max-w-700 mx-auto">
            Rescue delicious surplus baked goods in 3 easy steps – Save money, enjoy fresh food, and protect the environment.
        </p>
    </div>
</div>

<div class="container pb-5">
    <!-- 3 Steps Detailed -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="step-num mb-3">1</div>
                <h4 class="fw-bold text-dark mb-3">Choose Box & Window</h4>
                <p class="text-muted small">
                    Every late afternoon, our partner bakeries tally their fresh daily unsold products and offer them in 2 flat-price box categories:
                </p>
                <div class="p-3 bg-light rounded-3 text-start small mb-3 border">
                    <div class="mb-2"><strong class="text-danger">● Snack Box (40,000₫):</strong> Sweet pastries, croissants, muffins, tea cakes.</div>
                    <div><strong class="text-dark">● Meal Box (70,000₫):</strong> Savory deli sandwiches, quiches, sourdough dinner items.</div>
                </div>
                <p class="text-muted small">
                    Select your desired quantity and a collection window fitting your after-class or evening commute schedule.
                </p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="step-num mb-3">2</div>
                <h4 class="fw-bold text-dark mb-3">Confirm & Secure Spot</h4>
                <p class="text-muted small">
                    The platform verifies real-time bakery inventory using atomic database transactions to prevent double-booking:
                </p>
                <div class="p-3 bg-light rounded-3 text-start small mb-3 border">
                    <div class="mb-2"><strong>● Registered Members:</strong> Fast 1-click checkout using saved profile data.</div>
                    <div><strong>● Guest Customers:</strong> Direct reservation with verified email and phone number without mandatory signup.</div>
                </div>
                <p class="text-muted small">
                    Pay online via VNPay/MoMo sandbox or choose "Pay at Pickup" at the bakery counter.
                </p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="step-num mb-3">3</div>
                <h4 class="fw-bold text-dark mb-3">Pick Up & Enjoy</h4>
                <p class="text-muted small">
                    You immediately receive a unique <strong>Reservation Reference Code</strong> (e.g., <code>NRN-20261105-8F2B</code>) via email and on screen:
                </p>
                <div class="p-3 bg-light rounded-3 text-start small mb-3 border">
                    <div class="mb-2"><strong>● Location:</strong> Visit the partner bakery location.</div>
                    <div><strong>● Express Pickup:</strong> Present your code or phone number to the staff to receive your fresh box.</div>
                </div>
                <p class="text-muted small">
                    Pickup takes under 1 minute with no queuing or waiting!
                </p>
            </div>
        </div>
    </div>

    <!-- FAQ Accordion -->
    <div class="mt-5 pt-4 border-top">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-green-light text-green-dark px-3 py-2 text-uppercase fw-bold mb-2">Frequently Asked Questions</span>
            <h2 class="fw-bold text-dark mb-2">Frequently Asked Questions (FAQ)</h2>
            <p class="text-muted small">Common questions from students and customers using NomReNom.</p>
        </div>

        <div class="accordion shadow-sm rounded-4 overflow-hidden border-0" id="faqNomReNom">
            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="faqOne">
                    <button class="accordion-button fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        1. Are surplus food boxes safe and high quality?
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="faqOne" data-bs-parent="#faqNomReNom">
                    <div class="accordion-body text-muted small">
                        <strong>100% safe and fresh!</strong> All bakery items in our Snack and Meal Boxes are baked fresh on the same day by certified partner bakeries. They represent surplus batches that were not sold during morning and daytime rush hours, never expired or stale goods.
                    </div>
                </div>
            </div>

            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="faqTwo">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        2. Can I choose the exact pastries inside the box?
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#faqNomReNom">
                    <div class="accordion-body text-muted small">
                        Because surplus food depends on daily bakery inventory, boxes are prepared as "Mystery Surplus Boxes". However, we guarantee that the retail market value of the box is always at least 50% higher than the flat price you pay.
                    </div>
                </div>
            </div>

            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="faqThree">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        3. What happens if I cannot collect my box on time?
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#faqNomReNom">
                    <div class="accordion-body text-muted small">
                        If you have an unexpected conflict, you can easily cancel your booking via your <strong>My Reservations</strong> page at least 30 minutes before your pickup window begins. Your reserved slot is immediately returned to stock for others to claim.
                    </div>
                </div>
            </div>

            <div class="accordion-item border-0">
                <h2 class="accordion-header" id="faqFour">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        4. Can I pay with cash when picking up my box?
                    </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="faqFour" data-bs-parent="#faqNomReNom">
                    <div class="accordion-body text-muted small">
                        <strong>Yes!</strong> NomReNom supports <strong>Pay at Pickup</strong>. You can reserve your box online without paying upfront, then pay with cash or your bank transfer QR app directly to the bakery cashier upon pickup.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
