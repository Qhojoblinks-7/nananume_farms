<?php

class Database {
    private $connection;

    public function __construct() {
        // Use SQLite for easier testing
        $db_path = __DIR__ . '/../database/nananom_farms.db';
        
        try {
            $dsn = "sqlite:$db_path";
            $this->connection = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
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
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS support_agents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS enquiries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            company VARCHAR(100),
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            preferred_date DATE,
            preferred_time TIME,
            urgency_level VARCHAR(20) DEFAULT 'medium',
            follow_up_date DATE,
            follow_up_notes TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            assigned_to INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (assigned_to) REFERENCES support_agents(id)
        );

        CREATE TABLE IF NOT EXISTS bookings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            contact VARCHAR(20) NOT NULL,
            company VARCHAR(100),
            service_type VARCHAR(100) NOT NULL,
            booking_date DATE NOT NULL,
            booking_time TIME,
            additional_notes TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            assigned_to INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (assigned_to) REFERENCES support_agents(id)
        );

        CREATE TABLE IF NOT EXISTS services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            category VARCHAR(100),
            price REAL NOT NULL,
            duration_minutes INTEGER DEFAULT 60,
            is_active BOOLEAN DEFAULT 1,
            requires_booking BOOLEAN DEFAULT 1,
            max_participants INTEGER DEFAULT 1,
            image_url VARCHAR(500),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            category VARCHAR(100),
            price REAL NOT NULL,
            stock_quantity INTEGER DEFAULT 0,
            sku VARCHAR(100) UNIQUE,
            weight_kg REAL,
            dimensions_cm VARCHAR(50),
            is_active BOOLEAN DEFAULT 1,
            is_digital BOOLEAN DEFAULT 0,
            image_url VARCHAR(500),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS customers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            phone VARCHAR(20),
            address TEXT,
            city VARCHAR(100),
            state VARCHAR(100),
            postal_code VARCHAR(20),
            country VARCHAR(100) DEFAULT 'Nigeria',
            date_of_birth DATE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_id INTEGER NOT NULL,
            order_number VARCHAR(50) UNIQUE NOT NULL,
            order_type VARCHAR(20) DEFAULT 'product',
            subtotal REAL NOT NULL,
            tax_amount REAL DEFAULT 0,
            shipping_amount REAL DEFAULT 0,
            total_amount REAL NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            payment_status VARCHAR(20) DEFAULT 'pending',
            payment_method VARCHAR(50),
            shipping_address TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id)
        );

        CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            item_type VARCHAR(20) NOT NULL,
            item_id INTEGER NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            quantity INTEGER NOT NULL DEFAULT 1,
            unit_price REAL NOT NULL,
            total_price REAL NOT NULL,
            booking_date DATE,
            booking_time TIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id)
        );

        CREATE TABLE IF NOT EXISTS shopping_cart (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id VARCHAR(255) NOT NULL,
            customer_email VARCHAR(100),
            item_type VARCHAR(20) NOT NULL,
            item_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL DEFAULT 1,
            booking_date DATE,
            booking_time TIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
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

        // Add sample services if not present
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM services");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $sample_services = [
                ['Farm Consultation', 'Professional consultation on organic farming practices', 'Consulting', 150.00, 120, 1, 1, 3],
                ['Soil Testing', 'Comprehensive soil analysis and testing', 'Testing', 75.00, 60, 1, 1, 1],
                ['Crop Planning Workshop', 'Learn seasonal crop planning and rotation', 'Workshop', 200.00, 180, 1, 1, 10],
                ['Irrigation System Design', 'Custom irrigation system planning and design', 'Design', 300.00, 240, 1, 1, 1],
                ['Organic Certification Guidance', 'Help with organic certification process', 'Consulting', 250.00, 150, 1, 1, 2],
                ['Farm Tour', 'Guided tour of our organic farm facilities', 'Tour', 25.00, 90, 1, 1, 20]
            ];

            $insert = $this->connection->prepare("
                INSERT INTO services (name, description, category, price, duration_minutes, is_active, requires_booking, max_participants)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($sample_services as $service) {
                $insert->execute($service);
            }
        }

        // Add sample products if not present
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM products");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $sample_products = [
                ['Organic Tomatoes', 'Fresh organic tomatoes grown on our farm', 'Vegetables', 5.99, 50, 'ORG-TOM-001', 1.0, '10x8x6', 1, 0],
                ['Organic Carrots', 'Sweet organic carrots, perfect for cooking', 'Vegetables', 3.99, 30, 'ORG-CAR-001', 1.5, '15x5x5', 1, 0],
                ['Farm Fresh Eggs', 'Free-range organic eggs from happy chickens', 'Dairy & Eggs', 8.99, 100, 'ORG-EGG-001', 0.8, '12x8x8', 1, 0],
                ['Organic Honey', 'Pure raw honey from our beehives', 'Pantry', 15.99, 25, 'ORG-HON-001', 0.5, '8x8x12', 1, 0],
                ['Compost Fertilizer', 'Organic compost for healthy soil', 'Garden Supplies', 25.99, 20, 'ORG-COM-001', 10.0, '40x30x20', 1, 0],
                ['Organic Seeds Pack', 'Heirloom vegetable seeds collection', 'Seeds', 19.99, 200, 'ORG-SED-001', 0.2, '15x10x2', 1, 0],
                ['Farm Care Guide (PDF)', 'Complete digital guide to organic farming', 'Digital', 29.99, 1000, 'DIG-GUI-001', 0.0, '0x0x0', 1, 1],
                ['Organic Pesticide Spray', 'Natural pest control solution', 'Garden Supplies', 18.99, 15, 'ORG-PES-001', 1.2, '20x8x8', 1, 0]
            ];

            $insert = $this->connection->prepare("
                INSERT INTO products (name, description, category, price, stock_quantity, sku, weight_kg, dimensions_cm, is_active, is_digital)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($sample_products as $product) {
                $insert->execute($product);
            }
        }
    }
}

?>