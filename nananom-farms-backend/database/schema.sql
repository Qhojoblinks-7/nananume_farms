-- Nananom Farms Database Schema
-- Create database
CREATE DATABASE IF NOT EXISTS nananom_farms;
USE nananom_farms;

-- Admin table (single admin with embedded credentials)
CREATE TABLE admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Support agents table
CREATE TABLE support_agents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Enquiries table (for user enquiries)
CREATE TABLE enquiries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    company VARCHAR(100),
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    preferred_date DATE,
    preferred_time TIME,
    status ENUM('pending', 'in_progress', 'resolved', 'closed') DEFAULT 'pending',
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES support_agents(id) ON DELETE SET NULL
);

-- Bookings table (for service bookings)
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    company VARCHAR(100),
    service_type VARCHAR(100) NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME,
    additional_notes TEXT,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES support_agents(id) ON DELETE SET NULL
);

-- Insert default admin (password will be set via environment variables)
INSERT INTO admin (username, email, password_hash, full_name) 
VALUES ('admin', 'admin@nananomfarms.com', 'temporary_hash', 'System Administrator');

-- Create indexes for better performance
CREATE INDEX idx_enquiries_status ON enquiries(status);
CREATE INDEX idx_enquiries_assigned_to ON enquiries(assigned_to);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_bookings_assigned_to ON bookings(assigned_to);
CREATE INDEX idx_support_agents_email ON support_agents(email);