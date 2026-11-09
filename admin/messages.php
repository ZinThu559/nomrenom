<?php
/**
 * NomReNom - Administrator Customer Inquiries & Contact Messages
 * View, search, read, reply to, and manage messages submitted via the Contact Us form
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$pdo = getDbConnection();
$adminUser = currentUser();

// Handle Actions (Mark read, mark unread, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    $action = clean($_POST['action'] ?? '');
    $msgId = (int)($_POST['message_id'] ?? 0);

    if ($action === 'mark_read' && $msgId > 0) {
        $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?")->execute([$msgId]);
        setFlash('success', 'Message marked as read.');
    } elseif ($action === 'mark_unread' && $msgId > 0) {
        $pdo->prepare("UPDATE contact_messages SET is_read = 0 WHERE id = ?")->execute([$msgId]);
        setFlash('info', 'Message marked as unread.');
    } elseif ($action === 'delete' && $msgId > 0) {
        $pdo->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$msgId]);
        logAdminActivity($pdo, $adminUser['id'], 'DELETE_MESSAGE', 'CONTACT_MESSAGES', $msgId, 'Deleted customer contact message.');
        setFlash('success', 'Message deleted successfully.');
    }
    header('Location: ' . BASE_URL . '/admin/messages.php');
    exit;
}

// Filters
$statusFilter = clean($_GET['status'] ?? '');
$search = clean($_GET['q'] ?? '');

$sql = "SELECT * FROM contact_messages WHERE 1=1";
$params = [];

if ($statusFilter === 'unread') {
    $sql .= " AND is_read = 0";
} elseif ($statusFilter === 'read') {
    $sql .= " AND is_read = 1";
}

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}

$sql .= " ORDER BY is_read ASC, created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

// KPI Stats
$totalMessages = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
$unreadCount = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
$readCount = $totalMessages - $unreadCount;

$pageTitle = 'Customer Contact Messages';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark m-0">Customer Contact Messages</h3>
        <p class="text-muted small m-0">Inquiries and messages sent via the <strong>Contact Us</strong> page</p>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white border-start border-4 border-danger">
            <span class="text-muted small fw-bold text-uppercase">Unread Messages</span>
            <h3 class="fw-bold text-danger my-1"><?= number_format($unreadCount) ?></h3>
            <small class="text-muted"><i class="bi bi-envelope-exclamation text-danger me-1"></i> Requires response</small>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white border-start border-4 border-success">
            <span class="text-muted small fw-bold text-uppercase">Read / Handled</span>
            <h3 class="fw-bold text-success my-1"><?= number_format($readCount) ?></h3>
            <small class="text-muted"><i class="bi bi-check2-all text-success me-1"></i> Processed inquiries</small>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white border-start border-4 border-primary">
            <span class="text-muted small fw-bold text-uppercase">Total Inquiries</span>
            <h3 class="fw-bold text-primary my-1"><?= number_format($totalMessages) ?></h3>
            <small class="text-muted"><i class="bi bi-inbox me-1"></i> Lifetime contact submissions</small>
        </div>
    </div>
</div>

<!-- Search & Filter Toolbar -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
    <form method="GET" action="<?= BASE_URL ?>/admin/messages.php" class="row g-2 align-items-center">
        <div class="col-md-7">
            <div class="input-group">
                <span class="input-group-text bg-light border-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control border-0 bg-light" placeholder="Search by sender name, email, phone, subject, or message text...">
            </div>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All Messages</option>
                <option value="unread" <?= $statusFilter === 'unread' ? 'selected' : '' ?>>Unread Only</option>
                <option value="read" <?= $statusFilter === 'read' ? 'selected' : '' ?>>Read Only</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-nrn-primary w-100">Filter</button>
            <?php if (!empty($search) || !empty($statusFilter)): ?>
                <a href="<?= BASE_URL ?>/admin/messages.php" class="btn btn-outline-secondary" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Messages Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="bg-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4 py-3" style="width: 50px;">Status</th>
                    <th class="py-3">Sender</th>
                    <th class="py-3">Contact</th>
                    <th class="py-3">Subject & Preview</th>
                    <th class="py-3">Date</th>
                    <th class="pe-4 py-3 text-end" style="width: 170px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($messages)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                            No contact messages found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <tr class="<?= !$msg['is_read'] ? 'fw-semibold bg-light bg-opacity-50' : '' ?>">
                            <td class="ps-4 py-3">
                                <?php if (!$msg['is_read']): ?>
                                    <span class="badge bg-danger rounded-pill p-1" title="Unread Message">
                                        <i class="bi bi-envelope-fill"></i>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border rounded-pill p-1" title="Read">
                                        <i class="bi bi-envelope-open"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3">
                                <strong class="text-dark d-block"><?= htmlspecialchars($msg['name']) ?></strong>
                            </td>
                            <td class="py-3 small">
                                <div>
                                    <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" class="text-decoration-none text-success">
                                        <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($msg['email']) ?>
                                    </a>
                                </div>
                                <?php if (!empty($msg['phone'])): ?>
                                    <div class="text-muted mt-1">
                                        <a href="tel:<?= htmlspecialchars($msg['phone']) ?>" class="text-decoration-none text-muted">
                                            <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($msg['phone']) ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="py-3">
                                <span class="badge bg-light text-dark border mb-1"><?= htmlspecialchars($msg['subject']) ?></span>
                                <div class="small text-muted text-truncate" style="max-width: 320px;">
                                    <?= htmlspecialchars($msg['message']) ?>
                                </div>
                            </td>
                            <td class="py-3 small text-muted">
                                <?= formatDateTime($msg['created_at'], 'M d, Y H:i') ?>
                            </td>
                            <td class="pe-4 py-3 text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#viewModal<?= $msg['id'] ?>"
                                        title="Read Message Details">
                                    <i class="bi bi-eye"></i> View
                                </button>
                                <form method="POST" action="<?= BASE_URL ?>/admin/messages.php" class="d-inline" onsubmit="return confirm('Delete this message?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>

                                <!-- Message Details Modal -->
                                <div class="modal fade" id="viewModal<?= $msg['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-lg text-start">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <div class="modal-header border-bottom">
                                                <div>
                                                    <span class="badge bg-light text-dark border mb-1"><?= htmlspecialchars($msg['subject']) ?></span>
                                                    <h5 class="modal-title fw-bold text-dark m-0">Message from <?= htmlspecialchars($msg['name']) ?></h5>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="p-3 bg-light rounded-3 mb-4 border">
                                                    <div class="row g-2 small">
                                                        <div class="col-sm-6">
                                                            <strong>Sender Name:</strong> <?= htmlspecialchars($msg['name']) ?>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <strong>Received On:</strong> <?= formatDateTime($msg['created_at'], 'M d, Y \a\t H:i') ?>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($msg['email']) ?>"><?= htmlspecialchars($msg['email']) ?></a>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <strong>Phone:</strong> <?= htmlspecialchars($msg['phone'] ?: 'N/A') ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <h6 class="fw-bold text-dark mb-2">Message Content:</h6>
                                                <div class="p-3 bg-white rounded-3 border" style="white-space: pre-line; line-height: 1.6;">
                                                    <?= htmlspecialchars($msg['message']) ?>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top justify-content-between p-3">
                                                <form method="POST" action="<?= BASE_URL ?>/admin/messages.php" class="d-inline">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                                    <?php if (!$msg['is_read']): ?>
                                                        <input type="hidden" name="action" value="mark_read">
                                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                                            <i class="bi bi-check2"></i> Mark as Read
                                                        </button>
                                                    <?php else: ?>
                                                        <input type="hidden" name="action" value="mark_unread">
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                            <i class="bi bi-envelope"></i> Mark as Unread
                                                        </button>
                                                    <?php endif; ?>
                                                </form>

                                                <div class="d-flex gap-2">
                                                    <a href="mailto:<?= htmlspecialchars($msg['email']) ?>?subject=Re: <?= urlencode($msg['subject']) ?>" class="btn btn-sm btn-success">
                                                        <i class="bi bi-reply-fill me-1"></i> Reply via Email
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
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
