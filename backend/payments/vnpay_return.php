<?php
/**
 * NomReNom - VNPay Return Callback Handler
 * Verifies HMAC-SHA512 signature from gateway and atomically updates database records
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/payment.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
$inputData = [];

foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

unset($inputData['vnp_SecureHash']);
ksort($inputData);
$i = 0;
$hashData = "";

foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$secureHash = hash_hmac('sha512', $hashData, VNPAY_HASH_SECRET);
$vnp_TxnRef = clean($_GET['vnp_TxnRef'] ?? '');
$vnp_ResponseCode = clean($_GET['vnp_ResponseCode'] ?? '');
$vnp_TransactionNo = clean($_GET['vnp_TransactionNo'] ?? ('VNP-' . time()));

$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT * FROM reservations WHERE reservation_code = ? LIMIT 1");
$stmt->execute([$vnp_TxnRef]);
$reservation = $stmt->fetch();

if (!$reservation) {
    setFlash('danger', 'No reservation found matching this transaction.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Cryptographic Signature Verification
$isValidSignature = hash_equals($secureHash, $vnp_SecureHash);

if ($isValidSignature) {
    if ($vnp_ResponseCode == '00') {
        // Payment Succeeded!
        $updatePay = $pdo->prepare("UPDATE payments SET 
            payment_status = 'Paid',
            transaction_reference = ?,
            gateway_response = ?,
            payment_date = NOW()
            WHERE reservation_id = ?");
        $updatePay->execute([$vnp_TransactionNo, json_encode($_GET), $reservation['id']]);

        $updateRes = $pdo->prepare("UPDATE reservations SET 
            payment_status = 'Paid',
            reservation_status = 'Confirmed'
            WHERE id = ?");
        $updateRes->execute([$reservation['id']]);

        // Send confirmation email
        $itemsStmt = $pdo->prepare("SELECT * FROM reservation_items WHERE reservation_id = ?");
        $itemsStmt->execute([$reservation['id']]);
        $items = $itemsStmt->fetchAll();

        $bakeryStmt = $pdo->prepare("SELECT * FROM bakeries WHERE id = ?");
        $bakeryStmt->execute([$reservation['bakery_id']]);
        $bakery = $bakeryStmt->fetch();

        $slotStmt = $pdo->prepare("SELECT * FROM pickup_slots WHERE id = ?");
        $slotStmt->execute([$reservation['pickup_slot_id']]);
        $slot = $slotStmt->fetch();

        $recipientEmail = 'customer@nomrenom.vn';
        $recipientName = 'NomReNom Customer';
        if (!empty($reservation['user_id'])) {
            $uStmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
            $uStmt->execute([$reservation['user_id']]);
            $u = $uStmt->fetch();
            if ($u) {
                $recipientEmail = $u['email'];
                $recipientName = $u['full_name'];
            }
        } elseif (!empty($reservation['guest_id'])) {
            $gStmt = $pdo->prepare("SELECT email, full_name FROM guest_customers WHERE id = ?");
            $gStmt->execute([$reservation['guest_id']]);
            $g = $gStmt->fetch();
            if ($g) {
                $recipientEmail = $g['email'];
                $recipientName = $g['full_name'];
            }
        }

        sendReservationConfirmationEmail($reservation, $items, $bakery, $slot, $recipientEmail, $recipientName);

        setFlash('success', 'Online VNPay payment confirmed! Your food box reservation is now secured.');
        header('Location: ' . BASE_URL . '/confirmation.php?code=' . urlencode($vnp_TxnRef));
        exit;
    } else {
        // Payment was cancelled or failed by user at gateway
        $updatePay = $pdo->prepare("UPDATE payments SET 
            payment_status = 'Failed',
            gateway_response = ?
            WHERE reservation_id = ?");
        $updatePay->execute([json_encode($_GET), $reservation['id']]);

        setFlash('warning', 'The online payment was cancelled or declined by the gateway (Code: ' . htmlspecialchars($vnp_ResponseCode) . '). You can retry or choose Pay at Pickup.');
        header('Location: ' . BASE_URL . '/confirmation.php?code=' . urlencode($vnp_TxnRef));
        exit;
    }
} else {
    // Tampered or invalid cryptographic signature
    setFlash('danger', 'Invalid gateway digital signature (Signature Mismatch). Transaction rejected for security reasons.');
    header('Location: ' . BASE_URL . '/confirmation.php?code=' . urlencode($vnp_TxnRef));
    exit;
}
