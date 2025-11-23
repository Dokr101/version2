<?php
require_once 'config.php';

header('Content-Type: application/json');

// Handle AJAX requests
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'confirm_payment':
            confirmPayment();
            break;
        case 'check_availability':
            checkAvailability();
            break;
        case 'get_room_details':
            getRoomDetails();
            break;
        case 'calculate_price':
            calculatePrice();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function confirmPayment() {
    global $pdo;
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        return;
    }
    
    $booking_id = $_POST['booking_id'] ?? null;
    
    if (!$booking_id) {
        echo json_encode(['success' => false, 'message' => 'No booking ID provided']);
        return;
    }
    
    try {
        // Verify that the booking belongs to the user (unless admin/staff)
        if (!isAdmin() && !isStaff()) {
            $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = ? AND user_id = ?");
            $stmt->execute([$booking_id, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = ?");
            $stmt->execute([$booking_id]);
        }
        
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            echo json_encode(['success' => false, 'message' => 'Booking not found']);
            return;
        }
        
        // Update booking payment status to paid
        $stmt = $pdo->prepare("UPDATE bookings SET payment_status = 'paid' WHERE booking_id = ?");
        $success = $stmt->execute([$booking_id]);
        
        if ($success) {
            // Insert payment record
            $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, payment_method, status) VALUES (?, ?, 'online', 'completed')");
            $stmt->execute([$booking_id, $booking['total_price']]);
            
            echo json_encode(['success' => true, 'message' => 'Payment confirmed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update booking']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function checkAvailability() {
    global $pdo;
    
    $checkin = $_POST['checkin'] ?? null;
    $checkout = $_POST['checkout'] ?? null;
    $room_type = $_POST['room_type'] ?? null;
    
    if (!$checkin || !$checkout) {
        echo json_encode(['available' => false, 'message' => 'Please select dates']);
        return;
    }
    
    try {
        // Find available rooms of the specified type
        $query = "
            SELECT r.room_id, r.type, r.price, r.description, r.amenities
            FROM rooms r 
            WHERE r.type = ? 
            AND r.status = 'available'
            AND r.room_id NOT IN (
                SELECT b.room_id 
                FROM bookings b 
                WHERE b.status IN ('pending', 'confirmed', 'checked_in')
                AND (
                    (b.checkin <= ? AND b.checkout >= ?) OR 
                    (b.checkin <= ? AND b.checkout >= ?) OR
                    (b.checkin >= ? AND b.checkout <= ?)
                )
            )
            LIMIT 1
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $room_type, 
            $checkin, $checkin, 
            $checkout, $checkout,
            $checkin, $checkout
        ]);
        
        $available_room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($available_room) {
            echo json_encode([
                'available' => true, 
                'room_id' => $available_room['room_id'],
                'room_type' => $available_room['type'],
                'price' => $available_room['price'],
                'message' => 'Room is available!'
            ]);
        } else {
            echo json_encode([
                'available' => false, 
                'message' => 'No available rooms for the selected dates'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode(['available' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getRoomDetails() {
    global $pdo;
    
    $room_id = $_POST['room_id'] ?? null;
    
    if (!$room_id) {
        echo json_encode(['success' => false, 'message' => 'No room ID provided']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = ?");
        $stmt->execute([$room_id]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($room) {
            echo json_encode([
                'success' => true,
                'room' => $room
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Room not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function calculatePrice() {
    global $pdo;
    
    $room_id = $_POST['room_id'] ?? null;
    $checkin = $_POST['checkin'] ?? null;
    $checkout = $_POST['checkout'] ?? null;
    
    if (!$room_id || !$checkin || !$checkout) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT price FROM rooms WHERE room_id = ?");
        $stmt->execute([$room_id]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$room) {
            echo json_encode(['success' => false, 'message' => 'Room not found']);
            return;
        }
        
        $nights = (strtotime($checkout) - strtotime($checkin)) / (60 * 60 * 24);
        $total_price = $room['price'] * $nights;
        
        echo json_encode([
            'success' => true,
            'nights' => $nights,
            'price_per_night' => $room['price'],
            'total_price' => $total_price
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Additional helper functions
function getAvailableRooms($checkin = null, $checkout = null) {
    global $pdo;
    
    if ($checkin && $checkout) {
        $query = "
            SELECT r.* 
            FROM rooms r
            WHERE r.status = 'available'
            AND r.room_id NOT IN (
                SELECT b.room_id 
                FROM bookings b 
                WHERE b.status IN ('pending', 'confirmed', 'checked_in')
                AND (
                    (b.checkin <= ? AND b.checkout >= ?) OR 
                    (b.checkin <= ? AND b.checkout >= ?) OR
                    (b.checkin >= ? AND b.checkout <= ?)
                )
            )
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$checkin, $checkin, $checkout, $checkout, $checkin, $checkout]);
    } else {
        $stmt = $pdo->query("SELECT * FROM rooms WHERE status = 'available'");
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRoomTypes() {
    global $pdo;
    $stmt = $pdo->query("SELECT DISTINCT type FROM rooms WHERE status = 'available'");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getBookingStats($user_id = null) {
    global $pdo;
    
    if ($user_id) {
        // User-specific stats
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_bookings FROM bookings WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $total_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['total_bookings'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as confirmed_bookings FROM bookings WHERE user_id = ? AND status = 'confirmed'");
        $stmt->execute([$user_id]);
        $confirmed_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['confirmed_bookings'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as pending_bookings FROM bookings WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$user_id]);
        $pending_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['pending_bookings'];
        
        $stmt = $pdo->prepare("SELECT SUM(total_price) as total_spent FROM bookings WHERE user_id = ? AND status IN ('confirmed', 'checked_in', 'checked_out')");
        $stmt->execute([$user_id]);
        $total_spent = $stmt->fetch(PDO::FETCH_ASSOC)['total_spent'] ?? 0;
        
        return [
            'total_bookings' => $total_bookings,
            'confirmed_bookings' => $confirmed_bookings,
            'pending_bookings' => $pending_bookings,
            'total_spent' => $total_spent
        ];
    } else {
        // System-wide stats
        $stmt = $pdo->query("SELECT COUNT(*) as total_bookings FROM bookings");
        $total_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['total_bookings'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as pending_bookings FROM bookings WHERE status = 'pending'");
        $pending_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['pending_bookings'];
        
        $stmt = $pdo->query("SELECT SUM(total_price) as revenue FROM bookings WHERE status IN ('confirmed', 'checked_in', 'checked_out')");
        $revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;
        
        return [
            'total_bookings' => $total_bookings,
            'pending_bookings' => $pending_bookings,
            'revenue' => $revenue
        ];
    }
}

function generateInvoice($booking_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT b.*, u.name as guest_name, u.email, u.phone, r.type as room_type, r.price as room_price
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN rooms r ON b.room_id = r.room_id
        WHERE b.booking_id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        return false;
    }
    
    $invoice = [
        'invoice_id' => 'INV-' . str_pad($booking_id, 6, '0', STR_PAD_LEFT),
        'booking_id' => $booking_id,
        'guest_name' => $booking['guest_name'],
        'guest_email' => $booking['email'],
        'guest_phone' => $booking['phone'],
        'room_type' => $booking['room_type'],
        'checkin' => $booking['checkin'],
        'checkout' => $booking['checkout'],
        'nights' => (strtotime($booking['checkout']) - strtotime($booking['checkin'])) / (60 * 60 * 24),
        'room_price' => $booking['room_price'],
        'total_amount' => $booking['total_price'],
        'booking_date' => $booking['created_at'],
        'status' => $booking['status']
    ];
    
    return $invoice;
}

function sendBookingConfirmation($booking_id) {
    // In a real application, this would send an email
    // For now, we'll just log the action
    error_log("Booking confirmation sent for booking ID: " . $booking_id);
    return true;
}

function validateBookingDates($checkin, $checkout) {
    $today = date('Y-m-d');
    $checkin_date = DateTime::createFromFormat('Y-m-d', $checkin);
    $checkout_date = DateTime::createFromFormat('Y-m-d', $checkout);
    $today_date = DateTime::createFromFormat('Y-m-d', $today);
    
    if ($checkin_date < $today_date) {
        return "Check-in date cannot be in the past";
    }
    
    if ($checkout_date <= $checkin_date) {
        return "Check-out date must be after check-in date";
    }
    
    // Maximum stay of 30 days
    $interval = $checkin_date->diff($checkout_date);
    if ($interval->days > 30) {
        return "Maximum stay is 30 days";
    }
    
    return true;
}

function getMonthlyRevenue($year = null) {
    global $pdo;
    
    if (!$year) {
        $year = date('Y');
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as booking_count,
            SUM(total_price) as revenue
        FROM bookings 
        WHERE status IN ('confirmed', 'checked_in', 'checked_out')
        AND YEAR(created_at) = ?
        GROUP BY month
        ORDER BY month
    ");
    $stmt->execute([$year]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPopularRoomTypes($limit = 5) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT r.type, COUNT(b.booking_id) as booking_count, SUM(b.total_price) as revenue
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        WHERE b.status IN ('confirmed', 'checked_in', 'checked_out')
        GROUP BY r.type
        ORDER BY booking_count DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Utility function to format date for display
function formatDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

// Utility function to format currency
function formatCurrency($amount) {
    return 'Rs.' . number_format($amount, 2);
}
?>