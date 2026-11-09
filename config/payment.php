<?php
/**
 * NomReNom - Payment Gateways Configuration (Sandbox Mode)
 * Supporting VNPay & MoMo Vietnam
 */

require_once __DIR__ . '/config.php';

// VNPay Sandbox Credentials
define('VNPAY_TMN_CODE', '2QXUI4J4'); // Official VNPay Public Demo Terminal Code
define('VNPAY_HASH_SECRET', 'RAIQUIOWGHGUDGUTRHGUDGUTRHGUDGUT'); // Demo Secret Key
define('VNPAY_API_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
define('VNPAY_RETURN_URL', BASE_URL . '/payments/vnpay_return.php');

// MoMo Sandbox Credentials
define('MOMO_PARTNER_CODE', 'MOMO_DEMO_PARTNER');
define('MOMO_ACCESS_KEY', 'MOMO_DEMO_ACCESS_KEY');
define('MOMO_SECRET_KEY', 'MOMO_DEMO_SECRET_KEY');
define('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create');
define('MOMO_RETURN_URL', BASE_URL . '/payments/momo_return.php');

// In-person / Pay at Pickup
define('PAYMENT_METHOD_PICKUP', 'Pickup');
define('PAYMENT_METHOD_ONLINE', 'Online');
