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
    error_log("Database connection failed in enquiries: " . $e->getMessage());
    // Continue without database - will use fallback responses
}

// Parse URL for calendar-specific endpoints
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_segments = explode('/', trim($path, '/'));

// Check for calendar-specific endpoints
$action = $_GET['action'] ?? '';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        handleCreateEnquiry($db);
        break;
    case 'GET':
        if ($action === 'calendar') {
            handleGetCalendarData($db);
        } elseif ($action === 'availability') {
            handleCheckAvailability($db);
        } elseif ($action === 'schedule') {
            handleGetSchedule($db);
        } else {
            handleGetEnquiries($db);
        }
        break;
    case 'PUT':
        handleUpdateEnquiry($db);
        break;
    case 'DELETE':
        handleDeleteEnquiry($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleCreateEnquiry($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $full_name = $data['full_name'] ?? '';
    $email = $data['email'] ?? '';
    $company = $data['company'] ?? '';
    $subject = $data['subject'] ?? '';
    $message = $data['message'] ?? '';
    $preferred_date = $data['preferred_date'] ?? null;
    $preferred_time = $data['preferred_time'] ?? null;
    $urgency_level = $data['urgency_level'] ?? 'medium';

    if (empty($full_name) || empty($email) || empty($subject) || empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Full name, email, subject, and message are required']);
        return;
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        return;
    }

    // Validate date if provided
    if ($preferred_date) {
        $date_obj = DateTime::createFromFormat('Y-m-d', $preferred_date);
        if (!$date_obj || $date_obj->format('Y-m-d') !== $preferred_date) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD']);
            return;
        }
        
        // Check if date is in the past
        $today = new DateTime();
        if ($date_obj < $today) {
            http_response_code(400);
            echo json_encode(['error' => 'Preferred date cannot be in the past']);
            return;
        }
    }

    // Validate time if provided
    if ($preferred_time) {
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $preferred_time)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid time format. Use HH:MM']);
            return;
        }
    }

    // Validate urgency level
    $valid_urgency_levels = ['low', 'medium', 'high', 'urgent'];
    if (!in_array($urgency_level, $valid_urgency_levels)) {
        $urgency_level = 'medium';
    }

    if ($db) {
        try {
            $query = "INSERT INTO enquiries (full_name, email, company, subject, message, preferred_date, preferred_time, urgency_level) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$full_name, $email, $company, $subject, $message, $preferred_date, $preferred_time, $urgency_level])) {
                $enquiry_id = $db->lastInsertId();
                echo json_encode([
                    'success' => true,
                    'message' => 'Enquiry submitted successfully',
                    'enquiry_id' => $enquiry_id,
                    'calendar_info' => [
                        'preferred_date' => $preferred_date,
                        'preferred_time' => $preferred_time,
                        'urgency_level' => $urgency_level
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to submit enquiry']);
            }
        } catch (Exception $e) {
            error_log("Create enquiry error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database error occurred']);
        }
    } else {
        // Fallback response when database is not available
        echo json_encode([
            'success' => true,
            'message' => 'Enquiry received (database temporarily unavailable)',
            'enquiry_id' => uniqid(),
            'calendar_info' => [
                'preferred_date' => $preferred_date,
                'preferred_time' => $preferred_time,
                'urgency_level' => $urgency_level
            ]
        ]);
    }
}

function handleGetEnquiries($db) {
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
            'enquiries' => [],
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 0, 'pages' => 0]
        ]);
        return;
    }

    try {
        $status = $_GET['status'] ?? '';
        $assigned_to = $_GET['assigned_to'] ?? '';
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';
        $urgency_level = $_GET['urgency_level'] ?? '';
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
            $where_conditions[] = "preferred_date >= ?";
            $params[] = $date_from;
        }

        if ($date_to) {
            $where_conditions[] = "preferred_date <= ?";
            $params[] = $date_to;
        }

        if ($urgency_level) {
            $where_conditions[] = "urgency_level = ?";
            $params[] = $urgency_level;
        }

        // Agents can only see enquiries assigned to them or unassigned ones
        if ($user->user_type === 'agent') {
            $where_conditions[] = "(assigned_to = ? OR assigned_to IS NULL)";
            $params[] = $user->user_id;
        }

        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

        // Get total count
        $count_query = "SELECT COUNT(*) as total FROM enquiries $where_clause";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get enquiries
        $query = "SELECT e.*, sa.full_name as assigned_agent_name 
                  FROM enquiries e 
                  LEFT JOIN support_agents sa ON e.assigned_to = sa.id 
                  $where_clause 
                  ORDER BY e.urgency_level DESC, e.preferred_date ASC, e.preferred_time ASC, e.created_at DESC 
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $enquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'enquiries' => $enquiries,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get enquiries error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleGetCalendarData($db) {
    // Check if user is authenticated
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
            'calendar_data' => []
        ]);
        return;
    }

    try {
        $month = $_GET['month'] ?? date('Y-m');
        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));

        // Get enquiries for the month
        $enquiry_query = "SELECT id, full_name, subject, preferred_date, preferred_time, urgency_level, status 
                         FROM enquiries 
                         WHERE preferred_date BETWEEN ? AND ?
                         ORDER BY preferred_date ASC, preferred_time ASC";
        
        $stmt = $db->prepare($enquiry_query);
        $stmt->execute([$start_date, $end_date]);
        $enquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get bookings for the month
        $booking_query = "SELECT id, full_name, service_type, booking_date, booking_time, status 
                         FROM bookings 
                         WHERE booking_date BETWEEN ? AND ?
                         ORDER BY booking_date ASC, booking_time ASC";
        
        $stmt = $db->prepare($booking_query);
        $stmt->execute([$start_date, $end_date]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format calendar data
        $calendar_data = [];
        
        foreach ($enquiries as $enquiry) {
            $date = $enquiry['preferred_date'];
            if (!isset($calendar_data[$date])) {
                $calendar_data[$date] = ['enquiries' => [], 'bookings' => []];
            }
            $calendar_data[$date]['enquiries'][] = $enquiry;
        }

        foreach ($bookings as $booking) {
            $date = $booking['booking_date'];
            if (!isset($calendar_data[$date])) {
                $calendar_data[$date] = ['enquiries' => [], 'bookings' => []];
            }
            $calendar_data[$date]['bookings'][] = $booking;
        }

        echo json_encode([
            'success' => true,
            'month' => $month,
            'calendar_data' => $calendar_data,
            'statistics' => [
                'total_enquiries' => count($enquiries),
                'total_bookings' => count($bookings),
                'busy_days' => count($calendar_data)
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get calendar data error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleCheckAvailability($db) {
    $date = $_GET['date'] ?? '';
    $time = $_GET['time'] ?? '';

    if (empty($date)) {
        http_response_code(400);
        echo json_encode(['error' => 'Date is required']);
        return;
    }

    if (!$db) {
        echo json_encode([
            'success' => true,
            'available' => true,
            'message' => 'Database not available, assuming slot is free'
        ]);
        return;
    }

    try {
        // Check for conflicts in enquiries
        $enquiry_query = "SELECT COUNT(*) as count FROM enquiries 
                         WHERE preferred_date = ? AND preferred_time = ? AND status != 'cancelled'";
        $params = [$date];
        
        if ($time) {
            $enquiry_query = "SELECT COUNT(*) as count FROM enquiries 
                             WHERE preferred_date = ? AND preferred_time = ? AND status != 'cancelled'";
            $params[] = $time;
        }

        $stmt = $db->prepare($enquiry_query);
        $stmt->execute($params);
        $enquiry_conflicts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Check for conflicts in bookings
        $booking_query = "SELECT COUNT(*) as count FROM bookings 
                         WHERE booking_date = ? AND status != 'cancelled'";
        $booking_params = [$date];
        
        if ($time) {
            $booking_query = "SELECT COUNT(*) as count FROM bookings 
                             WHERE booking_date = ? AND booking_time = ? AND status != 'cancelled'";
            $booking_params[] = $time;
        }

        $stmt = $db->prepare($booking_query);
        $stmt->execute($booking_params);
        $booking_conflicts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $total_conflicts = $enquiry_conflicts + $booking_conflicts;
        $available = $total_conflicts === 0;

        echo json_encode([
            'success' => true,
            'available' => $available,
            'conflicts' => [
                'enquiries' => $enquiry_conflicts,
                'bookings' => $booking_conflicts,
                'total' => $total_conflicts
            ],
            'date' => $date,
            'time' => $time
        ]);
    } catch (Exception $e) {
        error_log("Check availability error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleGetSchedule($db) {
    // Check if user is authenticated
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
            'schedule' => []
        ]);
        return;
    }

    try {
        $date = $_GET['date'] ?? date('Y-m-d');
        $agent_id = $_GET['agent_id'] ?? null;

        $where_conditions = ["DATE(preferred_date) = ?"];
        $params = [$date];

        if ($agent_id) {
            $where_conditions[] = "assigned_to = ?";
            $params[] = $agent_id;
        } elseif ($user->user_type === 'agent') {
            $where_conditions[] = "(assigned_to = ? OR assigned_to IS NULL)";
            $params[] = $user->user_id;
        }

        $where_clause = implode(' AND ', $where_conditions);

        // Get schedule for the day
        $query = "SELECT e.*, sa.full_name as assigned_agent_name 
                 FROM enquiries e 
                 LEFT JOIN support_agents sa ON e.assigned_to = sa.id 
                 WHERE $where_clause
                 ORDER BY e.preferred_time ASC, e.urgency_level DESC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'date' => $date,
            'schedule' => $schedule,
            'count' => count($schedule)
        ]);
    } catch (Exception $e) {
        error_log("Get schedule error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleUpdateEnquiry($db) {
    $user = Auth::requireAuth();
    if ($user->user_type !== 'admin' && $user->user_type !== 'agent') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        return;
    }

    if (!$db) {
        http_response_code(503);
        echo json_encode(['error' => 'Database connection not available']);
        return;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $enquiry_id = $data['enquiry_id'] ?? '';
        $status = $data['status'] ?? '';
        $assigned_to = $data['assigned_to'] ?? null;
        $follow_up_date = $data['follow_up_date'] ?? null;
        $follow_up_notes = $data['follow_up_notes'] ?? '';
        $preferred_date = $data['preferred_date'] ?? null;
        $preferred_time = $data['preferred_time'] ?? null;

        if (empty($enquiry_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Enquiry ID is required']);
            return;
        }

        // Check if enquiry exists and user has permission
        $check_query = "SELECT id, assigned_to FROM enquiries WHERE id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$enquiry_id]);
        $enquiry = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$enquiry) {
            http_response_code(404);
            echo json_encode(['error' => 'Enquiry not found']);
            return;
        }

        // Agents can only update enquiries assigned to them
        if ($user->user_type === 'agent' && $enquiry['assigned_to'] != $user->user_id) {
            http_response_code(403);
            echo json_encode(['error' => 'You can only update enquiries assigned to you']);
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

        if ($follow_up_date) {
            $update_fields[] = "follow_up_date = ?";
            $params[] = $follow_up_date;
        }

        if ($follow_up_notes) {
            $update_fields[] = "follow_up_notes = ?";
            $params[] = $follow_up_notes;
        }

        if ($preferred_date) {
            $update_fields[] = "preferred_date = ?";
            $params[] = $preferred_date;
        }

        if ($preferred_time) {
            $update_fields[] = "preferred_time = ?";
            $params[] = $preferred_time;
        }

        if (empty($update_fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            return;
        }

        $params[] = $enquiry_id;
        $query = "UPDATE enquiries SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute($params)) {
            echo json_encode(['success' => true, 'message' => 'Enquiry updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update enquiry']);
        }
    } catch (Exception $e) {
        error_log("Update enquiry error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleDeleteEnquiry($db) {
    $user = Auth::requireAuth();
    if ($user->user_type !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Only admins can delete enquiries']);
        return;
    }

    if (!$db) {
        http_response_code(503);
        echo json_encode(['error' => 'Database connection not available']);
        return;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $enquiry_id = $data['enquiry_id'] ?? '';

        if (empty($enquiry_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Enquiry ID is required']);
            return;
        }

        $query = "DELETE FROM enquiries WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$enquiry_id])) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Enquiry deleted successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Enquiry not found']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete enquiry']);
        }
    } catch (Exception $e) {
        error_log("Delete enquiry error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}
?>