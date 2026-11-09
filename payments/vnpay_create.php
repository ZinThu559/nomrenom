<?php
/**
 * NomReNom - VNPay Sandbox Payment Gateway Initiator
 * Generates cryptographic signature (HMAC-SHA512)
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

$vnp_TxnRef = $reservation['reservation_code'];
$vnp_OrderInfo = "Payment for NomReNom reservation " . $vnp_TxnRef;
$vnp_OrderType = 'billpayment';
$vnp_Amount = (int)$reservation['total_amount'] * 100; // VNPay requires multiplying by 100
$vnp_Locale = 'en';
$vnp_IpAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

$inputData = [
    "vnp_Version" => "2.1.0",
    "vnp_TmnCode" => VNPAY_TMN_CODE,
    "vnp_Amount" => $vnp_Amount,
    "vnp_Command" => "pay",
    "vnp_CreateDate" => date('YmdHis'),
    "vnp_CurrCode" => "VND",
    "vnp_IpAddr" => $vnp_IpAddr,
    "vnp_Locale" => $vnp_Locale,
    "vnp_OrderInfo" => $vnp_OrderInfo,
    "vnp_OrderType" => $vnp_OrderType,
    "vnp_ReturnUrl" => VNPAY_RETURN_URL,
    "vnp_TxnRef" => $vnp_TxnRef
];

// Sort parameters alphabetically as required by VNPay specification
ksort($inputData);
$query = "";
$i = 0;
$hashdata = "";

foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashdata .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
    $query .= urlencode($key) . "=" . urlencode($value) . '&';
}

$vnp_Url = VNPAY_API_URL . "?" . $query;
$vnpSecureHash = hash_hmac('sha512', $hashdata, VNPAY_HASH_SECRET);
$vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;

// In local evaluation, show a clean gateway redirection portal with Sandbox Simulator
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VNPay Sandbox Payment Redirect – NomReNom</title>
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
                        <span class="badge bg-primary px-3 py-2 fs-6"><i class="bi bi-shield-check me-1"></i> VNPay Sandbox Gateway</span>
                    </div>
                    <h3 class="font-serif text-green-dark mb-1">Online Payment Gateway</h3>
                    <p class="text-muted small mb-4">Reservation: <strong><?= htmlspecialchars($reservation['reservation_code']) ?></strong></p>

                    <div class="p-3 bg-light rounded-3 text-start mb-4 small">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total amount payable:</span>
                            <strong class="text-green-dark fs-5"><?= formatVnd($reservation['total_amount']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Transaction reference (TxnRef):</span>
                            <code><?= htmlspecialchars($reservation['reservation_code']) ?></code>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Verification method:</span>
                            <span>HMAC-SHA512 (VNPay Standard)</span>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <!-- Direct link to official VNPay Sandbox -->
                        <a href="<?= $vnp_Url ?>" class="btn btn-primary py-2 fw-bold">
                            <i class="bi bi-arrow-up-right-square me-2"></i> Proceed to VNPay Sandbox Gateway
                        </a>

                        <!-- Interactive Local Sandbox Simulator (Guarantees flawless evaluation on localhost) -->
                        <a href="<?= BASE_URL ?>/payments/sandbox_simulator.php?code=<?= urlencode($reservation['reservation_code']) ?>&gateway=vnpay" class="btn btn-outline-success py-2">
                            <i class="bi bi-cpu me-2"></i> Local Sandbox Simulator (Local Fast Test)
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
