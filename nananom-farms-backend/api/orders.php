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
    error_log("Database connection failed in orders: " . $e->getMessage());
    // Continue without database - will use fallback responses
}

$action = $_GET['action'] ?? '';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        switch ($action) {
            case 'track':
                handleTrackOrder($db);
                break;
            case 'customer':
                handleGetCustomerOrders($db);
                break;
            case 'statistics':
                handleGetOrderStatistics($db);
                break;
            default:
                handleGetOrders($db);
        }
        break;
    case 'PUT':
        handleUpdateOrder($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleGetOrders($db) {
    // Check if user is authenticated (admin or agent)
    $user = Auth::getCurrentUser();
    if (!$user || ($user->user_type !== 'admin' && $user->user_type !== 'agent')) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }

    if (!$db) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection not available',
            'orders' => []
        ]);
        return;
    }

    try {
        $status = $_GET['status'] ?? '';
        $order_type = $_GET['order_type'] ?? '';
        $payment_status = $_GET['payment_status'] ?? '';
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';
        $customer_email = $_GET['customer_email'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(50, max(1, intval($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $where_conditions = [];
        $params = [];

        if ($status) {
            $where_conditions[] = "o.status = ?";
            $params[] = $status;
        }

        if ($order_type) {
            $where_conditions[] = "o.order_type = ?";
            $params[] = $order_type;
        }

        if ($payment_status) {
            $where_conditions[] = "o.payment_status = ?";
            $params[] = $payment_status;
        }

        if ($date_from) {
            $where_conditions[] = "DATE(o.created_at) >= ?";
            $params[] = $date_from;
        }

        if ($date_to) {
            $where_conditions[] = "DATE(o.created_at) <= ?";
            $params[] = $date_to;
        }

        if ($customer_email) {
            $where_conditions[] = "c.email LIKE ?";
            $params[] = "%$customer_email%";
        }

        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

        // Get total count
        $count_query = "SELECT COUNT(*) as total 
                       FROM orders o 
                       JOIN customers c ON o.customer_id = c.id 
                       $where_clause";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get orders with customer info
        $query = "SELECT o.*, c.full_name as customer_name, c.email as customer_email, c.phone as customer_phone
                 FROM orders o
                 JOIN customers c ON o.customer_id = c.id
                 $where_clause
                 ORDER BY o.created_at DESC
                 LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get order items for each order
        foreach ($orders as &$order) {
            $items_query = "SELECT * FROM order_items WHERE order_id = ? ORDER BY id";
            $items_stmt = $db->prepare($items_query);
            $items_stmt->execute([$order['id']]);
            $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get orders error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleTrackOrder($db) {
    $order_number = $_GET['order_number'] ?? '';
    $customer_email = $_GET['customer_email'] ?? '';

    if (empty($order_number)) {
        http_response_code(400);
        echo json_encode(['error' => 'Order number is required']);
        return;
    }

    if (!$db) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection not available'
        ]);
        return;
    }

    try {
        // Get order details
        $query = "SELECT o.*, c.full_name as customer_name, c.email as customer_email, c.phone as customer_phone
                 FROM orders o
                 JOIN customers c ON o.customer_id = c.id
                 WHERE o.order_number = ?";
        
        $params = [$order_number];
        
        // If customer email provided, add it to the query for security
        if ($customer_email) {
            $query .= " AND c.email = ?";
            $params[] = $customer_email;
        }

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            return;
        }

        // Get order items
        $items_query = "SELECT * FROM order_items WHERE order_id = ? ORDER BY id";
        $items_stmt = $db->prepare($items_query);
        $items_stmt->execute([$order['id']]);
        $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Generate tracking timeline
        $timeline = generateTrackingTimeline($order);

        echo json_encode([
            'success' => true,
            'order' => $order,
            'tracking_timeline' => $timeline
        ]);

    } catch (Exception $e) {
        error_log("Track order error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleGetCustomerOrders($db) {
    $customer_email = $_GET['customer_email'] ?? '';

    if (empty($customer_email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Customer email is required']);
        return;
    }

    if (!$db) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection not available',
            'orders' => []
        ]);
        return;
    }

    try {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(20, max(1, intval($_GET['limit'] ?? 5)));
        $offset = ($page - 1) * $limit;

        // Get customer orders
        $query = "SELECT o.*, c.full_name as customer_name
                 FROM orders o
                 JOIN customers c ON o.customer_id = c.id
                 WHERE c.email = ?
                 ORDER BY o.created_at DESC
                 LIMIT ? OFFSET ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$customer_email, $limit, $offset]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count
        $count_query = "SELECT COUNT(*) as total 
                       FROM orders o 
                       JOIN customers c ON o.customer_id = c.id 
                       WHERE c.email = ?";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->execute([$customer_email]);
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get order items for each order
        foreach ($orders as &$order) {
            $items_query = "SELECT * FROM order_items WHERE order_id = ? ORDER BY id";
            $items_stmt = $db->prepare($items_query);
            $items_stmt->execute([$order['id']]);
            $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);

    } catch (Exception $e) {
        error_log("Get customer orders error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleGetOrderStatistics($db) {
    // Check if user is authenticated (admin only)
    $user = Auth::getCurrentUser();
    if (!$user || $user->user_type !== 'admin') {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }

    if (!$db) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection not available'
        ]);
        return;
    }

    try {
        $period = $_GET['period'] ?? 'month'; // week, month, year
        $date = $_GET['date'] ?? date('Y-m-d');

        switch ($period) {
            case 'week':
                $start_date = date('Y-m-d', strtotime('monday this week', strtotime($date)));
                $end_date = date('Y-m-d', strtotime('sunday this week', strtotime($date)));
                break;
            case 'year':
                $start_date = date('Y-01-01', strtotime($date));
                $end_date = date('Y-12-31', strtotime($date));
                break;
            default: // month
                $start_date = date('Y-m-01', strtotime($date));
                $end_date = date('Y-m-t', strtotime($date));
        }

        // Overall statistics
        $stats_query = "
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as average_order_value,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
                SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
                SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as payment_pending,
                SUM(CASE WHEN order_type = 'product' THEN 1 ELSE 0 END) as product_orders,
                SUM(CASE WHEN order_type = 'service' THEN 1 ELSE 0 END) as service_orders,
                SUM(CASE WHEN order_type = 'mixed' THEN 1 ELSE 0 END) as mixed_orders
            FROM orders 
            WHERE DATE(created_at) BETWEEN ? AND ?
        ";
        
        $stmt = $db->prepare($stats_query);
        $stmt->execute([$start_date, $end_date]);
        $overall_stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Daily breakdown
        $daily_query = "
            SELECT 
                DATE(created_at) as order_date,
                COUNT(*) as orders_count,
                SUM(total_amount) as daily_revenue
            FROM orders 
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY order_date
        ";
        
        $stmt = $db->prepare($daily_query);
        $stmt->execute([$start_date, $end_date]);
        $daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Top products/services
        $items_query = "
            SELECT 
                oi.item_name,
                oi.item_type,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.total_price) as total_revenue,
                COUNT(DISTINCT oi.order_id) as order_count
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY oi.item_id, oi.item_type, oi.item_name
            ORDER BY total_revenue DESC
            LIMIT 10
        ";
        
        $stmt = $db->prepare($items_query);
        $stmt->execute([$start_date, $end_date]);
        $top_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'period' => $period,
            'date_range' => [
                'start' => $start_date,
                'end' => $end_date
            ],
            'statistics' => [
                'overall' => $overall_stats,
                'daily_breakdown' => $daily_stats,
                'top_items' => $top_items
            ]
        ]);

    } catch (Exception $e) {
        error_log("Get order statistics error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleUpdateOrder($db) {
    // Check if user is authenticated (admin or agent)
    $user = Auth::getCurrentUser();
    if (!$user || ($user->user_type !== 'admin' && $user->user_type !== 'agent')) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }

    if (!$db) {
        http_response_code(503);
        echo json_encode(['error' => 'Database connection not available']);
        return;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $order_id = $data['order_id'] ?? '';

        if (empty($order_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID is required']);
            return;
        }

        // Check if order exists
        $check_query = "SELECT id, status, payment_status FROM orders WHERE id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$order_id]);
        $order = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            return;
        }

        $update_fields = [];
        $params = [];

        // Allowed fields for update
        $allowed_fields = ['status', 'payment_status', 'payment_method', 'shipping_address', 'notes'];
        
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

        $params[] = $order_id;
        $query = "UPDATE orders SET " . implode(', ', $update_fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute($params)) {
            echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update order']);
        }

    } catch (Exception $e) {
        error_log("Update order error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function generateTrackingTimeline($order) {
    $timeline = [];
    $status = $order['status'];
    $payment_status = $order['payment_status'];
    $created_at = $order['created_at'];

    // Order placed
    $timeline[] = [
        'status' => 'Order Placed',
        'description' => 'Your order has been received and is being processed',
        'date' => $created_at,
        'completed' => true
    ];

    // Payment
    if ($payment_status === 'paid') {
        $timeline[] = [
            'status' => 'Payment Confirmed',
            'description' => 'Payment has been successfully processed',
            'date' => $created_at,
            'completed' => true
        ];
    } else {
        $timeline[] = [
            'status' => 'Payment Pending',
            'description' => 'Waiting for payment confirmation',
            'date' => null,
            'completed' => false
        ];
    }

    // Order status timeline
    $statuses = ['confirmed', 'processing', 'shipped', 'delivered'];
    $current_index = array_search($status, $statuses);

    foreach ($statuses as $index => $timeline_status) {
        $completed = $index <= $current_index;
        $description = getStatusDescription($timeline_status);
        
        $timeline[] = [
            'status' => ucfirst($timeline_status),
            'description' => $description,
            'date' => $completed ? $created_at : null,
            'completed' => $completed
        ];
    }

    return $timeline;
}

function getStatusDescription($status) {
    $descriptions = [
        'confirmed' => 'Order confirmed and being prepared',
        'processing' => 'Order is being processed and packaged',
        'shipped' => 'Order has been shipped and is on the way',
        'delivered' => 'Order has been successfully delivered'
    ];

    return $descriptions[$status] ?? 'Status update';
}
?>