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
    error_log("Database connection failed in products: " . $e->getMessage());
    // Continue without database - will use fallback responses
}

$action = $_GET['action'] ?? '';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        switch ($action) {
            case 'categories':
                handleGetProductCategories($db);
                break;
            case 'search':
                handleSearchProducts($db);
                break;
            case 'featured':
                handleGetFeaturedProducts($db);
                break;
            default:
                handleGetProducts($db);
        }
        break;
    case 'POST':
        switch ($action) {
            case 'purchase':
                handlePurchaseProduct($db);
                break;
            default:
                if (Auth::getCurrentUser() && Auth::getCurrentUser()->user_type === 'admin') {
                    handleCreateProduct($db);
                } else {
                    http_response_code(401);
                    echo json_encode(['error' => 'Unauthorized']);
                }
        }
        break;
    case 'PUT':
        if (Auth::getCurrentUser() && Auth::getCurrentUser()->user_type === 'admin') {
            handleUpdateProduct($db);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
        }
        break;
    case 'DELETE':
        if (Auth::getCurrentUser() && Auth::getCurrentUser()->user_type === 'admin') {
            handleDeleteProduct($db);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleGetProducts($db) {
    if (!$db) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection not available',
            'products' => []
        ]);
        return;
    }

    try {
        $category = $_GET['category'] ?? '';
        $is_active = $_GET['is_active'] ?? 1;
        $in_stock = $_GET['in_stock'] ?? '';
        $price_min = floatval($_GET['price_min'] ?? 0);
        $price_max = floatval($_GET['price_max'] ?? 0);
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(50, max(1, intval($_GET['limit'] ?? 12)));
        $offset = ($page - 1) * $limit;
        $sort_by = $_GET['sort_by'] ?? 'name';
        $sort_order = strtoupper($_GET['sort_order'] ?? 'ASC');

        // Validate sort parameters
        $allowed_sort_fields = ['name', 'price', 'created_at', 'stock_quantity'];
        if (!in_array($sort_by, $allowed_sort_fields)) {
            $sort_by = 'name';
        }
        if (!in_array($sort_order, ['ASC', 'DESC'])) {
            $sort_order = 'ASC';
        }

        $where_conditions = ['is_active = ?'];
        $params = [$is_active];

        if ($category) {
            $where_conditions[] = "category = ?";
            $params[] = $category;
        }

        if ($in_stock === '1') {
            $where_conditions[] = "stock_quantity > 0";
        }

        if ($price_min > 0) {
            $where_conditions[] = "price >= ?";
            $params[] = $price_min;
        }

        if ($price_max > 0) {
            $where_conditions[] = "price <= ?";
            $params[] = $price_max;
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

        // Get total count
        $count_query = "SELECT COUNT(*) as total FROM products $where_clause";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get products
        $query = "SELECT * FROM products $where_clause ORDER BY $sort_by $sort_order LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'products' => $products,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ],
            'filters' => [
                'category' => $category,
                'in_stock' => $in_stock,
                'price_range' => [$price_min, $price_max],
                'sort_by' => $sort_by,
                'sort_order' => $sort_order
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get products error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleGetProductCategories($db) {
    if (!$db) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection not available',
            'categories' => []
        ]);
        return;
    }

    try {
        $query = "SELECT category, 
                         COUNT(*) as product_count, 
                         AVG(price) as avg_price,
                         MIN(price) as min_price,
                         MAX(price) as max_price,
                         SUM(stock_quantity) as total_stock
                 FROM products 
                 WHERE is_active = 1 
                 GROUP BY category 
                 ORDER BY category";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
    } catch (Exception $e) {
        error_log("Get product categories error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleSearchProducts($db) {
    if (!$db) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection not available',
            'products' => []
        ]);
        return;
    }

    try {
        $search_term = $_GET['q'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(50, max(1, intval($_GET['limit'] ?? 12)));
        $offset = ($page - 1) * $limit;

        if (empty($search_term)) {
            http_response_code(400);
            echo json_encode(['error' => 'Search term is required']);
            return;
        }

        $where_clause = "WHERE is_active = 1 AND (name LIKE ? OR description LIKE ? OR category LIKE ? OR sku LIKE ?)";
        $search_pattern = "%$search_term%";
        $params = [$search_pattern, $search_pattern, $search_pattern, $search_pattern];

        // Get total count
        $count_query = "SELECT COUNT(*) as total FROM products $where_clause";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get products with relevance scoring
        $query = "SELECT *, 
                  (CASE 
                    WHEN name LIKE ? THEN 3
                    WHEN description LIKE ? THEN 2
                    WHEN category LIKE ? THEN 1
                    ELSE 0
                  END) as relevance_score
                 FROM products $where_clause 
                 ORDER BY relevance_score DESC, name ASC 
                 LIMIT ? OFFSET ?";
        
        $search_params = array_merge([$search_pattern, $search_pattern, $search_pattern], $params, [$limit, $offset]);
        
        $stmt = $db->prepare($query);
        $stmt->execute($search_params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'search_term' => $search_term,
            'products' => $products,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    } catch (Exception $e) {
        error_log("Search products error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleGetFeaturedProducts($db) {
    if (!$db) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection not available',
            'products' => []
        ]);
        return;
    }

    try {
        $limit = min(20, max(1, intval($_GET['limit'] ?? 8)));

        // Get featured products (could be based on sales, rating, or manually curated)
        // For now, we'll get products with good stock and recent creation
        $query = "SELECT * FROM products 
                 WHERE is_active = 1 AND stock_quantity > 5 
                 ORDER BY created_at DESC, stock_quantity DESC 
                 LIMIT ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$limit]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'featured_products' => $products,
            'count' => count($products)
        ]);
    } catch (Exception $e) {
        error_log("Get featured products error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handlePurchaseProduct($db) {
    if (!$db) {
        http_response_code(503);
        echo json_encode(['error' => 'Database connection not available']);
        return;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $product_id = $data['product_id'] ?? '';
        $quantity = max(1, intval($data['quantity'] ?? 1));
        $customer_email = $data['customer_email'] ?? '';
        $customer_name = $data['customer_name'] ?? '';
        $customer_phone = $data['customer_phone'] ?? '';
        $shipping_address = $data['shipping_address'] ?? '';
        $notes = $data['notes'] ?? '';

        if (empty($product_id) || empty($customer_email) || empty($customer_name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Product ID, customer email, and name are required']);
            return;
        }

        // Validate email
        if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        // Get product details
        $product_query = "SELECT * FROM products WHERE id = ? AND is_active = 1";
        $product_stmt = $db->prepare($product_query);
        $product_stmt->execute([$product_id]);
        $product = $product_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            return;
        }

        // Check stock availability
        if (!$product['is_digital'] && $product['stock_quantity'] < $quantity) {
            http_response_code(409);
            echo json_encode([
                'error' => 'Insufficient stock',
                'available_quantity' => $product['stock_quantity'],
                'requested_quantity' => $quantity
            ]);
            return;
        }

        // Start transaction
        $db->beginTransaction();

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
            
            // Update customer info if provided
            if ($customer_phone || $shipping_address) {
                $update_customer_query = "UPDATE customers SET ";
                $update_params = [];
                $update_fields = [];
                
                if ($customer_phone) {
                    $update_fields[] = "phone = ?";
                    $update_params[] = $customer_phone;
                }
                if ($shipping_address) {
                    $update_fields[] = "address = ?";
                    $update_params[] = $shipping_address;
                }
                
                $update_params[] = $customer_id;
                $update_customer_query .= implode(', ', $update_fields) . " WHERE id = ?";
                
                $update_customer_stmt = $db->prepare($update_customer_query);
                $update_customer_stmt->execute($update_params);
            }
        }

        // Generate order number
        $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Calculate totals
        $subtotal = $product['price'] * $quantity;
        $tax_amount = $subtotal * 0.075; // 7.5% VAT in Nigeria
        $shipping_amount = $product['is_digital'] ? 0 : calculateShipping($product, $quantity);
        $total_amount = $subtotal + $tax_amount + $shipping_amount;

        // Create order
        $create_order_query = "INSERT INTO orders (customer_id, order_number, order_type, subtotal, tax_amount, shipping_amount, total_amount, shipping_address, notes) 
                              VALUES (?, ?, 'product', ?, ?, ?, ?, ?, ?)";
        $create_order_stmt = $db->prepare($create_order_query);
        $create_order_stmt->execute([$customer_id, $order_number, $subtotal, $tax_amount, $shipping_amount, $total_amount, $shipping_address, $notes]);
        $order_id = $db->lastInsertId();

        // Add order item
        $create_item_query = "INSERT INTO order_items (order_id, item_type, item_id, item_name, quantity, unit_price, total_price) 
                             VALUES (?, 'product', ?, ?, ?, ?, ?)";
        $create_item_stmt = $db->prepare($create_item_query);
        $create_item_stmt->execute([
            $order_id, $product_id, $product['name'], $quantity, $product['price'], 
            $product['price'] * $quantity
        ]);

        // Update stock quantity for physical products
        if (!$product['is_digital']) {
            $update_stock_query = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
            $update_stock_stmt = $db->prepare($update_stock_query);
            $update_stock_stmt->execute([$quantity, $product_id]);
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Product purchased successfully',
            'order_number' => $order_number,
            'order_id' => $order_id,
            'product' => $product,
            'purchase_details' => [
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'tax_amount' => $tax_amount,
                'shipping_amount' => $shipping_amount,
                'total_amount' => $total_amount
            ],
            'estimated_delivery' => $product['is_digital'] ? 'Immediate' : calculateDeliveryDate()
        ]);

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollback();
        }
        error_log("Purchase product error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleCreateProduct($db) {
    if (!$db) {
        http_response_code(503);
        echo json_encode(['error' => 'Database connection not available']);
        return;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $name = $data['name'] ?? '';
        $description = $data['description'] ?? '';
        $category = $data['category'] ?? '';
        $price = floatval($data['price'] ?? 0);
        $stock_quantity = intval($data['stock_quantity'] ?? 0);
        $sku = $data['sku'] ?? '';
        $weight_kg = floatval($data['weight_kg'] ?? 0);
        $dimensions_cm = $data['dimensions_cm'] ?? '';
        $is_digital = $data['is_digital'] ?? false;
        $image_url = $data['image_url'] ?? '';

        if (empty($name) || empty($category) || $price <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Name, category, and valid price are required']);
            return;
        }

        // Generate SKU if not provided
        if (empty($sku)) {
            $sku = generateSKU($category, $name);
        }

        $query = "INSERT INTO products (name, description, category, price, stock_quantity, sku, weight_kg, dimensions_cm, is_digital, image_url) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $description, $category, $price, $stock_quantity, $sku, $weight_kg, $dimensions_cm, $is_digital, $image_url]);
        
        $product_id = $db->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Product created successfully',
            'product_id' => $product_id,
            'sku' => $sku
        ]);

    } catch (Exception $e) {
        error_log("Create product error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleUpdateProduct($db) {
    if (!$db) {
        http_response_code(503);
        echo json_encode(['error' => 'Database connection not available']);
        return;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $product_id = $data['product_id'] ?? '';

        if (empty($product_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Product ID is required']);
            return;
        }

        $update_fields = [];
        $params = [];

        $allowed_fields = ['name', 'description', 'category', 'price', 'stock_quantity', 'sku', 'weight_kg', 'dimensions_cm', 'is_active', 'is_digital', 'image_url'];
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($update_fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }

        $params[] = $product_id;
        $query = "UPDATE products SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute($params)) {
            echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update product']);
        }

    } catch (Exception $e) {
        error_log("Update product error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleDeleteProduct($db) {
    if (!$db) {
        http_response_code(503);
        echo json_encode(['error' => 'Database connection not available']);
        return;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $product_id = $data['product_id'] ?? '';

        if (empty($product_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Product ID is required']);
            return;
        }

        // Soft delete by setting is_active to 0
        $query = "UPDATE products SET is_active = 0 WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$product_id])) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete product']);
        }

    } catch (Exception $e) {
        error_log("Delete product error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

// Helper functions
function calculateShipping($product, $quantity) {
    // Simple shipping calculation based on weight and distance
    $weight = $product['weight_kg'] * $quantity;
    $base_shipping = 10.00; // Base shipping cost in NGN
    $weight_factor = $weight * 2.00; // Additional cost per kg
    
    return $base_shipping + $weight_factor;
}

function calculateDeliveryDate() {
    // Add 3-7 business days for delivery
    $delivery_days = rand(3, 7);
    return date('Y-m-d', strtotime("+$delivery_days days"));
}

function generateSKU($category, $name) {
    $category_code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $category), 0, 3));
    $name_code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 3));
    $random = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    return $category_code . '-' . $name_code . '-' . $random;
}
?>