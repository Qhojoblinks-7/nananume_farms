<?php
/**
 * Create Test Support Agent
 * Run this script to create a test agent for login testing
 */

// Load environment variables
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

require_once 'config/database.php';
require_once 'includes/auth.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo "✗ Database connection failed\n";
        exit(1);
    }
    
    echo "✓ Database connection successful\n";
    
    // Check if test agent already exists
    $check_query = "SELECT id FROM support_agents WHERE email = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute(['test@nananomfarms.com']);
    
    if ($check_stmt->fetch()) {
        echo "✓ Test agent already exists\n";
        echo "Email: test@nananomfarms.com\n";
        echo "Password: test123\n";
        exit(0);
    }
    
    // Create test agent
    $email = 'test@nananomfarms.com';
    $password = 'test123';
    $full_name = 'Test Agent';
    
    $password_hash = Auth::hashPassword($password);
    
    $query = "INSERT INTO support_agents (email, password_hash, full_name, is_active) 
              VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$email, $password_hash, $full_name, true])) {
        $agent_id = $db->lastInsertId();
        echo "✓ Test agent created successfully!\n";
        echo "Agent ID: $agent_id\n";
        echo "Email: $email\n";
        echo "Password: $password\n";
        echo "Full Name: $full_name\n";
        echo "\nYou can now test agent login with these credentials.\n";
    } else {
        echo "✗ Failed to create test agent\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>