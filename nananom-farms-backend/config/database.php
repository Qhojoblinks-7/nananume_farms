<?php
class Database {
    private $connection;
    
    public function __construct() {
        try {
            // Use SQLite for development (easier setup)
            $db_path = __DIR__ . '/../database/nananom_farms.db';
            $this->connection = new PDO("sqlite:$db_path");
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create tables if they don't exist
            $this->createTables();
            
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    private function createTables() {
        $sql = "
        -- Admin table (single admin with embedded credentials)
        CREATE TABLE IF NOT EXISTS admin (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            full_name TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Support agents table
        CREATE TABLE IF NOT EXISTS support_agents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            full_name TEXT NOT NULL,
            phone TEXT,
            region TEXT,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Enquiries table (for user enquiries)
        CREATE TABLE IF NOT EXISTS enquiries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email TEXT NOT NULL,
            contact TEXT NOT NULL,
            company TEXT,
            subject TEXT NOT NULL,
            message TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            assigned_to INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (assigned_to) REFERENCES support_agents(id)
        );

        -- Bookings table (for service bookings)
        CREATE TABLE IF NOT EXISTS bookings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email TEXT NOT NULL,
            contact TEXT NOT NULL,
            company TEXT,
            service_type TEXT NOT NULL,
            booking_date DATE NOT NULL,
            booking_time TIME,
            additional_notes TEXT,
            status TEXT DEFAULT 'pending',
            assigned_to INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (assigned_to) REFERENCES support_agents(id)
        );
        ";
        
        $this->connection->exec($sql);
        
        // Insert default admin if not exists
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM admin WHERE username = 'admin'");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $stmt = $this->connection->prepare("
                INSERT INTO admin (username, email, password_hash, full_name) 
                VALUES ('admin', 'admin@nananomfarms.com', 'temporary_hash', 'System Administrator')
            ");
            $stmt->execute();
        }
    }
}
?>