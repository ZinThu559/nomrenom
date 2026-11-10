<?php
/**
 * NomReNom - MoMo Return Callback Handler
 * Verifies HMAC-SHA256 signature from MoMo gateway and updates records
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/payment.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

$partnerCode = clean($_GET['partnerCode'] ?? '');
$orderId = clean($_GET['orderId'] ?? '');
$requestId = clean($_GET['requestId'] ?? '');
$amount = clean($_GET['amount'] ?? '');
$orderInfo = clean($_GET['orderInfo'] ?? '');
$orderType = clean($_GET['orderType'] ?? '');
$transId = clean($_GET['transId'] ?? ('MOM-' . time()));
$resultCode = clean($_GET['resultCode'] ?? '');
$message = clean($_GET['message'] ?? '');
$responseTime = clean($_GET['responseTime'] ?? '');
$extraData = clean($_GET['extraData'] ?? '');
$signature = clean($_GET['signature'] ?? '');

$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT * FROM reservations WHERE reservation_code = ? LIMIT 1");
$stmt->execute([$orderId]);
$reservation = $stmt->fetch();

if (!$reservation) {
    setFlash('danger', 'No MoMo reservation record found matching this transaction.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Recompute MoMo signature to verify authenticity
$rawHash = "accessKey=" . MOMO_ACCESS_KEY .
           "&amount=" . $amount .
           "&extraData=" . $extraData .
           "&message=" . $message .
           "&orderId=" . $orderId .
           "&orderInfo=" . $orderInfo .
           "&orderType=" . $orderType .
           "&partnerCode=" . $partnerCode .
           "&requestId=" . $requestId .
           "&responseTime=" . $responseTime .
           "&resultCode=" . $resultCode .
           "&transId=" . $transId;

$expectedSignature = hash_hmac("sha256", $rawHash, MOMO_SECRET_KEY);
$isValidSignature = hash_equals($expectedSignature, $signature);

if ($isValidSignature) {
    if ($resultCode == '0') {
        // Successful payment
        $updatePay = $pdo->prepare("UPDATE payments SET 
            payment_status = 'Paid',
            transaction_reference = ?,
            gateway_response = ?,
            payment_date = NOW()
            WHERE reservation_id = ?");
        $updatePay->execute([$transId, json_encode($_GET), $reservation['id']]);

        $updateRes = $pdo->prepare("UPDATE reservations SET 
            payment_status = 'Paid',
            reservation_status = 'Confirmed'
            WHERE id = ?");
        $updateRes->execute([$reservation['id']]);

        setFlash('success', 'MoMo Wallet payment successful! Your food box reservation is confirmed.');
        header('Location: ' . BASE_URL . '/confirmation.php?code=' . urlencode($orderId));
        exit;
    } else {
        $updatePay = $pdo->prepare("UPDATE payments SET 
            payment_status = 'Failed',
            gateway_response = ?
            WHERE reservation_id = ?");
        $updatePay->execute([json_encode($_GET), $reservation['id']]);

        setFlash('warning', 'MoMo payment was cancelled or declined by user (Result Code: ' . htmlspecialchars($resultCode) . ').');
        header('Location: ' . BASE_URL . '/confirmation.php?code=' . urlencode($orderId));
        exit;
    }
} else {
    setFlash('danger', 'Invalid MoMo digital signature (Signature Mismatch). Transaction rejected.');
    header('Location: ' . BASE_URL . '/confirmation.php?code=' . urlencode($orderId));
    exit;
}
