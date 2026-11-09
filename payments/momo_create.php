<?php
/**
 * NomReNom - MoMo Sandbox Payment Gateway Initiator
 * Generates cryptographic signature (HMAC-SHA256)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/payment.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$code = clean($_GET['code'] ?? '');
if (empty($code)) {
    die("Invalid reservation reference code.");
}

$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT * FROM reservations WHERE reservation_code = ? LIMIT 1");
$stmt->execute([$code]);
$reservation = $stmt->fetch();

if (!$reservation) {
    die("Reservation record not found.");
}

$orderId = $reservation['reservation_code'];
$requestId = time() . "";
$amount = (string)(int)$reservation['total_amount'];
$orderInfo = "Payment for NomReNom reservation " . $orderId;
$redirectUrl = MOMO_RETURN_URL;
$ipnUrl = MOMO_RETURN_URL;
$extraData = "";

// Raw Hash string specification for MoMo
$rawHash = "accessKey=" . MOMO_ACCESS_KEY .
           "&amount=" . $amount .
           "&extraData=" . $extraData .
           "&ipnUrl=" . $ipnUrl .
           "&orderId=" . $orderId .
           "&orderInfo=" . $orderInfo .
           "&partnerCode=" . MOMO_PARTNER_CODE .
           "&redirectUrl=" . $redirectUrl .
           "&requestId=" . $requestId .
           "&requestType=captureWallet";

$signature = hash_hmac("sha256", $rawHash, MOMO_SECRET_KEY);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MoMo Sandbox Payment Redirect – NomReNom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="bg-cream d-flex align-items-center min-vh-100">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="card border-0 shadow-lg rounded-4 p-4 text-center bg-white">
                    <div class="mb-3">
                        <span class="badge bg-danger px-3 py-2 fs-6"><i class="bi bi-wallet2 me-1"></i> MoMo E-Wallet Sandbox</span>
                    </div>
                    <h3 class="font-serif text-green-dark mb-1">MoMo Payment Gateway</h3>
                    <p class="text-muted small mb-4">Reservation: <strong><?= htmlspecialchars($reservation['reservation_code']) ?></strong></p>

                    <div class="p-3 bg-light rounded-3 text-start mb-4 small">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total amount payable:</span>
                            <strong class="text-green-dark fs-5"><?= formatVnd($reservation['total_amount']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Order reference:</span>
                            <code><?= htmlspecialchars($reservation['reservation_code']) ?></code>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Hashing algorithm:</span>
                            <span>HMAC-SHA256 (MoMo Standard)</span>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <!-- Local Sandbox Simulator (guaranteed zero latency testing) -->
                        <a href="<?= BASE_URL ?>/payments/sandbox_simulator.php?code=<?= urlencode($reservation['reservation_code']) ?>&gateway=momo" class="btn btn-danger py-2 fw-bold">
                            <i class="bi bi-check2-circle me-2"></i> MoMo Sandbox Simulator (Local Test)
                        </a>

                        <a href="<?= BASE_URL ?>/checkout.php" class="btn btn-link text-muted small mt-2">
                            <i class="bi bi-arrow-left me-1"></i> Return to Checkout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
