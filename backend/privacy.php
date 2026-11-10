<?php
/**
 * NomReNom - Privacy Policy
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Privacy Policy';
require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-cream-dark py-5 mb-5 border-bottom">
    <div class="container text-center">
        <span class="badge bg-green-dark text-white px-3 py-2 text-uppercase mb-2">Customer Data Protection</span>
        <h1 class="display-5 font-serif text-green-dark mb-3">Privacy & Data Policy</h1>
        <p class="fs-5 text-muted max-w-700 mx-auto">
            Our commitment to protecting your personal information and handling user data with modern security standards.
        </p>
    </div>
</div>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <div class="small text-muted mb-4 border-bottom pb-3">
                    <strong>Effective Date:</strong> November 1, 2026 | <strong>Compliance:</strong> Vietnamese Personal Data Protection Decree (PDPD)
                </div>

                <h4 class="font-serif text-green-dark mb-3">1. Information We Collect</h4>
                <p class="text-muted small">
                    When you use the NomReNom platform (as a registered member or guest checkout), we collect only minimal information necessary to fulfill surplus-food reservations:
                </p>
                <ul class="text-muted small ps-3">
                    <li>Full name of the person collecting the order.</li>
                    <li>Email address for dispatching reservation codes, digital receipts, and account activation tokens.</li>
                    <li>Phone number so bakery staff can identify you upon arrival.</li>
                    <li>Reservation history and feedback ratings.</li>
                </ul>

                <h4 class="font-serif text-green-dark mb-3 mt-4">2. Zero Bank Card Data Storage</h4>
                <p class="text-muted small">
                    When paying online via licensed Vietnamese payment gateways (VNPay, MoMo), card transactions and e-wallets are processed entirely on the gateway's secure, PCI-DSS compliant infrastructure. NomReNom never accesses, collects, or stores card numbers, expiration dates, or CVV/CVC codes.
                </p>

                <h4 class="font-serif text-green-dark mb-3 mt-4">3. Security & Cryptographic Safeguards</h4>
                <ul class="text-muted small ps-3">
                    <li><strong>Password Hashing:</strong> Passwords are one-way hashed using industry-standard Bcrypt/Argon2i (<code>password_hash()</code>) and cannot be recovered as plain text.</li>
                    <li><strong>CSRF & XSS Protection:</strong> All form submissions are protected by one-time CSRF tokens and strict output sanitization.</li>
                    <li><strong>Secure Order Lookup:</strong> Guest customers must provide both their unique Reservation Code and matching email/phone number to access order information.</li>
                </ul>

                <h4 class="font-serif text-green-dark mb-3 mt-4">4. Your Data Rights</h4>
                <p class="text-muted small">
                    You have the right to inspect, update, or request deletion of your account and reservation history at any time through your <strong>My Profile</strong> dashboard or by emailing our team at <code>privacy@nomrenom.vn</code>.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
