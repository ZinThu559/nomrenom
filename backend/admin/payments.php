<?php
/**
 * NomReNom - Administrator Payments & Transactions Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

requireAdmin();
$pdo = getDbConnection();
$adminUser = currentUser();

// Handle Payment Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_payment_status') {
    requireCsrfToken();
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $newStatus = clean($_POST['payment_status'] ?? '');

    if ($paymentId > 0 && in_array($newStatus, ['Paid', 'Pending', 'Failed', 'Refunded', 'Pay at Pickup'])) {
        $stmt = $pdo->prepare("UPDATE payments SET payment_status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $paymentId]);

        // Also update reservation payment_status
        $resId = (int)$pdo->query("SELECT reservation_id FROM payments WHERE id = $paymentId")->fetchColumn();
        if ($resId > 0) {
            $pdo->prepare("UPDATE reservations SET payment_status = ? WHERE id = ?")->execute([$newStatus, $resId]);
        }

        logAdminActivity($pdo, $adminUser['id'], 'update', 'payment', $paymentId, "Updated payment status to {$newStatus}");
        setFlash('success', "Payment status updated to {$newStatus}.");
        header('Location: ' . BASE_URL . '/admin/payments.php');
        exit;
    }
}

// Filters
$statusFilter = clean($_GET['status'] ?? '');
$methodFilter = clean($_GET['method'] ?? '');
$search = clean($_GET['q'] ?? '');

$sql = "SELECT p.*, r.reservation_code, r.customer_name, r.customer_phone, r.customer_email, b.name as bakery_name 
        FROM payments p 
        JOIN reservations r ON p.reservation_id = r.id 
        JOIN bakeries b ON r.bakery_id = b.id 
        WHERE 1=1";
$params = [];

if (!empty($statusFilter)) {
    $sql .= " AND p.payment_status = ?";
    $params[] = $statusFilter;
}

if (!empty($methodFilter)) {
    $sql .= " AND p.payment_method = ?";
    $params[] = $methodFilter;
}

if (!empty($search)) {
    $sql .= " AND (r.reservation_code LIKE ? OR p.transaction_reference LIKE ? OR r.customer_name LIKE ? OR r.customer_phone LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY p.created_at DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// KPI Summary
$totalCollected = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'Paid'")->fetchColumn();
$totalPending = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'Pending'")->fetchColumn();
$totalTransactions = (int)$pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();

$pageTitle = 'Payments & Transactions';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark m-0">Payment Transactions</h3>
        <p class="text-muted small m-0">Monitor MoMo, VNPay, Bank Transfers, and Cash collections</p>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white border-start border-4 border-success">
            <span class="text-muted small fw-bold text-uppercase">Total Settled Revenue</span>
            <h3 class="fw-bold text-success my-1"><?= formatVnd($totalCollected) ?></h3>
            <small class="text-muted"><i class="bi bi-check-circle-fill text-success me-1"></i> Paid transactions</small>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white border-start border-4 border-warning">
            <span class="text-muted small fw-bold text-uppercase">Awaiting Collection / Pending</span>
            <h3 class="fw-bold text-warning my-1"><?= formatVnd($totalPending) ?></h3>
            <small class="text-muted"><i class="bi bi-clock-history me-1"></i> Cash on pickup & pending QR</small>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white border-start border-4 border-primary">
            <span class="text-muted small fw-bold text-uppercase">Total Order Payments</span>
            <h3 class="fw-bold text-primary my-1"><?= number_format($totalTransactions) ?></h3>
            <small class="text-muted"><i class="bi bi-receipt me-1"></i> Lifetime transactions recorded</small>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
    <form method="GET" action="<?= BASE_URL ?>/admin/payments.php" class="row g-2 align-items-center">
        <div class="col-md-5">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search order code, customer name, phone, or tx ref...">
        </div>
        <div class="col-md-3 col-6">
            <select name="status" class="form-select">
                <option value="">All Payment Statuses</option>
                <option value="Paid" <?= $statusFilter === 'Paid' ? 'selected' : '' ?>>Paid</option>
                <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Failed" <?= $statusFilter === 'Failed' ? 'selected' : '' ?>>Failed</option>
                <option value="Refunded" <?= $statusFilter === 'Refunded' ? 'selected' : '' ?>>Refunded</option>
            </select>
        </div>
        <div class="col-md-2 col-6">
            <select name="method" class="form-select">
                <option value="">All Methods</option>
                <option value="MoMo" <?= $methodFilter === 'MoMo' ? 'selected' : '' ?>>MoMo</option>
                <option value="VNPay" <?= $methodFilter === 'VNPay' ? 'selected' : '' ?>>VNPay</option>
                <option value="Bank Transfer" <?= $methodFilter === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                <option value="Cash on Pickup" <?= $methodFilter === 'Cash on Pickup' ? 'selected' : '' ?>>Cash on Pickup</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-nrn-primary w-100">Filter</button>
            <?php if (!empty($search) || !empty($statusFilter) || !empty($methodFilter)): ?>
                <a href="<?= BASE_URL ?>/admin/payments.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Payments Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="bg-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4 py-3">Order Ref</th>
                    <th class="py-3">Customer</th>
                    <th class="py-3">Payment Method</th>
                    <th class="py-3">Transaction Ref</th>
                    <th class="py-3 text-end">Amount</th>
                    <th class="py-3 text-center">Status</th>
                    <th class="py-3">Date</th>
                    <th class="pe-4 py-3 text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">No transactions found matching your criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td class="ps-4 py-3">
                                <a href="<?= BASE_URL ?>/admin/reservation_view.php?id=<?= $p['reservation_id'] ?>" class="fw-bold text-success text-decoration-none">
                                    <?= htmlspecialchars($p['reservation_code']) ?>
                                </a>
                                <small class="text-muted d-block"><?= htmlspecialchars($p['bakery_name']) ?></small>
                            </td>
                            <td class="py-3">
                                <strong class="text-dark d-block"><?= htmlspecialchars($p['customer_name'] ?? 'Guest Customer') ?></strong>
                                <small class="text-muted"><?= htmlspecialchars($p['customer_phone'] ?? '') ?></small>
                            </td>
                            <td class="py-3">
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-credit-card text-success me-1"></i><?= htmlspecialchars($p['payment_method']) ?>
                                </span>
                            </td>
                            <td class="py-3 text-muted small">
                                <code><?= htmlspecialchars($p['transaction_reference'] ?? 'N/A') ?></code>
                            </td>
                            <td class="py-3 text-end fw-bold text-dark fs-6">
                                <?= formatVnd($p['amount']) ?>
                            </td>
                            <td class="py-3 text-center">
                                <span class="badge <?= $p['payment_status'] === 'Paid' ? 'bg-success' : ($p['payment_status'] === 'Failed' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                    <?= htmlspecialchars($p['payment_status']) ?>
                                </span>
                            </td>
                            <td class="py-3 text-muted small">
                                <?= formatDateTime($p['created_at'], 'M d, Y H:i') ?>
                            </td>
                            <td class="pe-4 py-3 text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#statusModal<?= $p['id'] ?>">
                                    Change Status
                                </button>

                                <!-- Status Modal -->
                                <div class="modal fade" id="statusModal<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-sm text-start">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <form method="POST" action="<?= BASE_URL ?>/admin/payments.php">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="update_payment_status">
                                                <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                                                <div class="modal-header border-bottom">
                                                    <h6 class="modal-title fw-bold">Update Payment Status</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-3">
                                                    <label class="form-label small fw-bold">Select New Status</label>
                                                    <select name="payment_status" class="form-select">
                                                        <option value="Paid" <?= $p['payment_status'] === 'Paid' ? 'selected' : '' ?>>Paid</option>
                                                        <option value="Pending" <?= $p['payment_status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                        <option value="Failed" <?= $p['payment_status'] === 'Failed' ? 'selected' : '' ?>>Failed</option>
                                                        <option value="Refunded" <?= $p['payment_status'] === 'Refunded' ? 'selected' : '' ?>>Refunded</option>
                                                    </select>
                                                </div>
                                                <div class="modal-footer border-top p-2">
                                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-sm btn-nrn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
