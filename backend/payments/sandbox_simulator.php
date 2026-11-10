<?php
/**
 * NomReNom - Interactive Local Payment Sandbox Simulator
 * Designed for University Demonstrations and Automated Testing
 * Generates genuine HMAC signatures for local testing of success/failure/tampering
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/payment.php';
require_once __DIR__ . '/../includes/functions.php';

$code = clean($_GET['code'] ?? '');
$gateway = clean($_GET['gateway'] ?? 'vnpay');

$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT * FROM reservations WHERE reservation_code = ? LIMIT 1");
$stmt->execute([$code]);
$reservation = $stmt->fetch();

if (!$reservation) {
    die("Reservation not found for simulation.");
}

// Action Trigger
$action = clean($_GET['action'] ?? '');

if ($action === 'success') {
    if ($gateway === 'vnpay') {
        $amount = (int)$reservation['total_amount'] * 100;
        $params = [
            'vnp_Amount' => $amount,
            'vnp_BankCode' => 'NCB',
            'vnp_BankTranNo' => 'VNP' . date('YmdHis'),
            'vnp_CardType' => 'ATM',
            'vnp_OrderInfo' => "Payment for NomReNom reservation " . $code,
            'vnp_PayDate' => date('YmdHis'),
            'vnp_ResponseCode' => '00',
            'vnp_TmnCode' => VNPAY_TMN_CODE,
            'vnp_TransactionNo' => '14' . date('His'),
            'vnp_TransactionStatus' => '00',
            'vnp_TxnRef' => $code
        ];
        ksort($params);
        $hashData = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($k) . "=" . urlencode($v);
            } else {
                $hashData .= urlencode($k) . "=" . urlencode($v);
                $i = 1;
            }
        }
        $secureHash = hash_hmac('sha512', $hashData, VNPAY_HASH_SECRET);
        $redirectUrl = BASE_URL . '/payments/vnpay_return.php?' . http_build_query($params) . '&vnp_SecureHash=' . $secureHash;
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        // MoMo Success
        $transId = "MOM" . time();
        $params = [
            'partnerCode' => MOMO_PARTNER_CODE,
            'orderId' => $code,
            'requestId' => time() . "",
            'amount' => (string)(int)$reservation['total_amount'],
            'orderInfo' => "Payment for NomReNom reservation " . $code,
            'orderType' => "momo_wallet",
            'transId' => $transId,
            'resultCode' => '0',
            'message' => 'Successful.',
            'responseTime' => date('Y-m-d H:i:s'),
            'extraData' => ''
        ];
        $rawHash = "accessKey=" . MOMO_ACCESS_KEY .
                   "&amount=" . $params['amount'] .
                   "&extraData=" . $params['extraData'] .
                   "&message=" . $params['message'] .
                   "&orderId=" . $params['orderId'] .
                   "&orderInfo=" . $params['orderInfo'] .
                   "&orderType=" . $params['orderType'] .
                   "&partnerCode=" . $params['partnerCode'] .
                   "&requestId=" . $params['requestId'] .
                   "&responseTime=" . $params['responseTime'] .
                   "&resultCode=" . $params['resultCode'] .
                   "&transId=" . $params['transId'];
        $signature = hash_hmac("sha256", $rawHash, MOMO_SECRET_KEY);
        $redirectUrl = BASE_URL . '/payments/momo_return.php?' . http_build_query($params) . '&signature=' . $signature;
        header('Location: ' . $redirectUrl);
        exit;
    }
} elseif ($action === 'fail') {
    if ($gateway === 'vnpay') {
        $params = [
            'vnp_Amount' => (int)$reservation['total_amount'] * 100,
            'vnp_ResponseCode' => '24', // User cancelled transaction code
            'vnp_TmnCode' => VNPAY_TMN_CODE,
            'vnp_TxnRef' => $code
        ];
        ksort($params);
        $hashData = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($k) . "=" . urlencode($v);
            } else {
                $hashData .= urlencode($k) . "=" . urlencode($v);
                $i = 1;
            }
        }
        $secureHash = hash_hmac('sha512', $hashData, VNPAY_HASH_SECRET);
        header('Location: ' . BASE_URL . '/payments/vnpay_return.php?' . http_build_query($params) . '&vnp_SecureHash=' . $secureHash);
        exit;
    } else {
        $params = [
            'partnerCode' => MOMO_PARTNER_CODE,
            'orderId' => $code,
            'requestId' => time() . "",
            'amount' => (string)(int)$reservation['total_amount'],
            'orderInfo' => "Payment for NomReNom reservation " . $code,
            'orderType' => "momo_wallet",
            'transId' => "MOM_FAIL_" . time(),
            'resultCode' => '1006', // User cancelled
            'message' => 'User cancelled payment.',
            'responseTime' => date('Y-m-d H:i:s'),
            'extraData' => ''
        ];
        $rawHash = "accessKey=" . MOMO_ACCESS_KEY .
                   "&amount=" . $params['amount'] .
                   "&extraData=" . $params['extraData'] .
                   "&message=" . $params['message'] .
                   "&orderId=" . $params['orderId'] .
                   "&orderInfo=" . $params['orderInfo'] .
                   "&orderType=" . $params['orderType'] .
                   "&partnerCode=" . $params['partnerCode'] .
                   "&requestId=" . $params['requestId'] .
                   "&responseTime=" . $params['responseTime'] .
                   "&resultCode=" . $params['resultCode'] .
                   "&transId=" . $params['transId'];
        $signature = hash_hmac("sha256", $rawHash, MOMO_SECRET_KEY);
        header('Location: ' . BASE_URL . '/payments/momo_return.php?' . http_build_query($params) . '&signature=' . $signature);
        exit;
    }
} elseif ($action === 'tamper') {
    // Purposefully corrupt signature to test security defenses
    header('Location: ' . BASE_URL . '/payments/vnpay_return.php?vnp_TxnRef=' . urlencode($code) . '&vnp_ResponseCode=00&vnp_SecureHash=INVALID_CORRUPTED_HASH_ABC123');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Gateway Sandbox Simulator – NomReNom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="bg-cream py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5 bg-white">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-warning text-dark px-3 py-2"><i class="bi bi-tools me-1"></i> Developer Test Utility</span>
                        <span class="badge bg-green-light text-green-dark px-3 py-2">University Evaluation Mode</span>
                    </div>

                    <h2 class="font-serif text-green-dark mb-2">Local Gateway Simulator & Webhook Tester</h2>
                    <p class="text-muted small mb-4">
                        Because local development environments (localhost XAMPP) do not have a public IP address to receive real gateway webhooks, this sandbox computes real cryptographic HMAC signatures (HMAC-SHA512 for VNPay / HMAC-SHA256 for MoMo) to test complete end-to-end payment workflows.
                    </p>

                    <div class="p-3 bg-light rounded-3 mb-4 small">
                        <div class="row g-2">
                            <div class="col-sm-6"><strong>Reservation Code:</strong> <?= htmlspecialchars($reservation['reservation_code']) ?></div>
                            <div class="col-sm-6"><strong>Target Gateway:</strong> <?= strtoupper(htmlspecialchars($gateway)) ?> Sandbox</div>
                            <div class="col-sm-6"><strong>Amount Due:</strong> <?= formatVnd($reservation['total_amount']) ?></div>
                            <div class="col-sm-6"><strong>Current Status:</strong> <?= htmlspecialchars($reservation['payment_status']) ?></div>
                        </div>
                    </div>

                    <h5 class="text-green-dark mb-3">Select Test Scenario:</h5>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card h-100 p-3 border-success border-2 bg-light text-center">
                                <i class="bi bi-check-circle-fill fs-1 text-success mb-2"></i>
                                <h6 class="fw-bold">Payment Success</h6>
                                <p class="small text-muted mb-3">Generates valid signature, sets status to 'Paid' & 'Confirmed', and sends confirmation email.</p>
                                <a href="?code=<?= urlencode($code) ?>&gateway=<?= urlencode($gateway) ?>&action=success" class="btn btn-success btn-sm w-100 mt-auto">
                                    Simulate Success
                                </a>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card h-100 p-3 border-warning border-2 bg-light text-center">
                                <i class="bi bi-x-circle-fill fs-1 text-warning mb-2"></i>
                                <h6 class="fw-bold">Customer Cancelled</h6>
                                <p class="small text-muted mb-3">Dispatches cancellation code '24', sets status to 'Failed', and preserves stock inventory.</p>
                                <a href="?code=<?= urlencode($code) ?>&gateway=<?= urlencode($gateway) ?>&action=fail" class="btn btn-warning btn-sm w-100 mt-auto">
                                    Simulate Cancellation
                                </a>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card h-100 p-3 border-danger border-2 bg-light text-center">
                                <i class="bi bi-shield-slash-fill fs-1 text-danger mb-2"></i>
                                <h6 class="fw-bold">Tampered Signature</h6>
                                <p class="small text-muted mb-3">Tests cryptographic detection of tampered or altered callback payloads (Signature Mismatch).</p>
                                <a href="?code=<?= urlencode($code) ?>&gateway=<?= urlencode($gateway) ?>&action=tamper" class="btn btn-danger btn-sm w-100 mt-auto">
                                    Simulate Tampering
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4 pt-3 border-top">
                        <a href="<?= BASE_URL ?>/confirmation.php?code=<?= urlencode($code) ?>" class="text-muted small text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i> Return to reservation details
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
