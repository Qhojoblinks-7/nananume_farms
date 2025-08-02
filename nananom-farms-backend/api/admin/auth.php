<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load environment variables
$env_file = __DIR__ . '/../../.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

require_once dirname(__DIR__, 2) . '/includes/auth.php';

// Try to connect to database, but don't fail if it's not available
$db = null;
try {
    require_once dirname(__DIR__, 2) . '/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    error_log("Database connection failed, using fallback auth: " . $e->getMessage());
    // Continue without database - will use environment-based auth
}

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

    try {
        // Get admin credentials from environment variables
        $admin_username = $_ENV['ADMIN_USERNAME'] ?? 'admin';
        $admin_password = $_ENV['ADMIN_PASSWORD'] ?? 'temporary_hash';

        // Check if credentials match environment variables
        if ($username === $admin_username && $password === $admin_password) {
            
            // Try to get admin record from database if available
            $admin = null;
            if ($db) {
                try {
                    $query = "SELECT id, username, email, full_name, password_hash FROM admin WHERE username = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$username]);
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    error_log("Database query failed: " . $e->getMessage());
                    // Continue with fallback data
                }
            }
            
            // Use database data if available, otherwise use fallback
            if ($admin) {
                $user_data = [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'email' => $admin['email'],
                    'full_name' => $admin['full_name'],
                    'user_type' => 'admin'
                ];
            } else {
                // Fallback user data when database is not available
                $user_data = [
                    'id' => 1,
                    'username' => $admin_username,
                    'email' => 'admin@nananomfarms.com',
                    'full_name' => 'System Administrator',
                    'user_type' => 'admin'
                ];
            }
            
            // Generate JWT token
            $token = Auth::generateToken($user_data['id'], 'admin', $user_data['username']);
            
            echo json_encode([
                'success' => true,
                'token' => $token,
                'user' => $user_data
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    } catch (Exception $e) {
        error_log("Admin login error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error during login', 'details' => $e->getMessage()]);
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

    try {
        // Verify current password
        $admin_username = $_ENV['ADMIN_USERNAME'] ?? 'admin';
        $admin_password = $_ENV['ADMIN_PASSWORD'] ?? 'admin123';

        if ($current_password !== $admin_password) {
            http_response_code(401);
            echo json_encode(['error' => 'Current password is incorrect']);
            return;
        }

        if ($db) {
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
        } else {
            echo json_encode(['success' => true, 'message' => 'Password update requires database connection']);
        }
    } catch (Exception $e) {
        error_log("Admin password update error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error during password update']);
    }
}
?>