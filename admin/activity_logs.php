<?php
/**
 * NomReNom - Administrator Activity Audit Logs
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$pdo = getDbConnection();

$stmt = $pdo->query("SELECT l.*, u.full_name AS admin_name, u.email AS admin_email 
                     FROM admin_activity_logs l 
                     JOIN users u ON l.admin_user_id = u.id 
                     ORDER BY l.created_at DESC LIMIT 100");
$logs = $stmt->fetchAll();

$pageTitle = 'Activity Audit Logs';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-4">
    <h4 class="font-serif text-green-dark m-0">System Activity Audit Trail</h4>
    <small class="text-muted">Immutable log of administrative logins, status transitions, inventory changes, and partner operations</small>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table admin-table m-0">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Administrator</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Operation Details</th>
                    <th>Client IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No activity records logged yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><small class="text-muted font-monospace"><?= formatDateTime($log['created_at'], 'M d, Y H:i:s') ?></small></td>
                            <td>
                                <strong><?= htmlspecialchars($log['admin_name']) ?></strong>
                                <small class="text-muted d-block"><?= htmlspecialchars($log['admin_email']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($log['action']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-green-light text-green-dark"><?= htmlspecialchars($log['entity_type']) ?> #<?= $log['entity_id'] ?></span>
                            </td>
                            <td>
                                <span class="small"><?= htmlspecialchars($log['details'] ?? 'N/A') ?></span>
                            </td>
                            <td>
                                <small class="text-muted font-monospace"><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>