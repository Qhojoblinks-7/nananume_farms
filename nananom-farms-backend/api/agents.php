<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$database = new Database();
$db = $database->getConnection();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGetAgents($db);
        break;
    case 'PUT':
        handleUpdateAgent($db);
        break;
    case 'DELETE':
        handleDeleteAgent($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleGetAgents($db) {
    // Only admin can view all agents
    $user = Auth::requireAuth('admin');

    $is_active = $_GET['is_active'] ?? '';
    $region = $_GET['region'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(1, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    $where_conditions = [];
    $params = [];

    if ($is_active !== '') {
        $where_conditions[] = "is_active = ?";
        $params[] = $is_active === 'true' ? 1 : 0;
    }

    if ($region) {
        $where_conditions[] = "region = ?";
        $params[] = $region;
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM support_agents $where_clause";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get agents (exclude password_hash for security)
    $query = "SELECT id, username, email, full_name, phone, region, is_active, created_at, updated_at 
              FROM support_agents 
              $where_clause 
              ORDER BY created_at DESC 
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'agents' => $agents,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function handleUpdateAgent($db) {
    // Only admin can update agents
    $user = Auth::requireAuth('admin');

    $data = json_decode(file_get_contents('php://input'), true);
    $agent_id = $data['agent_id'] ?? '';
    $is_active = $data['is_active'] ?? null;
    $region = $data['region'] ?? '';
    $phone = $data['phone'] ?? '';

    if (empty($agent_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Agent ID is required']);
        return;
    }

    // Check if agent exists
    $check_query = "SELECT id FROM support_agents WHERE id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$agent_id]);
    
    if (!$check_stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Agent not found']);
        return;
    }

    $update_fields = [];
    $params = [];

    if ($is_active !== null) {
        $update_fields[] = "is_active = ?";
        $params[] = $is_active ? 1 : 0;
    }

    if ($region !== '') {
        $update_fields[] = "region = ?";
        $params[] = $region;
    }

    if ($phone !== '') {
        $update_fields[] = "phone = ?";
        $params[] = $phone;
    }

    if (empty($update_fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }

    $params[] = $agent_id;
    $query = "UPDATE support_agents SET " . implode(', ', $update_fields) . " WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute($params)) {
        echo json_encode(['success' => true, 'message' => 'Agent updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update agent']);
    }
}

function handleDeleteAgent($db) {
    // Only admin can delete agents
    $user = Auth::requireAuth('admin');

    $data = json_decode(file_get_contents('php://input'), true);
    $agent_id = $data['agent_id'] ?? '';

    if (empty($agent_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Agent ID is required']);
        return;
    }

    // Check if agent exists
    $check_query = "SELECT id FROM support_agents WHERE id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$agent_id]);
    
    if (!$check_stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Agent not found']);
        return;
    }

    // Check if agent has assigned enquiries or bookings
    $check_assignments_query = "SELECT 
        (SELECT COUNT(*) FROM enquiries WHERE assigned_to = ?) as enquiry_count,
        (SELECT COUNT(*) FROM bookings WHERE assigned_to = ?) as booking_count";
    $check_assignments_stmt = $db->prepare($check_assignments_query);
    $check_assignments_stmt->execute([$agent_id, $agent_id]);
    $assignments = $check_assignments_stmt->fetch(PDO::FETCH_ASSOC);

    if ($assignments['enquiry_count'] > 0 || $assignments['booking_count'] > 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Cannot delete agent. They have assigned enquiries or bookings.',
            'enquiry_count' => $assignments['enquiry_count'],
            'booking_count' => $assignments['booking_count']
        ]);
        return;
    }

    // Delete agent
    $query = "DELETE FROM support_agents WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$agent_id])) {
        echo json_encode(['success' => true, 'message' => 'Agent deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete agent']);
    }
}
?>