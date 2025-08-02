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
    error_log("Database connection failed in services: " . $e->getMessage());
    // Continue without database - will use fallback responses
}

$action = $_GET['action'] ?? '';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        switch ($action) {
            case 'categories':
                handleGetServiceCategories($db);
                break;
            case 'availability':
                handleCheckServiceAvailability($db);
                break;
            default:
                handleGetServices($db);
        }
        break;
    case 'POST':
        switch ($action) {
            case 'book':
                handleBookService($db);
                break;
            default:
                if (Auth::getCurrentUser() && Auth::getCurrentUser()->user_type === 'admin') {
                    handleCreateService($db);
                } else {
                    http_response_code(401);
                    echo json_encode(['error' => 'Unauthorized']);
                }
        }
        break;
    case 'PUT':
        if (Auth::getCurrentUser() && Auth::getCurrentUser()->user_type === 'admin') {
            handleUpdateService($db);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
        }
        break;
    case 'DELETE':
        if (Auth::getCurrentUser() && Auth::getCurrentUser()->user_type === 'admin') {
            handleDeleteService($db);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleGetServices($db) {
    if (!$db) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection not available',
            'services' => []
        ]);
        return;
    }

    try {
        $category = $_GET['category'] ?? '';
        $is_active = $_GET['is_active'] ?? 1;
        $search = $_GET['search'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(50, max(1, intval($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $where_conditions = ['is_active = ?'];
        $params = [$is_active];

        if ($category) {
            $where_conditions[] = "category = ?";
            $params[] = $category;
        }

        if ($search) {
            $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

        // Get total count
        $count_query = "SELECT COUNT(*) as total FROM services $where_clause";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get services
        $query = "SELECT * FROM services $where_clause ORDER BY category, name LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'services' => $services,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get services error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleGetServiceCategories($db) {
    if (!$db) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection not available',
            'categories' => []
        ]);
        return;
    }

    try {
        $query = "SELECT category, COUNT(*) as service_count, AVG(price) as avg_price 
                 FROM services 
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
        error_log("Get service categories error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleCheckServiceAvailability($db) {
    $service_id = $_GET['service_id'] ?? '';
    $date = $_GET['date'] ?? '';
    $time = $_GET['time'] ?? '';

    if (empty($service_id) || empty($date)) {
        http_response_code(400);
        echo json_encode(['error' => 'Service ID and date are required']);
        return;
    }

    if (!$db) {
        echo json_encode([
            'success' => true,
            'available' => true,
            'message' => 'Database not available, assuming available'
        ]);
        return;
    }

    try {
        // Get service details
        $service_query = "SELECT * FROM services WHERE id = ? AND is_active = 1";
        $service_stmt = $db->prepare($service_query);
        $service_stmt->execute([$service_id]);
        $service = $service_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$service) {
            http_response_code(404);
            echo json_encode(['error' => 'Service not found']);
            return;
        }

        // Check existing bookings for the service
        $booking_query = "SELECT COUNT(*) as count 
                         FROM order_items oi 
                         JOIN orders o ON oi.order_id = o.id 
                         WHERE oi.item_type = 'service' 
                         AND oi.item_id = ? 
                         AND oi.booking_date = ?";
        
        $params = [$service_id, $date];
        
        if ($time) {
            $booking_query .= " AND oi.booking_time = ?";
            $params[] = $time;
        }
        
        $booking_query .= " AND o.status NOT IN ('cancelled')";

        $booking_stmt = $db->prepare($booking_query);
        $booking_stmt->execute($params);
        $existing_bookings = $booking_stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $available = $existing_bookings < $service['max_participants'];

        echo json_encode([
            'success' => true,
            'available' => $available,
            'service' => $service,
            'existing_bookings' => $existing_bookings,
            'max_participants' => $service['max_participants'],
            'date' => $date,
            'time' => $time
        ]);
    } catch (Exception $e) {
        error_log("Check service availability error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleBookService($db) {
    if (!$db) {
        http_response_code(503);
        echo json_encode(['error' => 'Database connection not available']);
        return;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $service_id = $data['service_id'] ?? '';
        $customer_email = $data['customer_email'] ?? '';
        $customer_name = $data['customer_name'] ?? '';
        $customer_phone = $data['customer_phone'] ?? '';
        $booking_date = $data['booking_date'] ?? '';
        $booking_time = $data['booking_time'] ?? '';
        $quantity = max(1, intval($data['quantity'] ?? 1));
        $notes = $data['notes'] ?? '';

        if (empty($service_id) || empty($customer_email) || empty($customer_name) || empty($booking_date)) {
            http_response_code(400);
            echo json_encode(['error' => 'Service ID, customer email, name, and booking date are required']);
            return;
        }

        // Validate email
        if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        // Get service details
        $service_query = "SELECT * FROM services WHERE id = ? AND is_active = 1";
        $service_stmt = $db->prepare($service_query);
        $service_stmt->execute([$service_id]);
        $service = $service_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$service) {
            http_response_code(404);
            echo json_encode(['error' => 'Service not found']);
            return;
        }

        // Check availability
        if ($service['requires_booking']) {
            $booking_query = "SELECT COUNT(*) as count 
                             FROM order_items oi 
                             JOIN orders o ON oi.order_id = o.id 
                             WHERE oi.item_type = 'service' 
                             AND oi.item_id = ? 
                             AND oi.booking_date = ?";
            
            $params = [$service_id, $booking_date];
            
            if ($booking_time) {
                $booking_query .= " AND oi.booking_time = ?";
                $params[] = $booking_time;
            }
            
            $booking_query .= " AND o.status NOT IN ('cancelled')";

            $booking_stmt = $db->prepare($booking_query);
            $booking_stmt->execute($params);
            $existing_bookings = $booking_stmt->fetch(PDO::FETCH_ASSOC)['count'];

            if (($existing_bookings + $quantity) > $service['max_participants']) {
                http_response_code(409);
                echo json_encode([
                    'error' => 'Service not available for the requested date/time',
                    'available_slots' => $service['max_participants'] - $existing_bookings
                ]);
                return;
            }
        }

        // Start transaction
        $db->beginTransaction();

        // Create or get customer
        $customer_query = "SELECT id FROM customers WHERE email = ?";
        $customer_stmt = $db->prepare($customer_query);
        $customer_stmt->execute([$customer_email]);
        $customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            $create_customer_query = "INSERT INTO customers (full_name, email, phone) VALUES (?, ?, ?)";
            $create_customer_stmt = $db->prepare($create_customer_query);
            $create_customer_stmt->execute([$customer_name, $customer_email, $customer_phone]);
            $customer_id = $db->lastInsertId();
        } else {
            $customer_id = $customer['id'];
        }

        // Generate order number
        $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Calculate totals
        $subtotal = $service['price'] * $quantity;
        $tax_amount = $subtotal * 0.075; // 7.5% VAT in Nigeria
        $total_amount = $subtotal + $tax_amount;

        // Create order
        $create_order_query = "INSERT INTO orders (customer_id, order_number, order_type, subtotal, tax_amount, total_amount, notes) 
                              VALUES (?, ?, 'service', ?, ?, ?, ?)";
        $create_order_stmt = $db->prepare($create_order_query);
        $create_order_stmt->execute([$customer_id, $order_number, $subtotal, $tax_amount, $total_amount, $notes]);
        $order_id = $db->lastInsertId();

        // Add order item
        $create_item_query = "INSERT INTO order_items (order_id, item_type, item_id, item_name, quantity, unit_price, total_price, booking_date, booking_time) 
                             VALUES (?, 'service', ?, ?, ?, ?, ?, ?, ?)";
        $create_item_stmt = $db->prepare($create_item_query);
        $create_item_stmt->execute([
            $order_id, $service_id, $service['name'], $quantity, $service['price'], 
            $service['price'] * $quantity, $booking_date, $booking_time
        ]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Service booked successfully',
            'order_number' => $order_number,
            'order_id' => $order_id,
            'service' => $service,
            'booking_details' => [
                'date' => $booking_date,
                'time' => $booking_time,
                'quantity' => $quantity
            ],
            'total_amount' => $total_amount,
            'tax_amount' => $tax_amount
        ]);

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollback();
        }
        error_log("Book service error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleCreateService($db) {
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
        $duration_minutes = intval($data['duration_minutes'] ?? 60);
        $max_participants = intval($data['max_participants'] ?? 1);
        $requires_booking = $data['requires_booking'] ?? true;
        $image_url = $data['image_url'] ?? '';

        if (empty($name) || empty($category) || $price <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Name, category, and valid price are required']);
            return;
        }

        $query = "INSERT INTO services (name, description, category, price, duration_minutes, requires_booking, max_participants, image_url) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $description, $category, $price, $duration_minutes, $requires_booking, $max_participants, $image_url]);
        
        $service_id = $db->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Service created successfully',
            'service_id' => $service_id
        ]);

    } catch (Exception $e) {
        error_log("Create service error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleUpdateService($db) {
    if (!$db) {
        http_response_code(503);
        echo json_encode(['error' => 'Database connection not available']);
        return;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $service_id = $data['service_id'] ?? '';

        if (empty($service_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Service ID is required']);
            return;
        }

        $update_fields = [];
        $params = [];

        $allowed_fields = ['name', 'description', 'category', 'price', 'duration_minutes', 'requires_booking', 'max_participants', 'is_active', 'image_url'];
        
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

        $params[] = $service_id;
        $query = "UPDATE services SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute($params)) {
            echo json_encode(['success' => true, 'message' => 'Service updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update service']);
        }

    } catch (Exception $e) {
        error_log("Update service error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleDeleteService($db) {
    if (!$db) {
        http_response_code(503);
        echo json_encode(['error' => 'Database connection not available']);
        return;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $service_id = $data['service_id'] ?? '';

        if (empty($service_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Service ID is required']);
            return;
        }

        // Soft delete by setting is_active to 0
        $query = "UPDATE services SET is_active = 0 WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$service_id])) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Service deleted successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Service not found']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete service']);
        }

    } catch (Exception $e) {
        error_log("Delete service error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}
?>