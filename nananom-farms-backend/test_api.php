<?php
/**
 * Simple API Test Script
 * Run this to test basic API functionality
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

// Test database connection
echo "Testing Database Connection...\n";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "✓ Database connection successful\n";
        
        // Test if tables exist
        $tables = ['admin', 'support_agents', 'enquiries', 'bookings'];
        foreach ($tables as $table) {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✓ Table '$table' exists\n";
            } else {
                echo "✗ Table '$table' missing\n";
            }
        }
    } else {
        echo "✗ Database connection failed\n";
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

echo "\nTesting Environment Variables...\n";
$required_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'ADMIN_USERNAME', 'ADMIN_PASSWORD', 'JWT_SECRET'];
foreach ($required_vars as $var) {
    if (isset($_ENV[$var]) && !empty($_ENV[$var])) {
        echo "✓ $var is set\n";
    } else {
        echo "✗ $var is missing or empty\n";
    }
}

echo "\nTesting JWT Library...\n";
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    echo "✓ Composer autoloader found\n";
    
    if (class_exists('Firebase\JWT\JWT')) {
        echo "✓ JWT library loaded\n";
    } else {
        echo "✗ JWT library not found\n";
    }
} else {
    echo "✗ Composer dependencies not installed. Run 'composer install'\n";
}

echo "\nAPI Test Summary:\n";
echo "1. Copy .env.example to .env and configure your settings\n";
echo "2. Run 'composer install' to install dependencies\n";
echo "3. Import database/schema.sql to create tables\n";
echo "4. Test the API endpoints using the documentation in README.md\n";

echo "\nDefault Admin Credentials:\n";
echo "Username: " . ($_ENV['ADMIN_USERNAME'] ?? 'admin') . "\n";
echo "Password: " . ($_ENV['ADMIN_PASSWORD'] ?? 'admin123') . "\n";
echo "\n⚠️  IMPORTANT: Change these credentials in production!\n";
?>