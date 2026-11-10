<?php
/**
 * NomReNom - Customer Header Component
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' – ' : '' ?><?= APP_NAME ?> – <?= APP_TAGLINE ?></title>
    
    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Theme Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body>

    <!-- Clean Modern Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-nomrenom sticky-top">
        <div class="container-xl">
            <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
                <span class="brand-icon">
                    <i class="bi bi-basket2-fill"></i>
                </span>
                <span class="brand-text">NomReNom</span>
            </a>

            <button class="navbar-toggler border-0 p-2 shadow-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#navbarNomReNom" aria-controls="navbarNomReNom" aria-label="Toggle navigation">
                <i class="bi bi-list fs-2 text-dark"></i>
            </button>

            <!-- Responsive Navigation Drawer: Offcanvas on Mobile/Tablet (<992px), Inline Navbar on Desktop (>=992px) -->
            <div class="offcanvas-lg offcanvas-end" tabindex="-1" id="navbarNomReNom" aria-labelledby="navbarNomReNomLabel">
                <div class="offcanvas-header border-bottom bg-white">
                    <div class="d-flex align-items-center gap-2">
                        <span class="brand-icon">
                            <i class="bi bi-basket2-fill"></i>
                        </span>
                        <h5 class="offcanvas-title text-dark fw-bold m-0" id="navbarNomReNomLabel">NomReNom</h5>
                    </div>
                    <button type="button" class="btn-close text-reset shadow-none" data-bs-dismiss="offcanvas" data-bs-target="#navbarNomReNom" aria-label="Close"></button>
                </div>
                
                <div class="offcanvas-body d-flex flex-column flex-lg-row justify-content-between p-4 p-lg-0">
                    <ul class="navbar-nav mx-lg-auto mb-3 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php">
                                Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'boxes.php' || $currentPage === 'box_details.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/boxes.php">
                                Browse Snacks
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'track.php' || $currentPage === 'reservation_lookup.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/track.php">
                                Track Pickup
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'how_it_works.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/how_it_works.php">
                                How It Works
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'about.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/about.php">
                                About Us
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'feedback.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/feedback.php">
                                Reviews
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'contact.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/contact.php">
                                Contact
                            </a>
                        </li>
                    </ul>

                    <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 pt-3 pt-lg-0 border-top border-lg-0 flex-shrink-0">
                        <!-- Shopping Cart Button with Live Item Counter Badge -->
                        <a href="<?= BASE_URL ?>/cart.php" class="btn-nrn-nav-cart position-relative" title="Shopping Cart">
                            <i class="bi bi-cart3 cart-icon"></i>
                            <span class="cart-label d-none d-sm-inline">Cart</span>
                            <?php $cCount = function_exists('getCartCount') ? getCartCount() : 0; ?>
                            <span id="nav-cart-badge" class="badge rounded-pill bg-danger" style="<?= $cCount > 0 ? 'display:inline-block;' : 'display:none;' ?>">
                                <?= $cCount ?>
                            </span>
                        </a>

                        <?php if (isLoggedIn()): 
                            $rawName = $user['full_name'] ?? 'Account';
                            $cleanName = trim(preg_replace('/\(.*?\)/', '', $rawName));
                            $nameParts = preg_split('/\s+/', $cleanName);
                            $shortName = $nameParts[0] ?? 'Account';
                        ?>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2 user-profile-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                                    <i class="bi bi-person-circle fs-5 text-success"></i>
                                    <span class="user-short-name small fw-semibold"><?= htmlspecialchars($shortName) ?></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                    <li class="px-3 py-2 border-bottom">
                                        <div class="fw-bold text-dark small"><?= htmlspecialchars($user['full_name'] ?? '') ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                                    </li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/profile.php"><i class="bi bi-person me-2"></i> My Profile</a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/my_reservations.php"><i class="bi bi-receipt me-2"></i> My Orders</a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/feedback.php"><i class="bi bi-star me-2"></i> Rate &amp; Review Bakery</a></li>
                                    <?php if (isAdmin()): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-success fw-bold" href="<?= BASE_URL ?>/admin/index.php"><i class="bi bi-speedometer2 me-2"></i> Admin Portal</a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Log Out</a></li>
                                </ul>
                            </div>
                            <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-nrn-nav-cta">
                                <i class="bi bi-bag-check"></i> <span>Reserve Snacks</span>
                            </a>
                        <?php else: ?>
                            <div class="d-flex flex-column flex-lg-row gap-2 align-items-lg-center">
                                <a href="<?= BASE_URL ?>/login.php" class="btn btn-nrn-nav-login">
                                    Sign In
                                </a>
                                <a href="<?= BASE_URL ?>/boxes.php" class="btn btn-nrn-nav-cta">
                                    <i class="bi bi-bag-check"></i> <span>Reserve Snacks</span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <?php $isHomePage = ($currentPage === 'index.php' || basename($_SERVER['PHP_SELF'] ?? '') === 'index.php'); ?>
    <!-- Main Container & Session Flash Alerts -->
    <main class="<?= $isHomePage ? 'p-0' : 'py-4' ?>">
        <div class="container <?= $isHomePage ? 'pt-2' : '' ?>">
            <?= renderFlash() ?>

            <?php if (!empty($_SESSION['last_email_action'])): 
                $lastAction = $_SESSION['last_email_action'];
            ?>
                <!-- Developer & University Evaluator Local Mail Token Alert -->
                <div class="alert alert-info alert-dismissible fade show border-info d-flex align-items-center mb-4 shadow-sm" role="alert">
                    <i class="bi bi-envelope-paper-fill fs-3 text-info me-3"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block text-dark">Local Mail Dispatcher (Evaluation Mode):</strong>
                        <span>Verification email created for <strong><?= htmlspecialchars($lastAction['to']) ?></strong>:</span>
                        <div class="mt-1">
                            <a href="<?= htmlspecialchars($lastAction['url']) ?>" class="btn btn-sm btn-primary fw-bold">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Click To Open Verification Link (Token Link)
                            </a>
                            <span class="text-muted ms-2 small">Or check detailed payload in <code>storage/logs/mail.log</code></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['last_email_action']); ?>
            <?php endif; ?>
        </div>
