<?php
/**
 * Create Sample Data for Admin Dashboard Testing
 * Run this script to create sample enquiries and bookings
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

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo "✗ Database connection failed\n";
        exit(1);
    }
    
    echo "✓ Database connection successful\n";
    
    // Check if sample data already exists
    $check_enquiries = $db->query("SELECT COUNT(*) FROM enquiries")->fetchColumn();
    $check_bookings = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    
    if ($check_enquiries > 0 || $check_bookings > 0) {
        echo "✓ Sample data already exists\n";
        echo "Enquiries: $check_enquiries\n";
        echo "Bookings: $check_bookings\n";
        exit(0);
    }
    
    // Create sample enquiries
    $sample_enquiries = [
        [
            'full_name' => 'John Smith',
            'email' => 'john.smith@email.com',
            'company' => 'Smith Farms',
            'subject' => 'Organic Certification Inquiry',
            'message' => 'I am interested in getting organic certification for my farm. Can you provide information about the process and requirements?',
            'preferred_date' => '2024-08-15',
            'preferred_time' => '10:00:00',
            'urgency_level' => 'medium',
            'status' => 'pending'
        ],
        [
            'full_name' => 'Sarah Johnson',
            'email' => 'sarah.j@greenfarms.com',
            'company' => 'Green Valley Farms',
            'subject' => 'Soil Testing Services',
            'message' => 'We need comprehensive soil testing for our new plot. What services do you offer and what are the costs?',
            'preferred_date' => '2024-08-20',
            'preferred_time' => '14:00:00',
            'urgency_level' => 'high',
            'status' => 'in_progress'
        ],
        [
            'full_name' => 'Michael Brown',
            'email' => 'mike.brown@agritech.com',
            'company' => 'AgriTech Solutions',
            'subject' => 'Irrigation System Consultation',
            'message' => 'Looking for expert consultation on designing an efficient irrigation system for our 50-acre farm.',
            'preferred_date' => '2024-08-25',
            'preferred_time' => '09:00:00',
            'urgency_level' => 'medium',
            'status' => 'resolved'
        ],
        [
            'full_name' => 'Emily Davis',
            'email' => 'emily.davis@organic.com',
            'company' => 'Organic Harvest Co.',
            'subject' => 'Crop Planning Workshop',
            'message' => 'Interested in attending your crop planning workshop. When is the next session and how do we register?',
            'preferred_date' => '2024-09-01',
            'preferred_time' => '11:00:00',
            'urgency_level' => 'low',
            'status' => 'pending'
        ],
        [
            'full_name' => 'David Wilson',
            'email' => 'david.wilson@farmers.com',
            'company' => 'Wilson Family Farm',
            'subject' => 'Farm Tour Request',
            'message' => 'Would like to bring our farming group for a tour of your facilities. What are the available dates?',
            'preferred_date' => '2024-09-05',
            'preferred_time' => '15:00:00',
            'urgency_level' => 'medium',
            'status' => 'pending'
        ]
    ];
    
    // Insert sample enquiries
    $enquiry_query = "INSERT INTO enquiries (full_name, email, company, subject, message, preferred_date, preferred_time, urgency_level, status, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $enquiry_stmt = $db->prepare($enquiry_query);
    
    foreach ($sample_enquiries as $enquiry) {
        $enquiry_stmt->execute([
            $enquiry['full_name'],
            $enquiry['email'],
            $enquiry['company'],
            $enquiry['subject'],
            $enquiry['message'],
            $enquiry['preferred_date'],
            $enquiry['preferred_time'],
            $enquiry['urgency_level'],
            $enquiry['status'],
            date('Y-m-d H:i:s')
        ]);
    }
    
    echo "✓ Sample enquiries created: " . count($sample_enquiries) . "\n";
    
    // Create sample bookings
    $sample_bookings = [
        [
            'full_name' => 'Alice Cooper',
            'email' => 'alice.cooper@farm.com',
            'contact' => '+2348012345678',
            'company' => 'Cooper Farms',
            'service_type' => 'Farm Consultation',
            'booking_date' => '2024-08-18',
            'booking_time' => '10:00:00',
            'additional_notes' => 'Need consultation on organic farming practices',
            'status' => 'confirmed'
        ],
        [
            'full_name' => 'Robert Taylor',
            'email' => 'robert.t@agri.com',
            'contact' => '+2348023456789',
            'company' => 'Taylor Agriculture',
            'service_type' => 'Soil Testing',
            'booking_date' => '2024-08-22',
            'booking_time' => '14:00:00',
            'additional_notes' => 'Comprehensive soil analysis required',
            'status' => 'pending'
        ],
        [
            'full_name' => 'Lisa Anderson',
            'email' => 'lisa.anderson@organic.com',
            'contact' => '+2348034567890',
            'company' => 'Anderson Organic',
            'service_type' => 'Crop Planning Workshop',
            'booking_date' => '2024-08-30',
            'booking_time' => '09:00:00',
            'additional_notes' => 'Group of 5 farmers attending',
            'status' => 'confirmed'
        ],
        [
            'full_name' => 'James Martinez',
            'email' => 'james.m@irrigation.com',
            'contact' => '+2348045678901',
            'company' => 'Martinez Irrigation',
            'service_type' => 'Irrigation System Design',
            'booking_date' => '2024-09-02',
            'booking_time' => '11:00:00',
            'additional_notes' => 'Large-scale irrigation system design needed',
            'status' => 'pending'
        ],
        [
            'full_name' => 'Maria Garcia',
            'email' => 'maria.garcia@farm.com',
            'contact' => '+2348056789012',
            'company' => 'Garcia Family Farm',
            'service_type' => 'Farm Tour',
            'booking_date' => '2024-09-08',
            'booking_time' => '15:00:00',
            'additional_notes' => 'Educational tour for students',
            'status' => 'confirmed'
        ]
    ];
    
    // Insert sample bookings
    $booking_query = "INSERT INTO bookings (full_name, email, contact, company, service_type, booking_date, booking_time, additional_notes, status, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $booking_stmt = $db->prepare($booking_query);
    
    foreach ($sample_bookings as $booking) {
        $booking_stmt->execute([
            $booking['full_name'],
            $booking['email'],
            $booking['contact'],
            $booking['company'],
            $booking['service_type'],
            $booking['booking_date'],
            $booking['booking_time'],
            $booking['additional_notes'],
            $booking['status'],
            date('Y-m-d H:i:s')
        ]);
    }
    
    echo "✓ Sample bookings created: " . count($sample_bookings) . "\n";
    echo "\n🎉 Sample data created successfully!\n";
    echo "You can now test the admin dashboard with real data.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>