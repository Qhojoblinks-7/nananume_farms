<?php

class Database {
    private $connection;

    public function __construct() {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $db   = $_ENV['DB_NAME'] ?? 'nananom_farms';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';

        try {
            // Use TCP connection with explicit port to avoid socket permission issues
            $dsn = "mysql:host=$host;port=3306;dbname=$db;charset=utf8mb4";
            $this->connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Create tables
            $this->createTables();

        } catch (PDOException $e) {
            // Log the detailed error for debugging
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->connection;
    }

    private function createTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS admin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS support_agents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS enquiries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            company VARCHAR(100),
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            preferred_date DATE,
            preferred_time TIME,
            urgency_level ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            follow_up_date DATE,
            follow_up_notes TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            assigned_to INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (assigned_to) REFERENCES support_agents(id)
        );

        CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            contact VARCHAR(20) NOT NULL,
            company VARCHAR(100),
            service_type VARCHAR(100) NOT NULL,
            booking_date DATE NOT NULL,
            booking_time TIME,
            additional_notes TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            assigned_to INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (assigned_to) REFERENCES support_agents(id)
        );
        ";

        $this->connection->exec($sql);

        // Add default admin if not present
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM admin WHERE username = 'admin'");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $defaultPassword = password_hash('admin123', PASSWORD_BCRYPT);
            $insert = $this->connection->prepare("
                INSERT INTO admin (username, email, password_hash, full_name)
                VALUES ('admin', 'admin@nananomfarms.com', :password, 'System Administrator')
            ");
            $insert->execute([':password' => $defaultPassword]);
        }
    }
}

?>