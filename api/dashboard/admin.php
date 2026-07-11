<?php
require_once '../api_helper.php';
apiRequireAdmin();

// Total rooms
$stmt = $pdo->query("SELECT COUNT(*) as total_rooms FROM rooms");
$total_rooms = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_rooms'];

// Total bookings
$stmt = $pdo->query("SELECT COUNT(*) as total_bookings FROM bookings");
$total_bookings = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_bookings'];

// Pending bookings
$stmt = $pdo->query("SELECT COUNT(*) as pending_bookings FROM bookings WHERE status = 'pending'");
$pending_bookings = (int)$stmt->fetch(PDO::FETCH_ASSOC)['pending_bookings'];

// Revenue
$stmt = $pdo->query("SELECT SUM(total_price) as revenue FROM bookings WHERE status IN ('confirmed', 'checked_in', 'checked_out')");
$revenue = (float)($stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0);

// Total registered guests
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'guest'");
$total_users = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

// Pending staff
$stmt = $pdo->query("SELECT COUNT(*) as pending_staff FROM users WHERE role = 'staff' AND status = 'pending'");
$pending_staff = (int)$stmt->fetch(PDO::FETCH_ASSOC)['pending_staff'];

// Recent bookings (5)
$stmt = $pdo->query("
    SELECT b.*, u.name as user_name, r.type as room_type 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN rooms r ON b.room_id = r.room_id 
    ORDER BY b.created_at DESC 
    LIMIT 5
");
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        'totalRooms' => $total_rooms,
        'totalBookings' => $total_bookings,
        'pendingBookings' => $pending_bookings,
        'revenue' => $revenue,
        'registeredGuests' => $total_users,
        'pendingStaff' => $pending_staff
    ],
    'roomStats' => [
        'available' => (int)($room_stats['available_rooms'] ?? 0),
        'occupied' => (int)($room_stats['occupied_rooms'] ?? 0),
        'unavailable' => (int)($room_stats['unavailable_rooms'] ?? 0)
    ],
    'recentBookings' => $recent_bookings
]);
?>
