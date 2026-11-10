<?php
/**
 * NomReNom - Administrator Customer Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$pdo = getDbConnection();

// Fetch registered customers
$stmt = $pdo->query("SELECT u.*, 
                            (SELECT COUNT(*) FROM reservations WHERE user_id = u.id) AS total_orders,
                            (SELECT COALESCE(SUM(total_amount), 0) FROM reservations WHERE user_id = u.id AND payment_status = 'Paid') AS total_spent 
                     FROM users u 
                     WHERE u.role = 'customer' 
                     ORDER BY u.created_at DESC");
$customers = $stmt->fetchAll();

// Fetch guest customers
$guestStmt = $pdo->query("SELECT g.*, 
                                 (SELECT COUNT(*) FROM reservations WHERE guest_id = g.id) AS total_orders,
                                 (SELECT COALESCE(SUM(total_amount), 0) FROM reservations WHERE guest_id = g.id AND payment_status = 'Paid') AS total_spent 
                          FROM guest_customers g 
                          ORDER BY g.created_at DESC LIMIT 50");
$guests = $guestStmt->fetchAll();

$pageTitle = 'Customer & Member Directory';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-4">
    <h4 class="font-serif text-green-dark m-0">Customer & Member Accounts</h4>
    <small class="text-muted">Review registered customers and guest checkout profiles</small>
</div>

<!-- Tabs: Registered Users vs Guest Customers -->
<ul class="nav nav-pills mb-4" id="customerTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold" id="registered-tab" data-bs-toggle="pill" data-bs-target="#registeredTab" type="button" role="tab">
            <i class="bi bi-person-check-fill me-1"></i> Registered Members (<?= count($customers) ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" id="guest-tab" data-bs-toggle="pill" data-bs-target="#guestTab" type="button" role="tab">
            <i class="bi bi-person-dash-fill me-1"></i> Guest Customers (<?= count($guests) ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="customerTabContent">
    <!-- Tab 1: Registered Customers -->
    <div class="tab-pane fade show active" id="registeredTab" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="table-responsive">
                <table class="table admin-table m-0">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Email Address</th>
                            <th>Phone Number</th>
                            <th>Email Status</th>
                            <th>Total Orders</th>
                            <th>Total Spent (VND)</th>
                            <th>Member Since</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No registered members yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($customers as $c): ?>
                                <tr>
                                    <td>
                                        <strong class="text-green-dark"><?= htmlspecialchars($c['full_name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($c['email']) ?></td>
                                    <td><?= htmlspecialchars($c['phone']) ?></td>
                                    <td>
                                        <span class="badge <?= $c['is_email_verified'] ? 'bg-success' : 'bg-warning text-dark' ?>">
                                            <?= $c['is_email_verified'] ? 'Verified' : 'Unverified' ?>
                                        </span>
                                    </td>
                                    <td><strong class="text-dark"><?= (int)$c['total_orders'] ?></strong> orders</td>
                                    <td><strong class="text-green-dark"><?= formatVnd($c['total_spent']) ?></strong></td>
                                    <td><small class="text-muted"><?= formatDateTime($c['created_at']) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 2: Guest Customers -->
    <div class="tab-pane fade" id="guestTab" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="table-responsive">
                <table class="table admin-table m-0">
                    <thead>
                        <tr>
                            <th>Guest Name</th>
                            <th>Contact Email</th>
                            <th>Phone Number</th>
                            <th>Total Orders</th>
                            <th>Total Spent</th>
                            <th>Created Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($guests)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No guest orders recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($guests as $g): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($g['full_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($g['email']) ?></td>
                                    <td><?= htmlspecialchars($g['phone']) ?></td>
                                    <td><strong><?= (int)$g['total_orders'] ?></strong> orders</td>
                                    <td><strong class="text-green-dark"><?= formatVnd($g['total_spent']) ?></strong></td>
                                    <td><small class="text-muted"><?= formatDateTime($g['created_at']) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
