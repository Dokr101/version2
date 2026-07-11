<?php
// api/rooms/list.php
require_once '../api_helper.php';

$checkin = isset($_GET['checkin']) ? $_GET['checkin'] : null;
$checkout = isset($_GET['checkout']) ? $_GET['checkout'] : null;

// Validate dates if provided
$unavailable_room_ids = [];
if ($checkin && $checkout) {
    if (strtotime($checkin) >= strtotime($checkout)) {
        sendError('Check-out date must be after check-in date.');
    }
    
    // Find rooms that have overlapping bookings
    $stmt = $pdo->prepare("
        SELECT DISTINCT room_id FROM bookings 
        WHERE status IN ('pending', 'confirmed', 'checked_in')
        AND (
            (checkin <= ? AND checkout > ?) OR 
            (checkin < ? AND checkout >= ?) OR
            (checkin >= ? AND checkout <= ?)
        )
    ");
    $stmt->execute([$checkin, $checkin, $checkout, $checkout, $checkin, $checkout]);
    $unavailable_room_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Get all rooms
$stmt = $pdo->query("SELECT * FROM rooms ORDER BY room_id ASC");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map availability
foreach ($rooms as &$room) {
    $room['room_id'] = (int)$room['room_id'];
    $room['price'] = (float)$room['price'];
    
    if ($checkin && $checkout) {
        $room['is_available'] = !in_array($room['room_id'], $unavailable_room_ids) && $room['status'] === 'available';
    } else {
        $room['is_available'] = $room['status'] === 'available';
    }
}

sendJSON($rooms);
?>
