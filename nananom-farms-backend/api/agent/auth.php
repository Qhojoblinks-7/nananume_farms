<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

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
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $full_name = $data['full_name'] ?? '';

    if (empty($email) || empty($password) || empty($full_name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email, password, and full name are required']);
        return;
    }

    $check_query = "SELECT id FROM support_agents WHERE email = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$email]);

    if ($check_stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Email already exists']);
        return;
    }

    $password_hash = Auth::hashPassword($password);

    $query = "INSERT INTO support_agents (email, password_hash, full_name) 
              VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);

    if ($stmt->execute([$email, $password_hash, $full_name])) {
        $agent_id = $db->lastInsertId();
        $token = Auth::generateToken($agent_id, 'agent', $email);

        echo json_encode([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $agent_id,
                'email' => $email,
                'full_name' => $full_name,
                'user_type' => 'agent'
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create account']);
    }
}

function handleAgentLogin($db, $data) {
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email and password are required']);
        return;
    }

    $query = "SELECT id, email, password_hash, full_name, is_active 
              FROM support_agents WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
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
        $token = Auth::generateToken($agent['id'], 'agent', $agent['email']);

        echo json_encode([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $agent['id'],
                'email' => $agent['email'],
                'full_name' => $agent['full_name'],
                'user_type' => 'agent'
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
    }
}
?>
