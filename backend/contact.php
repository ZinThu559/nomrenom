<?php
/**
 * NomReNom - Contact Us & Bakery Partnership Inquiry
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDbConnection();
$submitted = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $subject = clean($_POST['subject'] ?? 'General Support');
    $message = clean($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Please fill out your full name, email address, and message.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } else {
        // 1. Store message in database
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
            $stmt->execute([$name, $email, $phone, $subject, $message]);
        } catch (Exception $e) {
            error_log("Failed to insert contact message: " . $e->getMessage());
        }

        // 2. Log contact message into activity log / file
        $logDetails = "Inquiry from {$name} ({$email}, {$phone}): [{$subject}] - {$message}";
        @file_put_contents(ROOT_PATH . '/storage/logs/mail.log', "[CONTACT FORM " . date('Y-m-d H:i:s') . "] " . $logDetails . "\n\n", FILE_APPEND);
        
        setFlash('success', 'Thank you for getting in touch! The NomReNom team has received your message and will respond to <strong>' . htmlspecialchars($email) . '</strong> shortly.');
        header('Location: ' . BASE_URL . '/contact.php');
        exit;
    }
}

$pageTitle = 'Contact Us & Bakery Partnerships';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5 mb-4">
    <div class="container text-center">
        <span class="badge bg-green-light text-green-dark px-3 py-2 fw-bold text-uppercase mb-2">Connect With Us</span>
        <h1 class="display-5 fw-bold text-dark mb-3">Contact Us & Partnerships</h1>
        <p class="fs-5 text-muted max-w-700 mx-auto">
            Whether you are a university student seeking support or a local bakery wanting to join our food rescue movement, we would love to hear from you!
        </p>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-5">
        <!-- Contact Form -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white" id="contactForm">
                <h4 class="fw-bold text-dark mb-3">Send Us a Message</h4>
                <p class="text-muted small mb-4">Complete the form below and our student support team will reply within 24 business hours.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger small mb-4"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/contact.php" method="POST">
                    <?= csrfField() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Your Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Jane Doe" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="name@domain.com" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" placeholder="0901234567">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Subject <span class="text-danger">*</span></label>
                            <select name="subject" class="form-select" required>
                                <option value="Order Support">Order & Reservation Support</option>
                                <option value="Bakery Partnership">New Bakery Partnership Inquiry</option>
                                <option value="Platform Feedback">Platform Suggestion / Feedback</option>
                                <option value="Campus Collaboration">University / Student Club Collaboration</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Message <span class="text-danger">*</span></label>
                        <textarea name="message" rows="5" class="form-control" placeholder="Write your questions or partnership details here..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-nrn-primary btn-lg w-100 py-3">
                        <i class="bi bi-send-fill me-2"></i> Send Message Now
                    </button>
                </form>
            </div>
        </div>

        <!-- Contact Information & Coordinates -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h5 class="text-green-dark mb-3"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Project Information</h5>
                <ul class="list-unstyled small text-muted d-flex flex-column gap-3 m-0">
                    <li class="d-flex align-items-start gap-2">
                        <i class="bi bi-building fs-5 text-green-primary"></i>
                        <div>
                            <strong>Campus Headquarters:</strong><br>
                            Vietnam National University Campus, Thu Duc City, Ho Chi Minh City.
                        </div>
                    </li>
                    <li class="d-flex align-items-start gap-2">
                        <i class="bi bi-envelope-at fs-5 text-green-primary"></i>
                        <div>
                            <strong>Official Email:</strong><br>
                            <a href="mailto:contact@nomrenom.vn" class="text-green-primary">contact@nomrenom.vn</a>
                        </div>
                    </li>
                    <li class="d-flex align-items-start gap-2">
                        <i class="bi bi-telephone fs-5 text-green-primary"></i>
                        <div>
                            <strong>Student Support Helpline:</strong><br>
                            0901 234 567 (Mon – Sun: 8:00 AM – 9:30 PM)
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Bakery Partnership Box -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-light border-start border-4 border-success">
                <h5 class="fw-bold text-dark mb-2"><i class="bi bi-shop me-2 text-success"></i>For Bakery Owners</h5>
                <p class="small text-muted mb-3">
                    Operate a local bakery or cafe and wish to reduce food waste, optimize ingredients, and connect with thousands of active students?
                </p>
                <div class="small fw-bold text-dark mb-2">Partner Benefits:</div>
                <ul class="small text-muted ps-3 mb-3">
                    <li>Recover production expenses on fresh daily surplus baked goods.</li>
                    <li>Full autonomous control over box quantities and pickup windows.</li>
                    <li>Complimentary sustainable brand promotion to university students.</li>
                </ul>
                <a href="#contactForm" class="btn btn-sm btn-nrn-caramel w-100">
                    <i class="bi bi-arrow-up-right me-1"></i> Inquire To Become a Partner Bakery
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
