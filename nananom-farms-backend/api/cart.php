<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load environment variables
$env_file = dirname(__DIR__) . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

require_once dirname(__DIR__) . '/includes/auth.php';

// Try to connect to database, but don't fail if it's not available
$db = null;
try {
    require_once dirname(__DIR__) . '/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    error_log("Database connection failed in cart: " . $e->getMessage());
    // Continue without database - will use fallback responses
}

$action = $_GET['action'] ?? '';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGetCart($db);
        break;
    case 'POST':
        switch ($action) {
            case 'add':
                handleAddToCart($db);
                break;
            case 'checkout':
                handleCheckout($db);
                break;
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
        }
        break;
    case 'PUT':
        handleUpdateCartItem($db);
        break;
    case 'DELETE':
        switch ($action) {
            case 'clear':
                handleClearCart($db);
                break;
            default:
                handleRemoveFromCart($db);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function getSessionId() {
    // Generate or retrieve session ID from headers or create new one
    $session_id = $_SERVER['HTTP_X_SESSION_ID'] ?? '';
    if (empty($session_id)) {
        $session_id = 'sess_' . uniqid() . '_' . time();
    }
    return $session_id;
}

function handleGetCart($db) {
    if (!$db) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection not available',
            'cart' => [],
            'session_id' => getSessionId()
        ]);
        return;
    }

    try {
        $session_id = getSessionId();
        $customer_email = $_GET['customer_email'] ?? '';

        $where_conditions = ['session_id = ?'];
        $params = [$session_id];

        if ($customer_email) {
            $where_conditions[] = "customer_email = ?";
            $params[] = $customer_email;
        }

        $where_clause = 'WHERE ' . implode(' OR ', $where_conditions);

        // Get cart items with product/service details
        $query = "SELECT 
                    sc.*,
                    CASE 
                        WHEN sc.item_type = 'product' THEN p.name
                        WHEN sc.item_type = 'service' THEN s.name
                    END as item_name,
                    CASE 
                        WHEN sc.item_type = 'product' THEN p.price
                        WHEN sc.item_type = 'service' THEN s.price
                    END as item_price,
                    CASE 
                        WHEN sc.item_type = 'product' THEN p.description
                        WHEN sc.item_type = 'service' THEN s.description
                    END as item_description,
                    CASE 
                        WHEN sc.item_type = 'product' THEN p.image_url
                        WHEN sc.item_type = 'service' THEN s.image_url
                    END as item_image,
                    CASE 
                        WHEN sc.item_type = 'product' THEN p.stock_quantity
                        ELSE NULL
                    END as stock_quantity,
                    CASE 
                        WHEN sc.item_type = 'product' THEN p.is_digital
                        ELSE 0
                    END as is_digital
                 FROM shopping_cart sc
                 LEFT JOIN products p ON sc.item_type = 'product' AND sc.item_id = p.id
                 LEFT JOIN services s ON sc.item_type = 'service' AND sc.item_id = s.id
                 $where_clause
                 ORDER BY sc.created_at ASC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate totals
        $subtotal = 0;
        $total_items = 0;
        $requires_booking = false;
        $has_physical_items = false;

        foreach ($cart_items as &$item) {
            $item_total = $item['item_price'] * $item['quantity'];
            $item['total_price'] = $item_total;
            $subtotal += $item_total;
            $total_items += $item['quantity'];
            
            if ($item['item_type'] === 'service') {
                $requires_booking = true;
            }
            if ($item['item_type'] === 'product' && !$item['is_digital']) {
                $has_physical_items = true;
            }
        }

        $tax_amount = $subtotal * 0.075; // 7.5% VAT
        $shipping_amount = $has_physical_items ? calculateCartShipping($cart_items) : 0;
        $total_amount = $subtotal + $tax_amount + $shipping_amount;

        echo json_encode([
            'success' => true,
            'session_id' => $session_id,
            'cart_items' => $cart_items,
            'summary' => [
                'total_items' => $total_items,
                'subtotal' => $subtotal,
                'tax_amount' => $tax_amount,
                'shipping_amount' => $shipping_amount,
                'total_amount' => $total_amount,
                'requires_booking' => $requires_booking,
                'has_physical_items' => $has_physical_items
            ]
        ]);

    } catch (Exception $e) {
        error_log("Get cart error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleAddToCart($db) {
    if (!$db) {
        http_response_code(503);
        echo json_encode(['error' => 'Database connection not available']);
        return;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $session_id = $data['session_id'] ?? getSessionId();
        $customer_email = $data['customer_email'] ?? '';
        $item_type = $data['item_type'] ?? '';
        $item_id = intval($data['item_id'] ?? 0);
        $quantity = max(1, intval($data['quantity'] ?? 1));
        $booking_date = $data['booking_date'] ?? null;
        $booking_time = $data['booking_time'] ?? null;

        if (empty($item_type) || empty($item_id) || !in_array($item_type, ['product', 'service'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid item type and item ID are required']);
            return;
        }

        // Validate item exists and is active
        $table = $item_type === 'product' ? 'products' : 'services';
        $item_query = "SELECT * FROM $table WHERE id = ? AND is_active = 1";
        $item_stmt = $db->prepare($item_query);
        $item_stmt->execute([$item_id]);
        $item = $item_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            http_response_code(404);
            echo json_encode(['error' => ucfirst($item_type) . ' not found']);
            return;
        }

        // Check stock for products
        if ($item_type === 'product' && !$item['is_digital'] && $item['stock_quantity'] < $quantity) {
            http_response_code(409);
            echo json_encode([
                'error' => 'Insufficient stock',
                'available_quantity' => $item['stock_quantity']
            ]);
            return;
        }

        // Check if item already exists in cart
        $existing_query = "SELECT * FROM shopping_cart 
                          WHERE session_id = ? AND item_type = ? AND item_id = ?";
        $existing_params = [$session_id, $item_type, $item_id];

        if ($booking_date && $booking_time) {
            $existing_query .= " AND booking_date = ? AND booking_time = ?";
            $existing_params[] = $booking_date;
            $existing_params[] = $booking_time;
        }

        $existing_stmt = $db->prepare($existing_query);
        $existing_stmt->execute($existing_params);
        $existing_item = $existing_stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_item) {
            // Update quantity
            $new_quantity = $existing_item['quantity'] + $quantity;
            
            // Check stock again for the new total quantity
            if ($item_type === 'product' && !$item['is_digital'] && $item['stock_quantity'] < $new_quantity) {
                http_response_code(409);
                echo json_encode([
                    'error' => 'Total quantity would exceed available stock',
                    'available_quantity' => $item['stock_quantity'],
                    'current_in_cart' => $existing_item['quantity']
                ]);
                return;
            }

            $update_query = "UPDATE shopping_cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$new_quantity, $existing_item['id']]);
            
            $message = 'Item quantity updated in cart';
        } else {
            // Add new item
            $insert_query = "INSERT INTO shopping_cart (session_id, customer_email, item_type, item_id, quantity, booking_date, booking_time) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->execute([$session_id, $customer_email, $item_type, $item_id, $quantity, $booking_date, $booking_time]);
            
            $message = 'Item added to cart';
        }

        echo json_encode([
            'success' => true,
            'message' => $message,
            'session_id' => $session_id,
            'item' => $item
        ]);

    } catch (Exception $e) {
        error_log("Add to cart error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleUpdateCartItem($db) {
    if (!$db) {
        http_response_code(503);
        echo json_encode(['error' => 'Database connection not available']);
        return;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $cart_item_id = intval($data['cart_item_id'] ?? 0);
        $quantity = max(1, intval($data['quantity'] ?? 1));
        $booking_date = $data['booking_date'] ?? null;
        $booking_time = $data['booking_time'] ?? null;

        if (empty($cart_item_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Cart item ID is required']);
            return;
        }

        // Get cart item details
        $cart_query = "SELECT sc.*, 
                             CASE 
                                WHEN sc.item_type = 'product' THEN p.stock_quantity
                                ELSE NULL
                             END as stock_quantity,
                             CASE 
                                WHEN sc.item_type = 'product' THEN p.is_digital
                                ELSE 0
                             END as is_digital
                      FROM shopping_cart sc
                      LEFT JOIN products p ON sc.item_type = 'product' AND sc.item_id = p.id
                      WHERE sc.id = ?";
        
        $cart_stmt = $db->prepare($cart_query);
        $cart_stmt->execute([$cart_item_id]);
        $cart_item = $cart_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cart_item) {
            http_response_code(404);
            echo json_encode(['error' => 'Cart item not found']);
            return;
        }

        // Check stock for products
        if ($cart_item['item_type'] === 'product' && !$cart_item['is_digital'] && $cart_item['stock_quantity'] < $quantity) {
            http_response_code(409);
            echo json_encode([
                'error' => 'Insufficient stock',
                'available_quantity' => $cart_item['stock_quantity']
            ]);
            return;
        }

        // Update cart item
        $update_fields = ['quantity = ?', 'updated_at = CURRENT_TIMESTAMP'];
        $update_params = [$quantity];

        if ($booking_date !== null) {
            $update_fields[] = 'booking_date = ?';
            $update_params[] = $booking_date;
        }

        if ($booking_time !== null) {
            $update_fields[] = 'booking_time = ?';
            $update_params[] = $booking_time;
        }

        $update_params[] = $cart_item_id;
        $update_query = "UPDATE shopping_cart SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        
        if ($update_stmt->execute($update_params)) {
            echo json_encode(['success' => true, 'message' => 'Cart item updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update cart item']);
        }

    } catch (Exception $e) {
        error_log("Update cart item error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleRemoveFromCart($db) {
    if (!$db) {
        http_response_code(503);
        echo json_encode(['error' => 'Database connection not available']);
        return;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $cart_item_id = intval($data['cart_item_id'] ?? 0);

        if (empty($cart_item_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Cart item ID is required']);
            return;
        }

        $delete_query = "DELETE FROM shopping_cart WHERE id = ?";
        $delete_stmt = $db->prepare($delete_query);
        
        if ($delete_stmt->execute([$cart_item_id])) {
            if ($delete_stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Cart item not found']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to remove item from cart']);
        }

    } catch (Exception $e) {
        error_log("Remove from cart error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleClearCart($db) {
    if (!$db) {
        http_response_code(503);
        echo json_encode(['error' => 'Database connection not available']);
        return;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $session_id = $data['session_id'] ?? getSessionId();
        $customer_email = $data['customer_email'] ?? '';

        $where_conditions = ['session_id = ?'];
        $params = [$session_id];

        if ($customer_email) {
            $where_conditions[] = "customer_email = ?";
            $params[] = $customer_email;
        }

        $where_clause = 'WHERE ' . implode(' OR ', $where_conditions);
        $delete_query = "DELETE FROM shopping_cart $where_clause";
        $delete_stmt = $db->prepare($delete_query);
        
        if ($delete_stmt->execute($params)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Cart cleared successfully',
                'items_removed' => $delete_stmt->rowCount()
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to clear cart']);
        }

    } catch (Exception $e) {
        error_log("Clear cart error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleCheckout($db) {
    if (!$db) {
        http_response_code(503);
        echo json_encode(['error' => 'Database connection not available']);
        return;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $session_id = $data['session_id'] ?? getSessionId();
        $customer_email = $data['customer_email'] ?? '';
        $customer_name = $data['customer_name'] ?? '';
        $customer_phone = $data['customer_phone'] ?? '';
        $shipping_address = $data['shipping_address'] ?? '';
        $payment_method = $data['payment_method'] ?? 'bank_transfer';
        $notes = $data['notes'] ?? '';

        if (empty($customer_email) || empty($customer_name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Customer email and name are required']);
            return;
        }

        // Validate email
        if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        // Get cart items
        $cart_query = "SELECT 
                         sc.*,
                         CASE 
                            WHEN sc.item_type = 'product' THEN p.name
                            WHEN sc.item_type = 'service' THEN s.name
                         END as item_name,
                         CASE 
                            WHEN sc.item_type = 'product' THEN p.price
                            WHEN sc.item_type = 'service' THEN s.price
                         END as item_price,
                         CASE 
                            WHEN sc.item_type = 'product' THEN p.stock_quantity
                            ELSE NULL
                         END as stock_quantity,
                         CASE 
                            WHEN sc.item_type = 'product' THEN p.is_digital
                            ELSE 0
                         END as is_digital
                      FROM shopping_cart sc
                      LEFT JOIN products p ON sc.item_type = 'product' AND sc.item_id = p.id
                      LEFT JOIN services s ON sc.item_type = 'service' AND sc.item_id = s.id
                      WHERE sc.session_id = ? OR sc.customer_email = ?
                      ORDER BY sc.created_at ASC";

        $cart_stmt = $db->prepare($cart_query);
        $cart_stmt->execute([$session_id, $customer_email]);
        $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cart_items)) {
            http_response_code(400);
            echo json_encode(['error' => 'Cart is empty']);
            return;
        }

        // Start transaction
        $db->beginTransaction();

        // Validate stock and calculate totals
        $subtotal = 0;
        $has_physical_items = false;
        $order_type = 'product';
        $mixed_items = false;

        foreach ($cart_items as $item) {
            // Check stock for products
            if ($item['item_type'] === 'product' && !$item['is_digital'] && $item['stock_quantity'] < $item['quantity']) {
                $db->rollback();
                http_response_code(409);
                echo json_encode([
                    'error' => 'Insufficient stock for ' . $item['item_name'],
                    'available_quantity' => $item['stock_quantity'],
                    'requested_quantity' => $item['quantity']
                ]);
                return;
            }

            $subtotal += $item['item_price'] * $item['quantity'];
            
            if ($item['item_type'] === 'product' && !$item['is_digital']) {
                $has_physical_items = true;
            }
            
            if (!$mixed_items) {
                if ($order_type === 'product' && $item['item_type'] === 'service') {
                    $order_type = 'mixed';
                    $mixed_items = true;
                } elseif ($order_type === 'product' && $item['item_type'] === 'product') {
                    // Keep as product
                } else {
                    $order_type = $item['item_type'];
                }
            }
        }

        // Create or get customer
        $customer_query = "SELECT id FROM customers WHERE email = ?";
        $customer_stmt = $db->prepare($customer_query);
        $customer_stmt->execute([$customer_email]);
        $customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            $create_customer_query = "INSERT INTO customers (full_name, email, phone, address) VALUES (?, ?, ?, ?)";
            $create_customer_stmt = $db->prepare($create_customer_query);
            $create_customer_stmt->execute([$customer_name, $customer_email, $customer_phone, $shipping_address]);
            $customer_id = $db->lastInsertId();
        } else {
            $customer_id = $customer['id'];
        }

        // Generate order number
        $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Calculate totals
        $tax_amount = $subtotal * 0.075; // 7.5% VAT
        $shipping_amount = $has_physical_items ? calculateCartShipping($cart_items) : 0;
        $total_amount = $subtotal + $tax_amount + $shipping_amount;

        // Create order
        $create_order_query = "INSERT INTO orders (customer_id, order_number, order_type, subtotal, tax_amount, shipping_amount, total_amount, payment_method, shipping_address, notes) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $create_order_stmt = $db->prepare($create_order_query);
        $create_order_stmt->execute([$customer_id, $order_number, $order_type, $subtotal, $tax_amount, $shipping_amount, $total_amount, $payment_method, $shipping_address, $notes]);
        $order_id = $db->lastInsertId();

        // Add order items and update stock
        foreach ($cart_items as $item) {
            $create_item_query = "INSERT INTO order_items (order_id, item_type, item_id, item_name, quantity, unit_price, total_price, booking_date, booking_time) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $create_item_stmt = $db->prepare($create_item_query);
            $create_item_stmt->execute([
                $order_id, $item['item_type'], $item['item_id'], $item['item_name'], 
                $item['quantity'], $item['item_price'], $item['item_price'] * $item['quantity'],
                $item['booking_date'], $item['booking_time']
            ]);

            // Update stock for physical products
            if ($item['item_type'] === 'product' && !$item['is_digital']) {
                $update_stock_query = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                $update_stock_stmt = $db->prepare($update_stock_query);
                $update_stock_stmt->execute([$item['quantity'], $item['item_id']]);
            }
        }

        // Clear cart
        $clear_cart_query = "DELETE FROM shopping_cart WHERE session_id = ? OR customer_email = ?";
        $clear_cart_stmt = $db->prepare($clear_cart_query);
        $clear_cart_stmt->execute([$session_id, $customer_email]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully',
            'order_number' => $order_number,
            'order_id' => $order_id,
            'order_details' => [
                'subtotal' => $subtotal,
                'tax_amount' => $tax_amount,
                'shipping_amount' => $shipping_amount,
                'total_amount' => $total_amount,
                'payment_method' => $payment_method
            ],
            'estimated_delivery' => $has_physical_items ? calculateDeliveryDate() : 'N/A'
        ]);

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollback();
        }
        error_log("Checkout error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred during checkout']);
    }
}

// Helper functions
function calculateCartShipping($cart_items) {
    $total_weight = 0;
    $base_shipping = 15.00; // Base shipping for cart
    
    foreach ($cart_items as $item) {
        if ($item['item_type'] === 'product' && !$item['is_digital']) {
            // Assume default weight if not available
            $weight = 1.0; // Default 1kg per product
            $total_weight += $weight * $item['quantity'];
        }
    }
    
    return $base_shipping + ($total_weight * 2.00);
}

function calculateDeliveryDate() {
    $delivery_days = rand(3, 7);
    return date('Y-m-d', strtotime("+$delivery_days days"));
}
?>