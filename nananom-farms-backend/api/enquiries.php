<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        handleCreateEnquiry($db);
        break;
    case 'GET':
        handleGetEnquiries($db);
        break;
    case 'PUT':
        handleUpdateEnquiry($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleCreateEnquiry($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $full_name = $data['full_name'] ?? '';
    $email = $data['email'] ?? '';
    $contact = $data['contact'] ?? '';
    $company = $data['company'] ?? '';
    $subject = $data['subject'] ?? '';
    $message = $data['message'] ?? '';

    if (empty($full_name) || empty($email) || empty($contact) || empty($subject) || empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Full name, email, contact, subject, and message are required']);
        return;
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        return;
    }

    $query = "INSERT INTO enquiries (full_name, email, contact, company, subject, message) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$full_name, $email, $contact, $company, $subject, $message])) {
        $enquiry_id = $db->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Enquiry submitted successfully',
            'enquiry_id' => $enquiry_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to submit enquiry']);
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

    $status = $_GET['status'] ?? '';
    $assigned_to = $_GET['assigned_to'] ?? '';
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
              ORDER BY e.created_at DESC 
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
}

function handleUpdateEnquiry($db) {
    $user = Auth::requireAuth();
    if ($user->user_type !== 'admin' && $user->user_type !== 'agent') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $enquiry_id = $data['enquiry_id'] ?? '';
    $status = $data['status'] ?? '';
    $assigned_to = $data['assigned_to'] ?? null;

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
}
?>