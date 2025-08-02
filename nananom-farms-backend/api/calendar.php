<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load environment variables
$env_file = dirname(__DIR__) . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

require_once dirname(__DIR__) . '/includes/auth.php';

// Try to connect to database, but don't fail if it's not available
$db = null;
try {
    require_once dirname(__DIR__) . '/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    error_log("Database connection failed in calendar: " . $e->getMessage());
    // Continue without database - will use fallback responses
}

$action = $_GET['action'] ?? '';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        switch ($action) {
            case 'month':
                handleGetMonthCalendar($db);
                break;
            case 'week':
                handleGetWeekCalendar($db);
                break;
            case 'day':
                handleGetDayCalendar($db);
                break;
            case 'availability':
                handleCheckAvailability($db);
                break;
            case 'time-slots':
                handleGetTimeSlots($db);
                break;
            case 'statistics':
                handleGetStatistics($db);
                break;
            default:
                handleGetMonthCalendar($db);
        }
        break;
    case 'POST':
        switch ($action) {
            case 'book-slot':
                handleBookTimeSlot($db);
                break;
            case 'block-time':
                handleBlockTimeSlot($db);
                break;
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
        }
        break;
    case 'PUT':
        switch ($action) {
            case 'reschedule':
                handleReschedule($db);
                break;
            case 'update-availability':
                handleUpdateAvailability($db);
                break;
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
        }
        break;
    case 'DELETE':
        switch ($action) {
            case 'cancel-slot':
                handleCancelSlot($db);
                break;
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleGetMonthCalendar($db) {
    // Check if user is authenticated
    $user = Auth::getCurrentUser();
    if (!$user || ($user->user_type !== 'admin' && $user->user_type !== 'agent')) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }

    if (!$db) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection not available',
            'calendar_data' => []
        ]);
        return;
    }

    try {
        $month = $_GET['month'] ?? date('Y-m');
        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));

        // Get enquiries for the month
        $enquiry_query = "SELECT id, full_name, email, subject, preferred_date, preferred_time, 
                                urgency_level, status, assigned_to 
                         FROM enquiries 
                         WHERE preferred_date BETWEEN ? AND ?
                         ORDER BY preferred_date ASC, preferred_time ASC";
        
        $stmt = $db->prepare($enquiry_query);
        $stmt->execute([$start_date, $end_date]);
        $enquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get bookings for the month
        $booking_query = "SELECT id, full_name, email, service_type, booking_date, booking_time, 
                                status, assigned_to 
                         FROM bookings 
                         WHERE booking_date BETWEEN ? AND ?
                         ORDER BY booking_date ASC, booking_time ASC";
        
        $stmt = $db->prepare($booking_query);
        $stmt->execute([$start_date, $end_date]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format calendar data
        $calendar_data = [];
        $days_in_month = date('t', strtotime($start_date));
        
        // Initialize all days in the month
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = $month . '-' . sprintf('%02d', $day);
            $calendar_data[$date] = [
                'date' => $date,
                'day_of_week' => date('w', strtotime($date)),
                'enquiries' => [],
                'bookings' => [],
                'total_events' => 0,
                'urgency_levels' => ['low' => 0, 'medium' => 0, 'high' => 0, 'urgent' => 0]
            ];
        }
        
        // Add enquiries to calendar
        foreach ($enquiries as $enquiry) {
            $date = $enquiry['preferred_date'];
            if (isset($calendar_data[$date])) {
                $calendar_data[$date]['enquiries'][] = $enquiry;
                $calendar_data[$date]['total_events']++;
                $calendar_data[$date]['urgency_levels'][$enquiry['urgency_level']]++;
            }
        }

        // Add bookings to calendar
        foreach ($bookings as $booking) {
            $date = $booking['booking_date'];
            if (isset($calendar_data[$date])) {
                $calendar_data[$date]['bookings'][] = $booking;
                $calendar_data[$date]['total_events']++;
            }
        }

        echo json_encode([
            'success' => true,
            'month' => $month,
            'calendar_data' => $calendar_data,
            'statistics' => [
                'total_enquiries' => count($enquiries),
                'total_bookings' => count($bookings),
                'busy_days' => count(array_filter($calendar_data, function($day) {
                    return $day['total_events'] > 0;
                }))
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get month calendar error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleGetWeekCalendar($db) {
    // Check if user is authenticated
    $user = Auth::getCurrentUser();
    if (!$user || ($user->user_type !== 'admin' && $user->user_type !== 'agent')) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }

    if (!$db) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection not available',
            'week_data' => []
        ]);
        return;
    }

    try {
        $week_start = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
        $week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));

        // Get enquiries for the week
        $enquiry_query = "SELECT e.*, sa.full_name as assigned_agent_name 
                         FROM enquiries e 
                         LEFT JOIN support_agents sa ON e.assigned_to = sa.id
                         WHERE e.preferred_date BETWEEN ? AND ?
                         ORDER BY e.preferred_date ASC, e.preferred_time ASC";
        
        $stmt = $db->prepare($enquiry_query);
        $stmt->execute([$week_start, $week_end]);
        $enquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get bookings for the week
        $booking_query = "SELECT b.*, sa.full_name as assigned_agent_name 
                         FROM bookings b 
                         LEFT JOIN support_agents sa ON b.assigned_to = sa.id
                         WHERE b.booking_date BETWEEN ? AND ?
                         ORDER BY b.booking_date ASC, b.booking_time ASC";
        
        $stmt = $db->prepare($booking_query);
        $stmt->execute([$week_start, $week_end]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format week data
        $week_data = [];
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime($week_start . " +$i days"));
            $week_data[$date] = [
                'date' => $date,
                'day_name' => date('l', strtotime($date)),
                'enquiries' => [],
                'bookings' => [],
                'time_slots' => generateTimeSlots()
            ];
        }

        // Add enquiries to week data
        foreach ($enquiries as $enquiry) {
            $date = $enquiry['preferred_date'];
            if (isset($week_data[$date])) {
                $week_data[$date]['enquiries'][] = $enquiry;
            }
        }

        // Add bookings to week data
        foreach ($bookings as $booking) {
            $date = $booking['booking_date'];
            if (isset($week_data[$date])) {
                $week_data[$date]['bookings'][] = $booking;
            }
        }

        echo json_encode([
            'success' => true,
            'week_start' => $week_start,
            'week_end' => $week_end,
            'week_data' => $week_data
        ]);
    } catch (Exception $e) {
        error_log("Get week calendar error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleGetDayCalendar($db) {
    // Check if user is authenticated
    $user = Auth::getCurrentUser();
    if (!$user || ($user->user_type !== 'admin' && $user->user_type !== 'agent')) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }

    if (!$db) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection not available',
            'day_data' => []
        ]);
        return;
    }

    try {
        $date = $_GET['date'] ?? date('Y-m-d');

        // Get enquiries for the day
        $enquiry_query = "SELECT e.*, sa.full_name as assigned_agent_name 
                         FROM enquiries e 
                         LEFT JOIN support_agents sa ON e.assigned_to = sa.id
                         WHERE e.preferred_date = ?
                         ORDER BY e.preferred_time ASC, e.urgency_level DESC";
        
        $stmt = $db->prepare($enquiry_query);
        $stmt->execute([$date]);
        $enquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get bookings for the day
        $booking_query = "SELECT b.*, sa.full_name as assigned_agent_name 
                         FROM bookings b 
                         LEFT JOIN support_agents sa ON b.assigned_to = sa.id
                         WHERE b.booking_date = ?
                         ORDER BY b.booking_time ASC";
        
        $stmt = $db->prepare($booking_query);
        $stmt->execute([$date]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Generate time slots with occupancy
        $time_slots = generateTimeSlots();
        
        // Mark occupied time slots
        foreach ($enquiries as $enquiry) {
            if ($enquiry['preferred_time']) {
                $time = substr($enquiry['preferred_time'], 0, 5); // HH:MM format
                if (isset($time_slots[$time])) {
                    $time_slots[$time]['occupied'] = true;
                    $time_slots[$time]['events'][] = ['type' => 'enquiry', 'data' => $enquiry];
                }
            }
        }

        foreach ($bookings as $booking) {
            if ($booking['booking_time']) {
                $time = substr($booking['booking_time'], 0, 5); // HH:MM format
                if (isset($time_slots[$time])) {
                    $time_slots[$time]['occupied'] = true;
                    $time_slots[$time]['events'][] = ['type' => 'booking', 'data' => $booking];
                }
            }
        }

        echo json_encode([
            'success' => true,
            'date' => $date,
            'day_name' => date('l', strtotime($date)),
            'enquiries' => $enquiries,
            'bookings' => $bookings,
            'time_slots' => $time_slots,
            'statistics' => [
                'total_enquiries' => count($enquiries),
                'total_bookings' => count($bookings),
                'occupied_slots' => count(array_filter($time_slots, function($slot) {
                    return $slot['occupied'];
                }))
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get day calendar error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleCheckAvailability($db) {
    $date = $_GET['date'] ?? '';
    $time = $_GET['time'] ?? '';
    $duration = intval($_GET['duration'] ?? 60); // minutes

    if (empty($date)) {
        http_response_code(400);
        echo json_encode(['error' => 'Date is required']);
        return;
    }

    if (!$db) {
        echo json_encode([
            'success' => true,
            'available' => true,
            'message' => 'Database not available, assuming slot is free'
        ]);
        return;
    }

    try {
        // Check for conflicts in enquiries and bookings
        $conflicts = [];
        
        if ($time) {
            // Check specific time slot
            $end_time = date('H:i:s', strtotime($time) + ($duration * 60));
            
            // Check enquiry conflicts
            $enquiry_query = "SELECT COUNT(*) as count FROM enquiries 
                             WHERE preferred_date = ? AND preferred_time BETWEEN ? AND ? 
                             AND status NOT IN ('cancelled', 'completed')";
            $stmt = $db->prepare($enquiry_query);
            $stmt->execute([$date, $time, $end_time]);
            $enquiry_conflicts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Check booking conflicts
            $booking_query = "SELECT COUNT(*) as count FROM bookings 
                             WHERE booking_date = ? AND booking_time BETWEEN ? AND ? 
                             AND status NOT IN ('cancelled', 'completed')";
            $stmt = $db->prepare($booking_query);
            $stmt->execute([$date, $time, $end_time]);
            $booking_conflicts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            $total_conflicts = $enquiry_conflicts + $booking_conflicts;
        } else {
            // Check entire day
            $enquiry_query = "SELECT COUNT(*) as count FROM enquiries 
                             WHERE preferred_date = ? AND status NOT IN ('cancelled', 'completed')";
            $stmt = $db->prepare($enquiry_query);
            $stmt->execute([$date]);
            $enquiry_conflicts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            $booking_query = "SELECT COUNT(*) as count FROM bookings 
                             WHERE booking_date = ? AND status NOT IN ('cancelled', 'completed')";
            $stmt = $db->prepare($booking_query);
            $stmt->execute([$date]);
            $booking_conflicts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            $total_conflicts = $enquiry_conflicts + $booking_conflicts;
        }

        $available = $total_conflicts === 0;

        echo json_encode([
            'success' => true,
            'available' => $available,
            'conflicts' => [
                'enquiries' => $enquiry_conflicts,
                'bookings' => $booking_conflicts,
                'total' => $total_conflicts
            ],
            'date' => $date,
            'time' => $time,
            'duration' => $duration
        ]);
    } catch (Exception $e) {
        error_log("Check availability error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function handleGetTimeSlots($db) {
    $date = $_GET['date'] ?? date('Y-m-d');
    $service_type = $_GET['service_type'] ?? 'general';

    // Generate available time slots
    $time_slots = generateTimeSlots();
    
    if ($db) {
        try {
            // Mark occupied slots
            $occupied_query = "
                SELECT TIME_FORMAT(preferred_time, '%H:%i') as time_slot FROM enquiries 
                WHERE preferred_date = ? AND status NOT IN ('cancelled', 'completed')
                UNION
                SELECT TIME_FORMAT(booking_time, '%H:%i') as time_slot FROM bookings 
                WHERE booking_date = ? AND status NOT IN ('cancelled', 'completed')
            ";
            
            $stmt = $db->prepare($occupied_query);
            $stmt->execute([$date, $date]);
            $occupied_slots = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($occupied_slots as $occupied_time) {
                if (isset($time_slots[$occupied_time])) {
                    $time_slots[$occupied_time]['available'] = false;
                    $time_slots[$occupied_time]['status'] = 'occupied';
                }
            }
        } catch (Exception $e) {
            error_log("Get time slots error: " . $e->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'date' => $date,
        'service_type' => $service_type,
        'time_slots' => array_values($time_slots)
    ]);
}

function handleGetStatistics($db) {
    // Check if user is authenticated
    $user = Auth::getCurrentUser();
    if (!$user || ($user->user_type !== 'admin' && $user->user_type !== 'agent')) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }

    if (!$db) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection not available',
            'statistics' => []
        ]);
        return;
    }

    try {
        $period = $_GET['period'] ?? 'month'; // week, month, year
        $date = $_GET['date'] ?? date('Y-m-d');

        switch ($period) {
            case 'week':
                $start_date = date('Y-m-d', strtotime('monday this week', strtotime($date)));
                $end_date = date('Y-m-d', strtotime('sunday this week', strtotime($date)));
                break;
            case 'year':
                $start_date = date('Y-01-01', strtotime($date));
                $end_date = date('Y-12-31', strtotime($date));
                break;
            default: // month
                $start_date = date('Y-m-01', strtotime($date));
                $end_date = date('Y-m-t', strtotime($date));
        }

        // Get enquiry statistics
        $enquiry_stats_query = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN urgency_level = 'urgent' THEN 1 ELSE 0 END) as urgent,
                SUM(CASE WHEN urgency_level = 'high' THEN 1 ELSE 0 END) as high_priority
            FROM enquiries 
            WHERE preferred_date BETWEEN ? AND ?
        ";
        
        $stmt = $db->prepare($enquiry_stats_query);
        $stmt->execute([$start_date, $end_date]);
        $enquiry_stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get booking statistics
        $booking_stats_query = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            FROM bookings 
            WHERE booking_date BETWEEN ? AND ?
        ";
        
        $stmt = $db->prepare($booking_stats_query);
        $stmt->execute([$start_date, $end_date]);
        $booking_stats = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'period' => $period,
            'date_range' => [
                'start' => $start_date,
                'end' => $end_date
            ],
            'statistics' => [
                'enquiries' => $enquiry_stats,
                'bookings' => $booking_stats,
                'total_events' => $enquiry_stats['total'] + $booking_stats['total']
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get statistics error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
}

function generateTimeSlots() {
    $slots = [];
    $start_hour = 9; // 9 AM
    $end_hour = 17;  // 5 PM
    $interval = 30;  // 30 minutes

    for ($hour = $start_hour; $hour < $end_hour; $hour++) {
        for ($minute = 0; $minute < 60; $minute += $interval) {
            $time = sprintf('%02d:%02d', $hour, $minute);
            $slots[$time] = [
                'time' => $time,
                'available' => true,
                'occupied' => false,
                'status' => 'available',
                'events' => []
            ];
        }
    }

    return $slots;
}

// Additional handler functions for POST, PUT, DELETE operations would go here
// (handleBookTimeSlot, handleBlockTimeSlot, handleReschedule, etc.)

function handleBookTimeSlot($db) {
    echo json_encode(['message' => 'Booking functionality to be implemented']);
}

function handleBlockTimeSlot($db) {
    echo json_encode(['message' => 'Block time functionality to be implemented']);
}

function handleReschedule($db) {
    echo json_encode(['message' => 'Reschedule functionality to be implemented']);
}

function handleUpdateAvailability($db) {
    echo json_encode(['message' => 'Update availability functionality to be implemented']);
}

function handleCancelSlot($db) {
    echo json_encode(['message' => 'Cancel slot functionality to be implemented']);
}
?>