<?php
/**
 * NomReNom - Admin Dashboard Header & Navigation
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

requireAdmin();
$adminUser = currentUser();
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' – ' : '' ?>Admin Portal – <?= APP_NAME ?></title>
    
    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Admin & Main CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css?v=<?= time() ?>">
    
    <!-- Chart.js for Visual Business Intelligence -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-body">
<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-brand">
            <span><i class="bi bi-basket2-fill me-2 text-warning"></i>NomReNom</span>
            <small>ADMIN</small>
        </div>

        <ul class="admin-menu">
            <li class="admin-menu-header">Overview</li>
            <li>
                <a href="<?= BASE_URL ?>/admin/index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard Analytics
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/reports.php" class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>">
                    <i class="bi bi-graph-up-arrow"></i> Reports & Insights
                </a>
            </li>

            <li class="admin-menu-header">Operations</li>
            <li>
                <a href="<?= BASE_URL ?>/admin/reservations.php" class="<?= $currentPage === 'reservations.php' || $currentPage === 'reservation_view.php' ? 'active' : '' ?>">
                    <i class="bi bi-receipt"></i> Customer Orders
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/boxes.php" class="<?= $currentPage === 'boxes.php' || $currentPage === 'box_form.php' ? 'active' : '' ?>">
                    <i class="bi bi-box-seam"></i> Food Products & Prices
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/categories.php" class="<?= $currentPage === 'categories.php' ? 'active' : '' ?>">
                    <i class="bi bi-tags"></i> Food Categories
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/bakeries.php" class="<?= $currentPage === 'bakeries.php' || $currentPage === 'bakery_form.php' ? 'active' : '' ?>">
                    <i class="bi bi-shop"></i> Bakery Partners
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/payments.php" class="<?= $currentPage === 'payments.php' ? 'active' : '' ?>">
                    <i class="bi bi-credit-card-2-front"></i> Payments & Transactions
                </a>
            </li>

            <li class="admin-menu-header">Users & Feedback</li>
            <li>
                <a href="<?= BASE_URL ?>/admin/customers.php" class="<?= $currentPage === 'customers.php' ? 'active' : '' ?>">
                    <i class="bi bi-people"></i> Customers & Members
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/messages.php" class="<?= $currentPage === 'messages.php' ? 'active' : '' ?>">
                    <i class="bi bi-chat-left-dots"></i> Customer Messages
                    <?php
                    try {
                        $hdrPdo = getDbConnection();
                        $unreadMsgCount = (int)$hdrPdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
                        if ($unreadMsgCount > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-auto"><?= $unreadMsgCount ?></span>
                        <?php endif;
                    } catch (Exception $e) {}
                    ?>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/feedback.php" class="<?= $currentPage === 'feedback.php' ? 'active' : '' ?>">
                    <i class="bi bi-star"></i> Customer Reviews
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/admin/activity_logs.php" class="<?= $currentPage === 'activity_logs.php' ? 'active' : '' ?>">
                    <i class="bi bi-journal-text"></i> Activity Audit Logs
                </a>
            </li>
        </ul>

        <div class="admin-sidebar-footer">
            <a href="<?= BASE_URL ?>/index.php" target="_blank" class="text-light text-decoration-none small d-block mb-1">
                <i class="bi bi-box-arrow-up-right me-1"></i> View Customer Website
            </a>
            <a href="<?= BASE_URL ?>/admin/logout.php" class="text-danger text-decoration-none small">
                <i class="bi bi-box-arrow-right me-1"></i> Log Out Admin
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="d-flex align-items-center">
                <button class="btn btn-sm btn-light border d-lg-none me-2" id="sidebarToggle" type="button" aria-label="Toggle Navigation">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <h5 class="m-0 font-serif text-green-dark"><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Admin Dashboard' ?></h5>
                    <small class="text-muted">NomReNom Operations & Marketplace Control</small>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-fill-gear text-success fs-5"></i>
                        <span class="fw-bold"><?= htmlspecialchars($adminUser['full_name'] ?? 'Admin') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><span class="dropdown-item-text small text-muted"><?= htmlspecialchars($adminUser['email'] ?? '') ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/activity_logs.php"><i class="bi bi-clock-history me-2"></i> Activity Logs</a></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/admin/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Log Out</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Flash messages inside admin view -->
        <div class="admin-content">
            <?= renderFlash() ?>
