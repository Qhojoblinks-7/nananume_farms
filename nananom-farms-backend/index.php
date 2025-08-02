<?php
// Load environment variables
$env_file = __DIR__ . './.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Set default timezone
date_default_timezone_set('UTC');

// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit(0);
}

// Simple routing
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

// Remove base path if needed
$base_path = 'nananom-farms-backend';
if (strpos($path, $base_path) === 0) {
    $path = substr($path, strlen($base_path));
}
$path = trim($path, '/');

// Route to appropriate API endpoint
switch ($path) {
    case 'api/admin/auth':
        require_once 'api/admin/auth.php';
        break;
    case 'api/agent/auth':
        require_once 'api/agent/auth.php';
        break;
    case 'api/enquiries':
        require_once 'api/enquiries.php';
        break;
    case 'api/bookings':
        require_once 'api/bookings.php';
        break;
    case 'api/agents':
        require_once 'api/agents.php';
        break;
    case 'api/calendar':
        require_once 'api/calendar.php';
        break;
    case 'api/services':
        require_once 'api/services.php';
        break;
    case 'api/products':
        require_once 'api/products.php';
        break;
    case 'api/cart':
        require_once 'api/cart.php';
        break;
    case 'api/orders':
        require_once 'api/orders.php';
        break;
    case '':
    case 'index.php':
        // API documentation or health check
        header('Content-Type: application/json');
        echo json_encode([
            'message' => 'Nananom Farms API',
            'version' => '1.0.0',
            'endpoints' => [
                'admin_auth' => '/api/admin/auth',
                'agent_auth' => '/api/agent/auth',
                'enquiries' => '/api/enquiries',
                'bookings' => '/api/bookings',
                'agents' => '/api/agents',
                'calendar' => '/api/calendar',
                'services' => '/api/services',
                'products' => '/api/products',
                'cart' => '/api/cart',
                'orders' => '/api/orders'
            ]
        ]);
        break;
    default:
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}
?>