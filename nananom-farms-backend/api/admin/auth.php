<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'login':
            handleAdminLogin($db, $data);
            break;
        case 'update_password':
            handleAdminPasswordUpdate($db, $data);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleAdminLogin($db, $data) {
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password are required']);
        return;
    }

    // Get admin credentials from environment variables
    $admin_username = $_ENV['ADMIN_USERNAME'] ?? 'admin';
    $admin_password = $_ENV['ADMIN_PASSWORD'] ?? 'admin123';

    if ($username === $admin_username && $password === $admin_password) {
        // Get admin details from database
        $query = "SELECT id, username, email, full_name FROM admin WHERE username = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            $token = Auth::generateToken($admin['id'], 'admin', $admin['username']);
            echo json_encode([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'email' => $admin['email'],
                    'full_name' => $admin['full_name'],
                    'user_type' => 'admin'
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Admin record not found']);
        }
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
    }
}

function handleAdminPasswordUpdate($db, $data) {
    $user = Auth::requireAuth('admin');
    
    $current_password = $data['current_password'] ?? '';
    $new_password = $data['new_password'] ?? '';

    if (empty($current_password) || empty($new_password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Current and new password are required']);
        return;
    }

    // Verify current password
    $admin_username = $_ENV['ADMIN_USERNAME'] ?? 'admin';
    $admin_password = $_ENV['ADMIN_PASSWORD'] ?? 'admin123';

    if ($current_password !== $admin_password) {
        http_response_code(401);
        echo json_encode(['error' => 'Current password is incorrect']);
        return;
    }

    // Update admin password in database
    $password_hash = Auth::hashPassword($new_password);
    $query = "UPDATE admin SET password_hash = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$password_hash, $user->user_id])) {
        echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update password']);
    }
}
?>