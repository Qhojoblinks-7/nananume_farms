<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'register':
            handleAgentRegistration($db, $data);
            break;
        case 'login':
            handleAgentLogin($db, $data);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleAgentRegistration($db, $data) {
    $username = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $full_name = $data['full_name'] ?? '';
    $phone = $data['phone'] ?? '';
    $region = $data['region'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username, email, password, and full name are required']);
        return;
    }

    // Check if username or email already exists
    $check_query = "SELECT id FROM support_agents WHERE username = ? OR email = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$username, $email]);
    
    if ($check_stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Username or email already exists']);
        return;
    }

    // Hash password and create agent
    $password_hash = Auth::hashPassword($password);
    
    $query = "INSERT INTO support_agents (username, email, password_hash, full_name, phone, region) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$username, $email, $password_hash, $full_name, $phone, $region])) {
        $agent_id = $db->lastInsertId();
        $token = Auth::generateToken($agent_id, 'agent', $username);
        
        echo json_encode([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $agent_id,
                'username' => $username,
                'email' => $email,
                'full_name' => $full_name,
                'phone' => $phone,
                'region' => $region,
                'user_type' => 'agent'
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create account']);
    }
}

function handleAgentLogin($db, $data) {
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password are required']);
        return;
    }

    // Find agent by username or email
    $query = "SELECT id, username, email, password_hash, full_name, phone, region, is_active 
              FROM support_agents WHERE username = ? OR email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$username, $username]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agent) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        return;
    }

    if (!$agent['is_active']) {
        http_response_code(401);
        echo json_encode(['error' => 'Account is deactivated']);
        return;
    }

    if (Auth::verifyPassword($password, $agent['password_hash'])) {
        $token = Auth::generateToken($agent['id'], 'agent', $agent['username']);
        
        echo json_encode([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $agent['id'],
                'username' => $agent['username'],
                'email' => $agent['email'],
                'full_name' => $agent['full_name'],
                'phone' => $agent['phone'],
                'region' => $agent['region'],
                'user_type' => 'agent'
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
    }
}
?>