<?php
require_once 'config.php';

header('Content-Type: application/json');

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'confirm_payment':
            confirmPayment();
            break;
        case 'check_availability':
            checkAvailability();
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
        // Verify that the booking belongs to the user (unless admin)
        if (!isAdmin()) {
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
        
        // Update booking status to confirmed
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE booking_id = ?");
        $success = $stmt->execute([$booking_id]);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Payment confirmed']);
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
            SELECT r.room_id 
            FROM rooms r 
            WHERE r.type = ? 
            AND r.status = 'available'
            AND r.room_id NOT IN (
                SELECT b.room_id 
                FROM bookings b 
                WHERE b.status IN ('pending', 'confirmed')
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

// Additional helper functions
function getRoomTypes() {
    global $pdo;
    $stmt = $pdo->query("SELECT DISTINCT type FROM rooms WHERE status = 'available'");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function calculateTotalPrice($room_id, $checkin, $checkout) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT price FROM rooms WHERE room_id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) return 0;
    
    $nights = (strtotime($checkout) - strtotime($checkin)) / (60 * 60 * 24);
    return $room['price'] * $nights;
}
?>