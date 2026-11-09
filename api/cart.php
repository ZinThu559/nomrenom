<?php
/**
 * NomReNom - Shopping Cart API Endpoint
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/cart.php';

$action = $_POST['action'] ?? $_GET['action'] ?? 'get';

switch ($action) {
    case 'add':
        $productId = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['qty'] ?? 1);
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            exit;
        }
        $result = addToCart($productId, $qty);
        echo json_encode($result);
        break;

    case 'update':
        $productId = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['qty'] ?? 1);
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            exit;
        }
        $result = updateCartQty($productId, $qty);
        echo json_encode($result);
        break;

    case 'remove':
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            exit;
        }
        $result = removeFromCart($productId);
        echo json_encode($result);
        break;

    case 'clear':
        clearCart();
        echo json_encode([
            'success' => true,
            'message' => 'Cart cleared.',
            'cart_count' => 0,
            'cart_total' => 0,
            'formatted_total' => '0 ₫'
        ]);
        break;

    case 'get':
    default:
        $cart = getCart();
        echo json_encode([
            'success' => true,
            'cart' => array_values($cart),
            'cart_count' => getCartCount(),
            'cart_total' => getCartTotal(),
            'formatted_total' => formatVnd(getCartTotal())
        ]);
        break;
}
