<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth {
    private static $secret_key;

    public static function init() {
        self::$secret_key = $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-this';
    }

    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    public static function generateToken($user_id, $user_type, $username) {
        $payload = [
            'user_id' => $user_id,
            'user_type' => $user_type,
            'username' => $username,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24) // 24 hours
        ];

        return JWT::encode($payload, self::$secret_key, 'HS256');
    }

    public static function verifyToken($token) {
        try {
            $decoded = JWT::decode($token, new Key(self::$secret_key, 'HS256'));
            return $decoded;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getCurrentUser() {
        $headers = getallheaders();
        $token = null;

        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            if (strpos($auth_header, 'Bearer ') === 0) {
                $token = substr($auth_header, 7);
            }
        }

        if (!$token) {
            return false;
        }

        return self::verifyToken($token);
    }

    public static function requireAuth($user_type = null) {
        $user = self::getCurrentUser();
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        if ($user_type && $user->user_type !== $user_type) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        return $user;
    }
}

// Initialize auth
Auth::init();
?>