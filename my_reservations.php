<?php
/**
 * NomReNom - Customer Reservation History
 * Allows viewing orders and cancelling eligible upcoming reservations with stock restoration
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireAuth();
$pdo = getDbConnection();
$userId = currentUserId();

// Handle Reservation Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation_id'])) {
    requireCsrfToken();
    $cancelId = (int)$_POST['cancel_reservation_id'];

    try {
        $pdo->beginTransaction();

        $checkStmt = $pdo->prepare("SELECT r.*, s.pickup_date, s.start_time 
                                    FROM reservations r 
                                    JOIN pickup_slots s ON r.pickup_slot_id = s.id 
                                    WHERE r.id = ? AND r.user_id = ? FOR UPDATE");
        $checkStmt->execute([$cancelId, $userId]);
        $res = $checkStmt->fetch();

        if (!$res) {
            throw new Exception("Valid reservation record not found.");
        }
        if ($res['reservation_status'] === 'Cancelled' || $res['reservation_status'] === 'Collected') {
            throw new Exception("This reservation cannot be cancelled because it is already completed or was previously cancelled.");
        }

        // Restore inventory for each item
        $itemsStmt = $pdo->prepare("SELECT food_box_id, quantity FROM reservation_items WHERE reservation_id = ?");
        $itemsStmt->execute([$cancelId]);
        $items = $itemsStmt->fetchAll();

        foreach ($items as $item) {
            $pdo->prepare("UPDATE food_boxes SET available_qty = available_qty + ? WHERE id = ?")
                ->execute([$item['quantity'], $item['food_box_id']]);
            $pdo->prepare("UPDATE pickup_slots SET reserved_count = GREATEST(0, reserved_count - ?) WHERE id = ?")
                ->execute([$item['quantity'], $res['pickup_slot_id']]);
        }

        // Update reservation status
        $pdo->prepare("UPDATE reservations SET 
            reservation_status = 'Cancelled',
            cancelled_at = NOW(),
            cancellation_reason = 'Cancelled by customer on website' 
            WHERE id = ?")->execute([$cancelId]);

        $pdo->commit();
        setFlash('success', 'Reservation #' . htmlspecialchars($res['reservation_code']) . ' successfully cancelled. Stock has been returned to the bakery.');
        header('Location: ' . BASE_URL . '/my_reservations.php');
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setFlash('danger', $e->getMessage());
        header('Location: ' . BASE_URL . '/my_reservations.php');
        exit;
    }
}

// Filter by status if requested
$statusFilter = clean($_GET['status'] ?? '');
$sql = "SELECT r.*, b.name AS bakery_name, b.address, b.district, 
               s.pickup_date, s.start_time, s.end_time 
        FROM reservations r 
        JOIN bakeries b ON r.bakery_id = b.id 
        JOIN pickup_slots s ON r.pickup_slot_id = s.id 
        WHERE r.user_id = ?";
$params = [$userId];

if (!empty($statusFilter)) {
    $sql .= " AND r.reservation_status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

$pageTitle = 'My Reservations';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h1 class="h2 fw-bold text-dark mb-1">My Reservations</h1>
            <p class="text-muted small m-0">Track pickup times, orders, and statuses for your reserved bakery boxes.</p>
        </div>
        <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-nrn-primary btn-sm mt-3 mt-md-0">
            <i class="bi bi-plus-lg me-1"></i> Reserve Another Box
        </a>
    </div>

    <!-- Status Filter Pills -->
    <div class="d-flex gap-2 overflow-auto pb-2 mb-4">
        <a href="<?= BASE_URL ?>/my_reservations.php" class="btn btn-sm <?= empty($statusFilter) ? 'btn-green-dark text-white' : 'btn-light border' ?>">
            All Orders
        </a>
        <a href="<?= BASE_URL ?>/my_reservations.php?status=Confirmed" class="btn btn-sm <?= $statusFilter === 'Confirmed' ? 'btn-success text-white' : 'btn-light border' ?>">
            Confirmed
        </a>
        <a href="<?= BASE_URL ?>/my_reservations.php?status=Pending" class="btn btn-sm <?= $statusFilter === 'Pending' ? 'btn-warning text-dark' : 'btn-light border' ?>">
            Pending
        </a>
        <a href="<?= BASE_URL ?>/my_reservations.php?status=Collected" class="btn btn-sm <?= $statusFilter === 'Collected' ? 'btn-primary text-white' : 'btn-light border' ?>">
            Collected
        </a>
        <a href="<?= BASE_URL ?>/my_reservations.php?status=Cancelled" class="btn btn-sm <?= $statusFilter === 'Cancelled' ? 'btn-secondary text-white' : 'btn-light border' ?>">
            Cancelled
        </a>
    </div>

    <!-- Reservations List -->
    <?php if (empty($reservations)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <i class="bi bi-calendar-x fs-1 text-muted"></i>
            <h5 class="mt-3 text-muted">No reservations found in this category</h5>
            <p class="small text-muted">Explore fresh surplus boxes from artisan bakeries nearby!</p>
            <div>
                <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-nrn-primary">Explore Food Boxes Now</a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($reservations as $r): 
                // Fetch items for this reservation
                $itStmt = $pdo->prepare("SELECT * FROM reservation_items WHERE reservation_id = ?");
                $itStmt->execute([$r['id']]);
                $items = $itStmt->fetchAll();

                $isCancelable = ($r['reservation_status'] === 'Confirmed' || $r['reservation_status'] === 'Pending');
            ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 pb-3 border-bottom">
                            <div>
                                <span class="badge bg-green-light text-green-dark fs-6 px-3 py-1">
                                    #<?= htmlspecialchars($r['reservation_code']) ?>
                                </span>
                                <small class="text-muted ms-2"><i class="bi bi-clock me-1"></i>Reserved on: <?= formatDateTime($r['created_at']) ?></small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="status-badge status-<?= strtolower($r['reservation_status']) ?>">
                                    <?= htmlspecialchars($r['reservation_status']) ?>
                                </span>
                                <span class="status-badge payment-<?= strtolower(str_replace(' ', '', $r['payment_status'])) ?>">
                                    <?= htmlspecialchars($r['payment_status']) ?>
                                </span>
                            </div>
                        </div>

                        <div class="row g-3 py-3">
                            <div class="col-md-5">
                                <h6 class="text-green-dark fw-bold mb-1"><i class="bi bi-shop me-1 text-success"></i><?= htmlspecialchars($r['bakery_name']) ?></h6>
                                <small class="text-muted d-block"><?= htmlspecialchars($r['address']) ?>, <?= htmlspecialchars($r['district']) ?></small>
                                <div class="mt-2 small text-dark">
                                    <i class="bi bi-calendar2-event text-warning me-1"></i>
                                    <strong>Pickup Window:</strong> <?= formatDateTime($r['pickup_date'], 'M d, Y') ?> (<?= formatTime($r['start_time']) ?> – <?= formatTime($r['end_time']) ?>)
                                </div>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted text-uppercase fw-bold d-block mb-1">Items in Reservation:</small>
                                <?php foreach ($items as $item): ?>
                                    <div class="small text-dark">
                                        <strong>x<?= (int)$item['quantity'] ?></strong> <?= htmlspecialchars($item['box_name']) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="col-md-3 text-md-end">
                                <small class="text-muted d-block">Total Due:</small>
                                <span class="fs-4 fw-bold text-green-dark"><?= formatVnd($r['total_amount']) ?></span>
                                <small class="text-muted d-block"><?= $r['payment_method'] === 'Pickup' ? 'Pay at Pickup' : 'Online Payment' ?></small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="<?= BASE_URL ?>/confirmation.php?code=<?= urlencode($r['reservation_code']) ?>" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-receipt me-1"></i> View Receipt & Voucher
                            </a>

                            <?php if ($isCancelable): ?>
                                <form action="<?= BASE_URL ?>/my_reservations.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this reservation? Stock will be released back to the bakery.');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="cancel_reservation_id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-x-circle me-1"></i> Cancel Reservation
                                    </button>
                                </form>
                            <?php elseif (in_array(strtolower($r['reservation_status']), ['collected', 'completed', 'ready for pickup', 'ready'])): ?>
                                <a href="<?= BASE_URL ?>/feedback.php?code=<?= urlencode($r['reservation_code']) ?>" class="btn btn-warning btn-sm text-dark fw-bold">
                                    <i class="bi bi-star-fill me-1"></i> Review Bakery & Box
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
