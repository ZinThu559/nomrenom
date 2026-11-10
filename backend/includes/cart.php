<?php
/**
 * NomReNom - Commercial Shopping Cart Engine
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Get current shopping cart items array
 * @return array
 */
function getCart(): array {
    return $_SESSION['cart'] ?? [];
}

/**
 * Get total number of food items in cart
 * @return int
 */
function getCartCount(): int {
    $count = 0;
    foreach (getCart() as $item) {
        $count += (int)($item['qty'] ?? 1);
    }
    return $count;
}

/**
 * Get total monetary sum of cart items in VND
 * @return float
 */
function getCartTotal(): float {
    $total = 0.0;
    foreach (getCart() as $item) {
        $total += ((float)$item['price'] * (int)$item['qty']);
    }
    return $total;
}

/**
 * Add a food product to the shopping cart
 * @param int $productId
 * @param int $qty
 * @return array ['success' => bool, 'message' => string, 'cart_count' => int, 'cart_total' => float]
 */
function addToCart(int $productId, int $qty = 1): array {
    if ($qty <= 0) $qty = 1;
    
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT f.*, b.name as bakery_name, b.district 
                          FROM food_boxes f 
                          JOIN bakeries b ON f.bakery_id = b.id 
                          WHERE f.id = ? AND f.is_active = 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        return ['success' => false, 'message' => 'Product not found or unavailable.'];
    }
    
    if ($product['available_qty'] < $qty) {
        return ['success' => false, 'message' => "Only {$product['available_qty']} items available today."];
    }
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$productId])) {
        $newQty = $_SESSION['cart'][$productId]['qty'] + $qty;
        if ($newQty > $product['available_qty']) {
            $newQty = (int)$product['available_qty'];
        }
        $_SESSION['cart'][$productId]['qty'] = $newQty;
    } else {
        $_SESSION['cart'][$productId] = [
            'id' => (int)$product['id'],
            'bakery_id' => (int)$product['bakery_id'],
            'bakery_name' => $product['bakery_name'],
            'district' => $product['district'],
            'name' => $product['name'],
            'price' => (float)$product['price'],
            'original_value' => (float)$product['original_value'],
            'image_url' => $product['image_url'],
            'box_type' => $product['box_type'],
            'max_qty' => (int)$product['available_qty'],
            'qty' => $qty
        ];
    }
    
    $formattedTotal = function_exists('formatVnd') ? formatVnd(getCartTotal()) : number_format(getCartTotal(), 0, ',', '.') . ' ₫';
    
    return [
        'success' => true,
        'message' => "Added {$product['name']} to your cart!",
        'cart_count' => getCartCount(),
        'cart_total' => getCartTotal(),
        'formatted_total' => $formattedTotal
    ];
}

/**
 * Update quantity of a food product in cart
 */
function updateCartQty(int $productId, int $qty): array {
    if (!isset($_SESSION['cart'][$productId])) {
        return ['success' => false, 'message' => 'Item not in cart.'];
    }
    
    if ($qty <= 0) {
        return removeFromCart($productId);
    }
    
    $maxQty = $_SESSION['cart'][$productId]['max_qty'] ?? 99;
    if ($qty > $maxQty) {
        $qty = $maxQty;
    }
    
    $_SESSION['cart'][$productId]['qty'] = $qty;
    $itemSubtotal = $_SESSION['cart'][$productId]['price'] * $qty;
    $formattedSubtotal = function_exists('formatVnd') ? formatVnd($itemSubtotal) : number_format($itemSubtotal, 0, ',', '.') . ' ₫';
    $formattedTotal = function_exists('formatVnd') ? formatVnd(getCartTotal()) : number_format(getCartTotal(), 0, ',', '.') . ' ₫';
    
    return [
        'success' => true,
        'message' => 'Cart updated.',
        'item_qty' => $qty,
        'item_subtotal' => $formattedSubtotal,
        'cart_count' => getCartCount(),
        'cart_total' => getCartTotal(),
        'formatted_total' => $formattedTotal
    ];
}

/**
 * Remove an item from the cart
 */
function removeFromCart(int $productId): array {
    if (isset($_SESSION['cart'][$productId])) {
        $name = $_SESSION['cart'][$productId]['name'];
        unset($_SESSION['cart'][$productId]);
        $formattedTotal = function_exists('formatVnd') ? formatVnd(getCartTotal()) : number_format(getCartTotal(), 0, ',', '.') . ' ₫';
        return [
            'success' => true,
            'message' => "Removed {$name} from cart.",
            'cart_count' => getCartCount(),
            'cart_total' => getCartTotal(),
            'formatted_total' => $formattedTotal
        ];
    }
    return ['success' => false, 'message' => 'Item was not in cart.'];
}

/**
 * Clear all cart items
 */
function clearCart(): void {
    $_SESSION['cart'] = [];
}
