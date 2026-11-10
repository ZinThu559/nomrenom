<?php
/**
 * NomReNom - Administrator Dashboard Overview & Analytics
 * Live KPI metrics and Chart.js visualizations
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Dashboard Overview';
$pdo = getDbConnection();

// 1. KPI Statistics from live database
$surveyCount = (int)$pdo->query("SELECT COUNT(*) FROM pilot_surveys")->fetchColumn();
$totalReservations = (int)$pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$confirmedCount = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE reservation_status = 'Confirmed'")->fetchColumn();
$collectedCount = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE reservation_status = 'Collected'")->fetchColumn();
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE reservation_status = 'Pending'")->fetchColumn();
$availableBoxes = (int)$pdo->query("SELECT COALESCE(SUM(available_qty), 0) FROM food_boxes WHERE is_active = 1")->fetchColumn();
$customerCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$bakeryCount = (int)$pdo->query("SELECT COUNT(*) FROM bakeries WHERE is_active = 1")->fetchColumn();

// Contact Messages
$unreadMessagesCount = 0;
try {
    $unreadMessagesCount = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
} catch (Exception $e) {}

// Revenue Statistics
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM reservations WHERE payment_status = 'Paid'")->fetchColumn();
$pendingRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM reservations WHERE payment_status IN ('Pay at Pickup', 'Pending') AND reservation_status != 'Cancelled'")->fetchColumn();

// Repeat Customer Rate: Percentage of customers with >= 2 reservations
$repeatUsers = (int)$pdo->query("SELECT COUNT(*) FROM (
    SELECT user_id FROM reservations WHERE user_id IS NOT NULL GROUP BY user_id HAVING COUNT(*) > 1
) sub")->fetchColumn();
$distinctUsers = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM reservations WHERE user_id IS NOT NULL")->fetchColumn();
$repeatRate = $distinctUsers > 0 ? (int)round(($repeatUsers / $distinctUsers) * 100) : 0;

// Collection Success Rate
$collectionRate = $totalReservations > 0 ? (int)round(($collectedCount / $totalReservations) * 100) : 0;

// 2. Data for Chart.js: 7-day Trend (Last 7 days)
$trendLabels = [];
$trendReservations = [];
$trendCollected = [];

for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $displayD = date('M d', strtotime("-$i days"));
    $trendLabels[] = $displayD;

    $stmtR = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE DATE(created_at) = ?");
    $stmtR->execute([$d]);
    $trendReservations[] = (int)$stmtR->fetchColumn();

    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE DATE(created_at) = ? AND reservation_status = 'Collected'");
    $stmtC->execute([$d]);
    $trendCollected[] = (int)$stmtC->fetchColumn();
}

// 3. Data for Box Ratio: Snack Box vs Meal Box ordered
$snackOrdered = (int)$pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM reservation_items ri JOIN food_boxes fb ON ri.food_box_id = fb.id WHERE fb.box_type = 'snack_box'")->fetchColumn();
$mealOrdered = (int)$pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM reservation_items ri JOIN food_boxes fb ON ri.food_box_id = fb.id WHERE fb.box_type = 'meal_box'")->fetchColumn();

// 4. Recent Reservations (Top 6)
$recentStmt = $pdo->query("SELECT r.*, b.name AS bakery_name, 
                                  COALESCE(u.full_name, g.full_name) AS customer_name 
                           FROM reservations r 
                           JOIN bakeries b ON r.bakery_id = b.id 
                           LEFT JOIN users u ON r.user_id = u.id 
                           LEFT JOIN guest_customers g ON r.guest_id = g.id 
                           ORDER BY r.created_at DESC LIMIT 6");
$recentReservations = $recentStmt->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- Executive Commercial Dashboard Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark m-0">Food Ordering Operations & Analytics</h3>
        <p class="text-muted small m-0">Real-time overview of customer orders, revenue, inventory, and partner bakeries</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/admin/messages.php" class="btn btn-outline-danger btn-sm position-relative">
            <i class="bi bi-chat-left-dots me-1"></i> Customer Messages
            <?php if ($unreadMessagesCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= $unreadMessagesCount ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/box_form.php" class="btn btn-nrn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Add Food Product
        </a>
        <a href="<?= BASE_URL ?>/admin/categories.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-tags me-1"></i> Categories
        </a>
    </div>
</div>

<?php if ($unreadMessagesCount > 0): ?>
<div class="alert alert-warning border-0 shadow-sm rounded-4 d-flex align-items-center justify-content-between p-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-warning text-dark rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            <i class="bi bi-envelope-exclamation-fill fs-5"></i>
        </div>
        <div>
            <strong class="d-block text-dark">You have <?= $unreadMessagesCount ?> unread customer message(s) from the Contact Us page</strong>
            <small class="text-muted">A customer or partner bakery submitted a new inquiry. Review it to respond promptly.</small>
        </div>
    </div>
    <a href="<?= BASE_URL ?>/admin/messages.php" class="btn btn-sm btn-dark px-3 rounded-pill">View Inquiries</a>
</div>
<?php endif; ?>

<!-- KPI Stat Cards Grid -->
<div class="row g-3 mb-4">
    <!-- Card 1: Settled Revenue -->
    <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card border-start border-4 border-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-lbl">Settled Revenue</div>
                    <div class="stat-val text-success"><?= formatVnd($totalRevenue) ?></div>
                    <small class="text-muted">Pending Collection: <?= formatVnd($pendingRevenue) ?></small>
                </div>
                <div class="stat-icon-wrapper bg-green-light text-green-dark">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Orders -->
    <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card border-start border-4 border-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-lbl">Total Customer Orders</div>
                    <div class="stat-val text-primary"><?= number_format($totalReservations) ?></div>
                    <small class="text-success"><i class="bi bi-clock-history"></i> <?= $confirmedCount + $pendingCount ?> active orders</small>
                </div>
                <div class="stat-icon-wrapper bg-light text-primary">
                    <i class="bi bi-receipt"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Food Stock Available -->
    <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card border-start border-4 border-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-lbl">Food Stock on Menu</div>
                    <div class="stat-val text-dark"><?= number_format($availableBoxes) ?></div>
                    <small class="text-muted"><i class="bi bi-shop"></i> Across <?= $bakeryCount ?> partner bakeries</small>
                </div>
                <div class="stat-icon-wrapper bg-light text-warning">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Customers & Members -->
    <div class="col-xl-3 col-sm-6">
        <div class="admin-stat-card border-start border-4 border-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-lbl">Registered Customers</div>
                    <div class="stat-val text-dark"><?= number_format($customerCount) ?></div>
                    <small class="text-muted"><i class="bi bi-people"></i> Verified customer accounts</small>
                </div>
                <div class="stat-icon-wrapper bg-light text-info">
                    <i class="bi bi-person-check-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Business Intelligence Charts Row -->
<div class="row g-4 mb-4">
    <!-- Chart 1: 7-Day Trend -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0 text-green-dark fw-bold"><i class="bi bi-graph-up me-2 text-success"></i>7-Day Reservation & Collection Trends</h6>
                <span class="badge bg-light text-muted border">Live Data</span>
            </div>
            <div style="height: 280px;">
                <canvas id="reservationsTrendChart" 
                        data-labels='<?= json_encode($trendLabels) ?>' 
                        data-reservations='<?= json_encode($trendReservations) ?>' 
                        data-collected='<?= json_encode($trendCollected) ?>'></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 2: Snack vs Meal Ratio & Repeat Customers -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h6 class="m-0 text-green-dark fw-bold mb-3"><i class="bi bi-pie-chart-fill me-2 text-warning"></i>Snack Box vs Meal Box Volume</h6>
            <div style="height: 200px;" class="mb-3">
                <canvas id="boxRatioChart" 
                        data-snack="<?= $snackOrdered ?: 3 ?>" 
                        data-meal="<?= $mealOrdered ?: 2 ?>"></canvas>
            </div>
            <div class="p-3 bg-light rounded-3 small">
                <div class="d-flex justify-content-between mb-1">
                    <span>Repeat Customer Rate:</span>
                    <strong class="text-green-dark"><?= $repeatRate ?>%</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Active Bakeries:</span>
                    <strong><?= $bakeryCount ?> partners</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Reservations Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="p-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="m-0 text-green-dark fw-bold"><i class="bi bi-list-check me-2 text-success"></i>Recent Customer Reservations</h6>
        <a href="<?= BASE_URL ?>/admin/reservations.php" class="btn btn-sm btn-outline-success">
            View All Reservations <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <div class="table-responsive">
        <table class="table admin-table m-0">
            <thead>
                <tr>
                    <th>Ref Code</th>
                    <th>Customer</th>
                    <th>Bakery</th>
                    <th>Amount</th>
                    <th>Reservation Status</th>
                    <th>Payment</th>
                    <th>Timestamp</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentReservations)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No reservations recorded in database yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentReservations as $row): ?>
                        <tr>
                            <td>
                                <strong class="text-green-dark font-monospace">#<?= htmlspecialchars($row['reservation_code']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($row['customer_name'] ?? 'Guest Customer') ?></td>
                            <td><?= htmlspecialchars($row['bakery_name']) ?></td>
                            <td><strong><?= formatVnd($row['total_amount']) ?></strong></td>
                            <td>
                                <span class="status-badge status-<?= strtolower($row['reservation_status']) ?>">
                                    <?= htmlspecialchars($row['reservation_status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge payment-<?= strtolower(str_replace(' ', '', $row['payment_status'])) ?>">
                                    <?= htmlspecialchars($row['payment_status']) ?>
                                </span>
                            </td>
                            <td><small class="text-muted"><?= formatDateTime($row['created_at']) ?></small></td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/admin/reservation_view.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-light border" title="View details">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
