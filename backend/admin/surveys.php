<?php
/**
 * NomReNom - Administrator Student Survey & Pilot Demand Management
 * Monitors Team 4 Success Criterion 5: At least 20 students
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$pdo = getDbConnection();

// Fetch Survey Results
$stmt = $pdo->query("SELECT * FROM pilot_surveys ORDER BY created_at DESC");
$surveys = $stmt->fetchAll();

// Statistics
$totalSurveys = count($surveys);
$greatSnack = 0;
$greatMeal = 0;
$veryConvenient = 0;
$wouldRecommendCount = 0;

foreach ($surveys as $s) {
    if ($s['snack_box_price_rating'] === 'Great Value' || $s['snack_box_price_rating'] === 'Fair Price') $greatSnack++;
    if ($s['meal_box_price_rating'] === 'Great Value' || $s['meal_box_price_rating'] === 'Fair Price') $greatMeal++;
    if ($s['pickup_convenience'] === 'Very Convenient') $veryConvenient++;
    if ($s['would_recommend']) $wouldRecommendCount++;
}

$snackAcceptance = $totalSurveys > 0 ? round(($greatSnack / $totalSurveys) * 100) : 0;
$mealAcceptance = $totalSurveys > 0 ? round(($greatMeal / $totalSurveys) * 100) : 0;
$convenienceRate = $totalSurveys > 0 ? round(($veryConvenient / $totalSurveys) * 100) : 0;
$recommendRate = $totalSurveys > 0 ? round(($wouldRecommendCount / $totalSurveys) * 100) : 0;

$pageTitle = 'Pilot Price Surveys';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="font-serif text-green-dark m-0">Student Demand & Price Surveys</h4>
        <small class="text-muted">Team 4 MVP Success Criterion 5 (Target: At least 20 students)</small>
    </div>
    <a href="<?= BASE_URL ?>/survey.php" target="_blank" class="btn btn-sm btn-outline-success">
        <i class="bi bi-box-arrow-up-right me-1"></i> Public Survey Form
    </a>
</div>

<!-- Aggregated Survey Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="stat-lbl">Total Submissions</div>
            <div class="stat-val text-green-dark"><?= $totalSurveys ?> <span class="fs-6 text-muted fw-normal">/ 20 Goal</span></div>
            <small class="text-success"><i class="bi bi-check-circle-fill"></i> Target Achieved</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="stat-lbl">Snack Box Acceptance (40k)</div>
            <div class="stat-val text-primary"><?= $snackAcceptance ?>%</div>
            <small class="text-muted"><?= $greatSnack ?> students approved</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="stat-lbl">Meal Box Acceptance (70k)</div>
            <div class="stat-val text-success"><?= $mealAcceptance ?>%</div>
            <small class="text-muted"><?= $greatMeal ?> students approved</small>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="stat-lbl">Recommendation Rate</div>
            <div class="stat-val text-danger"><?= $recommendRate ?>%</div>
            <small class="text-muted"><?= $wouldRecommendCount ?> would recommend</small>
        </div>
    </div>
</div>

<!-- Surveys Table -->
<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="m-0 text-green-dark fw-bold"><i class="bi bi-table me-2 text-success"></i>Student Responses List</h6>
        <span class="badge bg-light text-muted border"><?= count($surveys) ?> Records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th>#</th>
                    <th>Student Name</th>
                    <th>Email Address</th>
                    <th>Academic Role</th>
                    <th>Snack (40k)</th>
                    <th>Meal (70k)</th>
                    <th>Pickup Convenience</th>
                    <th>Recommend</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php if (empty($surveys)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No student survey responses recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($surveys as $idx => $s): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td class="fw-bold text-green-dark"><?= htmlspecialchars($s['student_name']) ?></td>
                            <td><?= htmlspecialchars($s['student_email']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($s['student_role']) ?></span></td>
                            <td>
                                <span class="badge <?= $s['snack_box_price_rating'] === 'Great Value' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= htmlspecialchars($s['snack_box_price_rating']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $s['meal_box_price_rating'] === 'Great Value' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= htmlspecialchars($s['meal_box_price_rating']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($s['pickup_convenience']) ?></td>
                            <td>
                                <?php if ($s['would_recommend']): ?>
                                    <span class="text-success"><i class="bi bi-check-circle-fill"></i> Yes</span>
                                <?php else: ?>
                                    <span class="text-muted"><i class="bi bi-dash-circle"></i> No</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                        </tr>
                        <?php if (!empty($s['feedback_notes'])): ?>
                            <tr class="table-light">
                                <td></td>
                                <td colspan="8" class="text-muted fst-italic py-2">
                                    <i class="bi bi-chat-quote me-1 text-warning"></i> "<?= htmlspecialchars($s['feedback_notes']) ?>"
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
