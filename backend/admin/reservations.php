<?php
/**
 * NomReNom - Administrator Reservation Management
 * Filter, search, status update, and CSV data export
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$pdo = getDbConnection();
$adminId = currentUserId();

// 1. Handle Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportStmt = $pdo->query("SELECT r.reservation_code, 
                                      COALESCE(u.full_name, g.full_name) AS customer_name,
                                      COALESCE(u.email, g.email) AS customer_email,
                                      COALESCE(u.phone, g.phone) AS customer_phone,
                                      b.name AS bakery_name,
                                      s.pickup_date, s.start_time, s.end_time,
                                      r.total_amount, r.reservation_status, r.payment_method, r.payment_status,
                                      r.created_at
                               FROM reservations r
                               JOIN bakeries b ON r.bakery_id = b.id
                               JOIN pickup_slots s ON r.pickup_slot_id = s.id
                               LEFT JOIN users u ON r.user_id = u.id
                               LEFT JOIN guest_customers g ON r.guest_id = g.id
                               ORDER BY r.created_at DESC");
    $data = $exportStmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=nomrenom_reservations_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, [
        'Reservation Code', 'Customer Name', 'Email', 'Phone', 'Bakery Partner', 
        'Pickup Date', 'Start Time', 'End Time', 'Total Amount (VND)', 
        'Reservation Status', 'Payment Method', 'Payment Status', 'Created At'
    ]);

    foreach ($data as $row) {
        fputcsv($output, [
            $row['reservation_code'],
            $row['customer_name'] ?? 'Guest Customer',
            $row['customer_email'],
            $row['customer_phone'],
            $row['bakery_name'],
            $row['pickup_date'],
            $row['start_time'],
            $row['end_time'],
            (float)$row['total_amount'],
            $row['reservation_status'],
            $row['payment_method'],
            $row['payment_status'],
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// 2. Handle Quick Status Updates (e.g. Mark as Collected & Paid at pickup)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = clean($_POST['action'] ?? '');
    $resId = (int)($_POST['reservation_id'] ?? 0);

    if ($action === 'mark_collected' && $resId > 0) {
        $update = $pdo->prepare("UPDATE reservations SET 
            reservation_status = 'Collected', 
            payment_status = 'Paid',
            collected_at = NOW() 
            WHERE id = ?");
        $update->execute([$resId]);

        // Also update payment record
        $pdo->prepare("UPDATE payments SET payment_status = 'Paid', payment_date = NOW() WHERE reservation_id = ?")
            ->execute([$resId]);

        logAdminActivity($pdo, $adminId, 'MARK_COLLECTED', 'RESERVATIONS', $resId, 'Marked reservation as Collected & Paid.');
        setFlash('success', 'Reservation status updated to <strong>Collected & Paid</strong>.');
        header('Location: ' . BASE_URL . '/admin/reservations.php');
        exit;
    }
}

// 3. Search and Filters
$search = clean($_GET['q'] ?? '');
$statusFilter = clean($_GET['status'] ?? '');
$bakeryFilter = (int)($_GET['bakery'] ?? 0);
$dateFilter = clean($_GET['date'] ?? '');

$bakeries = $pdo->query("SELECT id, name FROM bakeries ORDER BY name ASC")->fetchAll();

$sql = "SELECT r.*, b.name AS bakery_name, 
               s.pickup_date, s.start_time, s.end_time,
               COALESCE(u.full_name, g.full_name) AS customer_name,
               COALESCE(u.email, g.email) AS customer_email,
               COALESCE(u.phone, g.phone) AS customer_phone 
        FROM reservations r 
        JOIN bakeries b ON r.bakery_id = b.id 
        JOIN pickup_slots s ON r.pickup_slot_id = s.id 
        LEFT JOIN users u ON r.user_id = u.id 
        LEFT JOIN guest_customers g ON r.guest_id = g.id 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (r.reservation_code LIKE ? OR u.full_name LIKE ? OR g.full_name LIKE ? OR u.phone LIKE ? OR g.phone LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}

if (!empty($statusFilter)) {
    $sql .= " AND r.reservation_status = ?";
    $params[] = $statusFilter;
}

if ($bakeryFilter > 0) {
    $sql .= " AND r.bakery_id = ?";
    $params[] = $bakeryFilter;
}

if (!empty($dateFilter)) {
    $sql .= " AND s.pickup_date = ?";
    $params[] = $dateFilter;
}

$sql .= " ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

$pageTitle = 'Reservation Management';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="font-serif text-green-dark m-0">Customer Reservations</h4>
        <small class="text-muted">Monitor orders, reconcile pickup payments, and verify collections</small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/admin/reservations.php?export=csv" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Export to CSV
        </a>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
    <form action="<?= BASE_URL ?>/admin/reservations.php" method="GET" class="row g-2 align-items-center">
        <div class="col-lg-3 col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control border-start-0" placeholder="Code, name, phone...">
            </div>
        </div>
        <div class="col-lg-3 col-md-3 col-6">
            <select name="status" class="form-select">
                <option value="">-- All Order Statuses --</option>
                <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Confirmed" <?= $statusFilter === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="Preparing" <?= $statusFilter === 'Preparing' ? 'selected' : '' ?>>Preparing</option>
                <option value="Ready for Pickup" <?= $statusFilter === 'Ready for Pickup' ? 'selected' : '' ?>>Ready for Pickup</option>
                <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                <option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-lg-3 col-md-3 col-6">
            <select name="bakery" class="form-select">
                <option value="">-- All Bakeries --</option>
                <?php foreach ($bakeries as $bk): ?>
                    <option value="<?= $bk['id'] ?>" <?= $bakeryFilter == $bk['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($bk['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-2 col-md-6 col-6">
            <input type="date" name="date" value="<?= htmlspecialchars($dateFilter) ?>" class="form-control" title="Filter by pickup date">
        </div>
        <div class="col-lg-1 col-md-6 col-6 d-flex gap-1">
            <button type="submit" class="btn btn-nrn-primary w-100" title="Search">
                <i class="bi bi-funnel"></i>
            </button>
            <?php if (!empty($search) || !empty($statusFilter) || $bakeryFilter > 0 || !empty($dateFilter)): ?>
                <a href="<?= BASE_URL ?>/admin/reservations.php" class="btn btn-outline-secondary" title="Clear Filters">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table admin-table m-0">
            <thead>
                <tr>
                    <th>Ref Code</th>
                    <th>Customer</th>
                    <th>Bakery & Pickup Slot</th>
                    <th>Total</th>
                    <th>Order Status</th>
                    <th>Payment</th>
                    <th>Timestamp</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservations)): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">No reservations found matching your criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($reservations as $r): ?>
                        <tr>
                            <td>
                                <strong class="text-green-dark font-monospace">#<?= htmlspecialchars($r['reservation_code']) ?></strong>
                            </td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($r['customer_name'] ?? 'Guest Customer') ?></div>
                                <small class="text-muted d-block"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($r['customer_phone'] ?? 'N/A') ?></small>
                            </td>
                            <td>
                                <div><strong><?= htmlspecialchars($r['bakery_name']) ?></strong></div>
                                <small class="text-muted">
                                    <i class="bi bi-calendar-event me-1"></i><?= formatDateTime($r['pickup_date'], 'M d, Y') ?> (<?= formatTime($r['start_time']) ?>–<?= formatTime($r['end_time']) ?>)
                                </small>
                            </td>
                            <td>
                                <strong class="text-green-dark fs-6"><?= formatVnd($r['total_amount']) ?></strong>
                            </td>
                            <td>
                                <span class="status-badge status-<?= strtolower($r['reservation_status']) ?>">
                                    <?= htmlspecialchars($r['reservation_status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge payment-<?= strtolower(str_replace(' ', '', $r['payment_status'])) ?>">
                                    <?= htmlspecialchars($r['payment_status']) ?>
                                </span>
                                <small class="text-muted d-block mt-1"><?= $r['payment_method'] === 'Pickup' ? 'At Pickup' : 'Online' ?></small>
                            </td>
                            <td>
                                <small class="text-muted"><?= formatDateTime($r['created_at']) ?></small>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="<?= BASE_URL ?>/admin/reservation_view.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary" title="View details & update">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($r['reservation_status'] !== 'Collected' && $r['reservation_status'] !== 'Cancelled'): ?>
                                        <form action="<?= BASE_URL ?>/admin/reservations.php" method="POST" class="d-inline" onsubmit="return confirm('Confirm that the customer has collected the food box and paid in full?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="mark_collected">
                                            <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Mark Collected & Paid">
                                                <i class="bi bi-check2-circle"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
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
