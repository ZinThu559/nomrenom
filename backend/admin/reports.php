<?php
/**
 * NomReNom - Administrator Reports & Business Analytics
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$pdo = getDbConnection();

// 1. High Level Metrics
$totalRes = (int)$pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$collectedRes = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE reservation_status = 'Collected'")->fetchColumn();
$cancelledRes = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE reservation_status = 'Cancelled'")->fetchColumn();
$collectionRate = $totalRes > 0 ? (int)round(($collectedRes / $totalRes) * 100) : 0;
$cancellationRate = $totalRes > 0 ? (int)round(($cancelledRes / $totalRes) * 100) : 0;

$repeatUsers = (int)$pdo->query("SELECT COUNT(*) FROM (
    SELECT user_id FROM reservations WHERE user_id IS NOT NULL GROUP BY user_id HAVING COUNT(*) > 1
) sub")->fetchColumn();
$distinctUsers = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM reservations WHERE user_id IS NOT NULL")->fetchColumn();
$repeatRate = $distinctUsers > 0 ? (int)round(($repeatUsers / $distinctUsers) * 100) : 0;

// 2. Popular Food Boxes Ranking
$boxesRanking = $pdo->query("SELECT fb.name, fb.box_type, b.name AS bakery_name, 
                                    COALESCE(SUM(ri.quantity), 0) AS total_sold,
                                    COALESCE(SUM(ri.subtotal), 0) AS total_revenue 
                             FROM food_boxes fb 
                             JOIN bakeries b ON fb.bakery_id = b.id 
                             LEFT JOIN reservation_items ri ON fb.id = ri.food_box_id 
                             GROUP BY fb.id 
                             ORDER BY total_sold DESC, total_revenue DESC")->fetchAll();

// 3. Bakery Participation & Performance
$bakeryPerformance = $pdo->query("SELECT b.name, b.district, 
                                         (SELECT COUNT(*) FROM food_boxes WHERE bakery_id = b.id) AS active_boxes,
                                         (SELECT COUNT(*) FROM reservations WHERE bakery_id = b.id) AS total_orders,
                                         (SELECT COUNT(*) FROM reservations WHERE bakery_id = b.id AND reservation_status = 'Collected') AS collected_orders,
                                         (SELECT COALESCE(SUM(total_amount), 0) FROM reservations WHERE bakery_id = b.id AND payment_status = 'Paid') AS revenue 
                                  FROM bakeries b 
                                  WHERE b.is_active = 1 
                                  ORDER BY total_orders DESC")->fetchAll();

// 4. Price Acceptance (Snack 40k vs Meal 70k)
$snackData = $pdo->query("SELECT COALESCE(SUM(ri.quantity), 0) AS qty, COALESCE(SUM(ri.subtotal), 0) AS revenue 
                          FROM reservation_items ri 
                          JOIN food_boxes fb ON ri.food_box_id = fb.id 
                          WHERE fb.box_type = 'snack_box'")->fetch();
$mealData = $pdo->query("SELECT COALESCE(SUM(ri.quantity), 0) AS qty, COALESCE(SUM(ri.subtotal), 0) AS revenue 
                         FROM reservation_items ri 
                         JOIN food_boxes fb ON ri.food_box_id = fb.id 
                         WHERE fb.box_type = 'meal_box'")->fetch();

// Prepare charts data
$bakeryNames = [];
$bakeryOrders = [];
$bakeryRevenue = [];
foreach ($bakeryPerformance as $bp) {
    $bakeryNames[] = $bp['name'];
    $bakeryOrders[] = (int)$bp['total_orders'];
    $bakeryRevenue[] = (float)$bp['revenue'];
}

$pageTitle = 'Reports & Platform Analytics';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-4">
    <h4 class="font-serif text-green-dark m-0">Platform Performance & Surplus Food Impact</h4>
    <small class="text-muted">Evaluating user adoption, food waste prevention, and commercial revenue acceptance</small>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="admin-stat-card text-center">
            <div class="stat-lbl">Fulfillment Rate</div>
            <div class="stat-val text-success"><?= $collectionRate ?>%</div>
            <small class="text-muted"><?= $collectedRes ?> of <?= $totalRes ?> orders</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="admin-stat-card text-center">
            <div class="stat-lbl">Repeat Customer Rate</div>
            <div class="stat-val text-primary"><?= $repeatRate ?>%</div>
            <small class="text-muted"><?= $repeatUsers ?> users placed 2+ orders</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="admin-stat-card text-center">
            <div class="stat-lbl">Cancellation Rate</div>
            <div class="stat-val text-danger"><?= $cancellationRate ?>%</div>
            <small class="text-muted"><?= $cancelledRes ?> cancelled orders</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="admin-stat-card text-center">
            <div class="stat-lbl">Total Surplus Rescued Value</div>
            <div class="stat-val text-green-dark"><?= formatVnd((float)($snackData['revenue'] + $mealData['revenue'])) ?></div>
            <small class="text-muted">Generated from unsold inventory</small>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h6 class="text-green-dark fw-bold mb-3"><i class="bi bi-bar-chart-fill me-2 text-success"></i>Orders by Partner Bakery</h6>
            <div style="height: 280px;">
                <canvas id="bakeryPerformanceChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h6 class="text-green-dark fw-bold mb-3"><i class="bi bi-pie-chart-fill me-2 text-warning"></i>Product Category Breakdown</h6>
            <div style="height: 200px;" class="mb-3">
                <canvas id="revenuePieChart"></canvas>
            </div>
            <div class="small p-3 bg-light rounded-3">
                <div class="d-flex justify-content-between mb-1">
                    <span>Snack Box (40k):</span>
                    <strong><?= formatVnd($snackData['revenue']) ?> (<?= (int)$snackData['qty'] ?> boxes)</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Meal Box (70k):</span>
                    <strong><?= formatVnd($mealData['revenue']) ?> (<?= (int)$mealData['qty'] ?> boxes)</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Popular Products Ranking Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="p-3 px-4 border-bottom">
        <h6 class="m-0 text-green-dark fw-bold"><i class="bi bi-trophy-fill me-2 text-warning"></i>Most Popular Food Box Rankings</h6>
    </div>
    <div class="table-responsive">
        <table class="table admin-table m-0">
            <thead>
                <tr>
                    <th style="width: 60px;">Rank</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Bakery</th>
                    <th>Boxes Sold</th>
                    <th class="text-end">Revenue (VND)</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1; foreach ($boxesRanking as $box): ?>
                    <tr>
                        <td>
                            <span class="badge <?= $rank === 1 ? 'bg-warning text-dark' : ($rank === 2 ? 'bg-secondary' : ($rank === 3 ? 'bg-danger text-white' : 'bg-light text-dark border')) ?> rounded-pill px-3 py-1">
                                #<?= $rank++ ?>
                            </span>
                        </td>
                        <td><strong><?= htmlspecialchars($box['name']) ?></strong></td>
                        <td>
                            <span class="badge <?= $box['box_type'] === 'snack_box' ? 'bg-danger' : 'bg-success' ?>">
                                <?= $box['box_type'] === 'snack_box' ? 'Snack Box' : 'Meal Box' ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($box['bakery_name']) ?></td>
                        <td><strong><?= (int)$box['total_sold'] ?></strong> boxes</td>
                        <td class="text-end fw-bold text-green-dark"><?= formatVnd($box['total_revenue']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Bakery Performance Bar Chart
    const bpCtx = document.getElementById('bakeryPerformanceChart');
    if (bpCtx && typeof Chart !== 'undefined') {
        new Chart(bpCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($bakeryNames) ?>,
                datasets: [{
                    label: 'Reservations Count',
                    data: <?= json_encode($bakeryOrders) ?>,
                    backgroundColor: '#2D6A4F',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    // 2. Revenue Pie Chart
    const revCtx = document.getElementById('revenuePieChart');
    if (revCtx && typeof Chart !== 'undefined') {
        new Chart(revCtx, {
            type: 'pie',
            data: {
                labels: ['Snack Box (40,000 VND)', 'Meal Box (70,000 VND)'],
                datasets: [{
                    data: [<?= (float)$snackData['revenue'] ?: 40000 ?>, <?= (float)$mealData['revenue'] ?: 70000 ?>],
                    backgroundColor: ['#E76F51', '#1B4332']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>