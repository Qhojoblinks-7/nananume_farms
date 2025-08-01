<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$database = new Database();
$db = $database->getConnection();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        handleCreateBooking($db);
        break;
    case 'GET':
        handleGetBookings($db);
        break;
    case 'PUT':
        handleUpdateBooking($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleCreateBooking($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $full_name = $data['full_name'] ?? '';
    $email = $data['email'] ?? '';
    $contact = $data['contact'] ?? '';
    $company = $data['company'] ?? '';
    $service_type = $data['service_type'] ?? '';
    $booking_date = $data['booking_date'] ?? '';
    $booking_time = $data['booking_time'] ?? '';
    $additional_notes = $data['additional_notes'] ?? '';

    if (empty($full_name) || empty($email) || empty($contact) || empty($service_type) || empty($booking_date)) {
        http_response_code(400);
        echo json_encode(['error' => 'Full name, email, contact, service type, and booking date are required']);
        return;
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        return;
    }

    // Validate date
    $date_obj = DateTime::createFromFormat('Y-m-d', $booking_date);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $booking_date) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD']);
        return;
    }

    // Check if booking date is in the future
    $today = new DateTime();
    $booking_datetime = new DateTime($booking_date);
    if ($booking_datetime < $today) {
        http_response_code(400);
        echo json_encode(['error' => 'Booking date must be in the future']);
        return;
    }

    $query = "INSERT INTO bookings (full_name, email, contact, company, service_type, booking_date, booking_time, additional_notes) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$full_name, $email, $contact, $company, $service_type, $booking_date, $booking_time, $additional_notes])) {
        $booking_id = $db->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Booking submitted successfully',
            'booking_id' => $booking_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to submit booking']);
    }
}

function handleGetBookings($db) {
    // Check if user is authenticated (admin or agent)
    $user = Auth::getCurrentUser();
    if (!$user || ($user->user_type !== 'admin' && $user->user_type !== 'agent')) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }

    $status = $_GET['status'] ?? '';
    $assigned_to = $_GET['assigned_to'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(1, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    $where_conditions = [];
    $params = [];

    if ($status) {
        $where_conditions[] = "status = ?";
        $params[] = $status;
    }

    if ($assigned_to) {
        $where_conditions[] = "assigned_to = ?";
        $params[] = $assigned_to;
    }

    if ($date_from) {
        $where_conditions[] = "booking_date >= ?";
        $params[] = $date_from;
    }

    if ($date_to) {
        $where_conditions[] = "booking_date <= ?";
        $params[] = $date_to;
    }

    // Agents can only see bookings assigned to them or unassigned ones
    if ($user->user_type === 'agent') {
        $where_conditions[] = "(assigned_to = ? OR assigned_to IS NULL)";
        $params[] = $user->user_id;
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM bookings $where_clause";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get bookings
    $query = "SELECT b.*, sa.full_name as assigned_agent_name 
              FROM bookings b 
              LEFT JOIN support_agents sa ON b.assigned_to = sa.id 
              $where_clause 
              ORDER BY b.booking_date ASC, b.booking_time ASC 
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'bookings' => $bookings,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function handleUpdateBooking($db) {
    $user = Auth::requireAuth();
    if ($user->user_type !== 'admin' && $user->user_type !== 'agent') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $booking_id = $data['booking_id'] ?? '';
    $status = $data['status'] ?? '';
    $assigned_to = $data['assigned_to'] ?? null;

    if (empty($booking_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Booking ID is required']);
        return;
    }

    // Check if booking exists and user has permission
    $check_query = "SELECT id, assigned_to FROM bookings WHERE id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$booking_id]);
    $booking = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        http_response_code(404);
        echo json_encode(['error' => 'Booking not found']);
        return;
    }

    // Agents can only update bookings assigned to them
    if ($user->user_type === 'agent' && $booking['assigned_to'] != $user->user_id) {
        http_response_code(403);
        echo json_encode(['error' => 'You can only update bookings assigned to you']);
        return;
    }

    $update_fields = [];
    $params = [];

    if ($status) {
        $update_fields[] = "status = ?";
        $params[] = $status;
    }

    if ($assigned_to !== null) {
        $update_fields[] = "assigned_to = ?";
        $params[] = $assigned_to;
    }

    if (empty($update_fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }

    $params[] = $booking_id;
    $query = "UPDATE bookings SET " . implode(', ', $update_fields) . " WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute($params)) {
        echo json_encode(['success' => true, 'message' => 'Booking updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update booking']);
    }
}
?>