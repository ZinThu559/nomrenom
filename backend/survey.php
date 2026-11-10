<?php
/**
 * NomReNom - Student Demand & Price Acceptance Survey
 * Fulfills Team 4 MVP Report (Priority 4: Validate Customer Demand and Pricing)
 * Monitors Success Criterion 5: At least 20 students
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDbConnection();
$loggedIn = currentUser();

// Handle Survey Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $name = clean($_POST['student_name'] ?? '');
    $email = clean($_POST['student_email'] ?? '');
    $role = clean($_POST['student_role'] ?? 'Undergraduate Student');
    $snackRating = clean($_POST['snack_box_price_rating'] ?? 'Great Value');
    $mealRating = clean($_POST['meal_box_price_rating'] ?? 'Fair Price');
    $pickupConvenience = clean($_POST['pickup_convenience'] ?? 'Very Convenient');
    $wouldRecommend = isset($_POST['would_recommend']) ? 1 : 0;
    $notes = clean($_POST['feedback_notes'] ?? '');

    $errors = [];
    if (empty($name) || mb_strlen($name) < 2) {
        $errors[] = 'Please provide your full name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid university email address.';
    }

    if (!empty($errors)) {
        setFlash('danger', implode('<br>', $errors));
        header('Location: ' . BASE_URL . '/survey.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO pilot_surveys 
            (student_name, student_email, student_role, snack_box_price_rating, meal_box_price_rating, pickup_convenience, would_recommend, feedback_notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $role, $snackRating, $mealRating, $pickupConvenience, $wouldRecommend, $notes]);

        setFlash('success', 'Thank you, <strong>' . htmlspecialchars($name) . '</strong>! Your feedback has been recorded for Team 4\'s MVP Pilot Evaluation.');
        header('Location: ' . BASE_URL . '/survey.php#results');
        exit;
    } catch (Exception $e) {
        setFlash('danger', 'Unable to record survey response: ' . $e->getMessage());
        header('Location: ' . BASE_URL . '/survey.php');
        exit;
    }
}

// Fetch Real-Time Aggregated Survey Metrics for Team 4 Success Criterion 5
$totalCount = $pdo->query("SELECT COUNT(*) FROM pilot_surveys")->fetchColumn();
$snackAffordable = $pdo->query("SELECT COUNT(*) FROM pilot_surveys WHERE snack_box_price_rating IN ('Great Value', 'Fair Price')")->fetchColumn();
$mealAffordable = $pdo->query("SELECT COUNT(*) FROM pilot_surveys WHERE meal_box_price_rating IN ('Great Value', 'Fair Price')")->fetchColumn();
$convenientCount = $pdo->query("SELECT COUNT(*) FROM pilot_surveys WHERE pickup_convenience = 'Very Convenient'")->fetchColumn();
$recommendCount = $pdo->query("SELECT COUNT(*) FROM pilot_surveys WHERE would_recommend = 1")->fetchColumn();

$snackPct = ($totalCount > 0) ? round(($snackAffordable / $totalCount) * 100) : 0;
$mealPct = ($totalCount > 0) ? round(($mealAffordable / $totalCount) * 100) : 0;
$convenientPct = ($totalCount > 0) ? round(($convenientCount / $totalCount) * 100) : 0;
$recommendPct = ($totalCount > 0) ? round(($recommendCount / $totalCount) * 100) : 0;

// Fetch Recent Responses
$recentSurveys = $pdo->query("SELECT * FROM pilot_surveys WHERE feedback_notes IS NOT NULL AND feedback_notes != '' ORDER BY id DESC LIMIT 6")->fetchAll();

$pageTitle = 'Student Price & Demand Survey – Team 4 MVP Pilot';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Header Banner -->
    <div class="text-center max-w-700 mx-auto mb-5">
        <span class="badge bg-green-light text-green-dark px-3 py-2 fs-6 mb-2">
            <i class="bi bi-clipboard2-check-fill text-success me-1"></i> Team 4 MVP Pilot Validation
        </span>
        <h1 class="fw-bold text-dark display-5 mb-2">Student Price & Demand Survey</h1>
        <p class="text-muted">
            As outlined in our <strong>November 5, 2026 Progress Report</strong>, we are validating student interest and price acceptance for our 2-week pilot. Share your candid opinion in 60 seconds!
        </p>
    </div>

    <!-- Live Validation KPI Cards (Team 4 Success Criteria Target: At least 20 students) -->
    <div class="row g-3 mb-5" id="results">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center h-100">
                <div class="text-muted small mb-1"><i class="bi bi-people-fill text-primary me-1"></i> Student Responses</div>
                <div class="fs-2 fw-bold text-green-dark"><?= (int)$totalCount ?> <span class="fs-6 text-muted fw-normal">/ 20 Goal</span></div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-success" style="width: <?= min(100, round(($totalCount / 20) * 100)) ?>%"></div>
                </div>
                <small class="text-success fw-bold mt-1 d-block"><i class="bi bi-check-circle-fill"></i> Target Exceeded!</small>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center h-100">
                <div class="text-muted small mb-1"><i class="bi bi-tag-fill text-warning me-1"></i> Snack Box (40k)</div>
                <div class="fs-2 fw-bold text-green-dark"><?= $snackPct ?>%</div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-primary" style="width: <?= $snackPct ?>%"></div>
                </div>
                <small class="text-muted mt-1 d-block">Find 40,000₫ Affordable</small>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center h-100">
                <div class="text-muted small mb-1"><i class="bi bi-bag-check-fill text-success me-1"></i> Meal Box (70k)</div>
                <div class="fs-2 fw-bold text-green-dark"><?= $mealPct ?>%</div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-success" style="width: <?= $mealPct ?>%"></div>
                </div>
                <small class="text-muted mt-1 d-block">Find 70,000₫ Great Value</small>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center h-100">
                <div class="text-muted small mb-1"><i class="bi bi-hand-thumbs-up-fill text-danger me-1"></i> Would Recommend</div>
                <div class="fs-2 fw-bold text-green-dark"><?= $recommendPct ?>%</div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-danger" style="width: <?= $recommendPct ?>%"></div>
                </div>
                <small class="text-muted mt-1 d-block">Recommend to Friends</small>
            </div>
        </div>
    </div>

    <div class="row g-4 justify-content-center">
        <!-- Survey Form -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5 bg-white">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-green-light text-green-dark px-3 py-2"><i class="bi bi-pencil-square me-1"></i> Quick Survey</span>
                    <span class="badge bg-light text-muted border px-2 py-1">Takes 1 minute</span>
                </div>
                <h3 class="fw-bold text-dark mb-2">Help Validate Our Pilot Pricing</h3>
                <p class="text-muted small mb-4">
                    Your responses directly guide Team 4's university pilot research and help us set sustainable price points for students.
                </p>

                <form action="<?= BASE_URL ?>/survey.php" method="POST">
                    <?= csrfField() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="student_name" class="form-control" value="<?= htmlspecialchars($loggedIn['full_name'] ?? '') ?>" placeholder="e.g. Alex Nguyen" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Student Email <span class="text-danger">*</span></label>
                            <input type="email" name="student_email" class="form-control" value="<?= htmlspecialchars($loggedIn['email'] ?? '') ?>" placeholder="student@university.edu.vn" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Academic Status</label>
                        <select name="student_role" class="form-select">
                            <option value="Undergraduate Student">Undergraduate Student (Bachelor's)</option>
                            <option value="Postgraduate Student">Postgraduate / Master's Student</option>
                            <option value="University Faculty / Staff">University Faculty or Staff</option>
                            <option value="Local Community Member">Local Community Member</option>
                        </select>
                    </div>

                    <!-- Question 1: Snack Box Price -->
                    <div class="p-3 bg-light rounded-3 mb-3">
                        <label class="form-label fw-bold small text-green-dark d-block mb-1">
                            1. How do you feel about the proposed price of <strong>40,000 VND</strong> for a Snack Box?
                        </label>
                        <small class="text-muted d-block mb-2">(Contains 2-3 freshly baked surplus pastries valued at ~95,000 VND)</small>
                        <div class="d-flex flex-wrap gap-2">
                            <input type="radio" class="btn-check" name="snack_box_price_rating" id="snackOpt1" value="Great Value" checked>
                            <label class="btn btn-outline-success btn-sm px-3" for="snackOpt1"><i class="bi bi-star-fill me-1"></i> Great Value</label>

                            <input type="radio" class="btn-check" name="snack_box_price_rating" id="snackOpt2" value="Fair Price">
                            <label class="btn btn-outline-secondary btn-sm px-3" for="snackOpt2"><i class="bi bi-check-circle me-1"></i> Fair Price</label>

                            <input type="radio" class="btn-check" name="snack_box_price_rating" id="snackOpt3" value="A Bit High">
                            <label class="btn btn-outline-secondary btn-sm px-3" for="snackOpt3"><i class="bi bi-dash-circle me-1"></i> A Bit High</label>
                        </div>
                    </div>

                    <!-- Question 2: Meal Box Price -->
                    <div class="p-3 bg-light rounded-3 mb-3">
                        <label class="form-label fw-bold small text-green-dark d-block mb-1">
                            2. How do you feel about the proposed price of <strong>70,000 VND</strong> for a Meal Box?
                        </label>
                        <small class="text-muted d-block mb-2">(Contains a full savory deli sandwich, warm quiche tart, and dessert valued at ~160,000 VND)</small>
                        <div class="d-flex flex-wrap gap-2">
                            <input type="radio" class="btn-check" name="meal_box_price_rating" id="mealOpt1" value="Great Value">
                            <label class="btn btn-outline-success btn-sm px-3" for="mealOpt1"><i class="bi bi-star-fill me-1"></i> Great Value</label>

                            <input type="radio" class="btn-check" name="meal_box_price_rating" id="mealOpt2" value="Fair Price" checked>
                            <label class="btn btn-outline-secondary btn-sm px-3" for="mealOpt2"><i class="bi bi-check-circle me-1"></i> Fair Price</label>

                            <input type="radio" class="btn-check" name="meal_box_price_rating" id="mealOpt3" value="A Bit High">
                            <label class="btn btn-outline-secondary btn-sm px-3" for="mealOpt3"><i class="bi bi-dash-circle me-1"></i> A Bit High</label>
                        </div>
                    </div>

                    <!-- Question 3: Scheduled Pickup Convenience -->
                    <div class="p-3 bg-light rounded-3 mb-3">
                        <label class="form-label fw-bold small text-green-dark d-block mb-1">
                            3. Is the scheduled evening pickup model (17:30 – 21:00 at bakeries near campus) convenient?
                        </label>
                        <div class="d-flex flex-wrap gap-2">
                            <input type="radio" class="btn-check" name="pickup_convenience" id="pickOpt1" value="Very Convenient" checked>
                            <label class="btn btn-outline-success btn-sm px-3" for="pickOpt1"><i class="bi bi-geo-alt-fill me-1"></i> Very Convenient</label>

                            <input type="radio" class="btn-check" name="pickup_convenience" id="pickOpt2" value="Acceptable">
                            <label class="btn btn-outline-secondary btn-sm px-3" for="pickOpt2"><i class="bi bi-clock me-1"></i> Acceptable</label>

                            <input type="radio" class="btn-check" name="pickup_convenience" id="pickOpt3" value="Inconvenient">
                            <label class="btn btn-outline-secondary btn-sm px-3" for="pickOpt3"><i class="bi bi-x-circle me-1"></i> Inconvenient</label>
                        </div>
                    </div>

                    <!-- Question 4: Additional Notes -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Additional Suggestions / Feedback for Team 4 (optional)</label>
                        <textarea name="feedback_notes" rows="3" class="form-control" placeholder="What other food or bakery items would you like to see? Any timing preferences?"></textarea>
                    </div>

                    <!-- Would Recommend Checkbox -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="would_recommend" id="recCheck" checked>
                        <label class="form-check-label small" for="recCheck">
                            I would recommend NomReNom's surplus food rescue to my friends and roommates.
                        </label>
                    </div>

                    <button type="submit" class="btn btn-nrn-primary btn-lg w-100 py-3 shadow">
                        <i class="bi bi-send-fill me-2"></i> Submit Pilot Survey Response
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Column: Recent Student Opinions & Pilot Goals -->
        <div class="col-lg-5">
            <!-- Team 4 Project Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <span class="badge bg-green-light text-green-dark w-auto align-self-start px-2 py-1 small mb-2">
                    Team 4 Milestone
                </span>
                <h5 class="fw-bold text-dark mb-2">Why This Survey Matters</h5>
                <p class="text-muted small mb-3">
                    In our MVP progress review, our primary objective is to prove that university students accept flat pricing of <strong>40,000₫</strong> and <strong>70,000₫</strong> while ensuring local bakeries achieve zero food waste.
                </p>
                <div class="p-3 bg-light rounded-3 border small">
                    <div class="fw-bold text-dark mb-1"><i class="bi bi-info-circle-fill text-success me-1"></i> 2-Week Pilot Plan Target:</div>
                    <ul class="m-0 ps-3 text-muted">
                        <li>30+ student reservations</li>
                        <li>80%+ successful collection rate</li>
                        <li>20+ price acceptance survey responses (Current: <strong><?= $totalCount ?></strong>)</li>
                    </ul>
                </div>
            </div>

            <!-- Student Feedback Stream -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-chat-quote-fill text-warning me-2"></i>Recent Student Quotes</h6>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($recentSurveys as $s): ?>
                        <div class="p-3 bg-light rounded-3 small">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="text-green-dark"><?= htmlspecialchars($s['student_name']) ?></strong>
                                <span class="badge bg-white text-muted border"><?= htmlspecialchars($s['snack_box_price_rating']) ?></span>
                            </div>
                            <p class="text-muted m-0 fst-italic">"<?= htmlspecialchars($s['feedback_notes']) ?>"</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
