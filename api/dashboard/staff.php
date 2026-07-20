<?php
require_once '../api_helper.php';
apiRequireStaff();

$today = date('Y-m-d');

// Today's checkins
$stmt = $pdo->prepare("
        SELECT
    b.*,
    u.name AS guest_name,
    u.phone AS guest_phone,
    u.email AS guest_email,
    r.type AS room_type,
    p.transaction_id,
    p.created_at AS payment_date
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN rooms r ON b.room_id = r.room_id
    LEFT JOIN payments p ON p.booking_id = b.booking_id
        AND p.status = 'completed'
    WHERE b.checkin = ? AND b.status IN ('confirmed', 'checked_in')
    ORDER BY b.checkin
");
$stmt->execute([$today]);
$today_checkins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Today's checkouts
$stmt = $pdo->prepare("
    SELECT
    b.*,
    u.name AS guest_name,
    u.phone AS guest_phone,
    u.email AS guest_email,
    r.type AS room_type,
    p.transaction_id,
    p.created_at AS payment_date
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN rooms r ON b.room_id = r.room_id
    LEFT JOIN payments p ON p.booking_id = b.booking_id
        AND p.status = 'completed'
    WHERE b.checkout = ? AND b.status IN ('checked_in', 'checked_out')
    ORDER BY b.checkout
");
$stmt->execute([$today]);
$today_checkouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pending reservations
$stmt = $pdo->query("
    SELECT
    b.*,
    u.name AS guest_name,
    u.phone AS guest_phone,
    u.email AS guest_email,
    r.type AS room_type,
    p.transaction_id,
    p.created_at AS payment_date
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN rooms r ON b.room_id = r.room_id
    LEFT JOIN payments p ON p.booking_id = b.booking_id
        AND p.status = 'completed'
    WHERE b.status = 'pending'
    ORDER BY b.created_at DESC 
    LIMIT 5
");
$pending_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Occupied rooms count
$stmt = $pdo->query("SELECT COUNT(*) as occupied_rooms FROM bookings WHERE status = 'checked_in'");
$occupied_rooms = (int)$stmt->fetch(PDO::FETCH_ASSOC)['occupied_rooms'];

// Room stats
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_rooms,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_rooms,
        SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_rooms,
        SUM(CASE WHEN status = 'unavailable' THEN 1 ELSE 0 END) as unavailable_rooms
    FROM rooms
");
$room_stats = $stmt->fetch(PDO::FETCH_ASSOC);

sendJSON([
    'stats' => [
        'todayCheckinsCount' => count($today_checkins),
        'todayCheckoutsCount' => count($today_checkouts),
        'pendingReservationsCount' => count($pending_reservations),
        'occupiedRooms' => $occupied_rooms
    ],
    'todayCheckins' => $today_checkins,
    'todayCheckouts' => $today_checkouts,
    'pendingReservations' => $pending_reservations,
    'roomStats' => [
        'available' => (int)($room_stats['available_rooms'] ?? 0),
        'occupied' => (int)($room_stats['occupied_rooms'] ?? 0),
        'unavailable' => (int)($room_stats['unavailable_rooms'] ?? 0)
    ]
]);
?>
