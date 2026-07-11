<?php
// api/bookings/list.php
require_once '../api_helper.php';
apiRequireLogin();

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$query = "
    SELECT b.*, u.name as guest_name, u.email as guest_email, u.phone as guest_phone,
           r.type as room_type, r.price as room_price, r.image_url as room_image
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON b.room_id = r.room_id
";

$params = [];

if ($role === 'guest') {
    $query .= " WHERE b.user_id = ? ORDER BY b.created_at DESC";
    $params[] = $user_id;
} else {
    // Admin or Staff can see all, maybe filter by status or guest_id
    $filters = [];
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $filters[] = "b.status = ?";
        $params[] = $_GET['status'];
    }
    if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
        $filters[] = "b.user_id = ?";
        $params[] = $_GET['user_id'];
    }
    if (isset($_GET['checkin_date']) && !empty($_GET['checkin_date'])) {
        $filters[] = "b.checkin = ?";
        $params[] = $_GET['checkin_date'];
    }
    
    if (!empty($filters)) {
        $query .= " WHERE " . implode(" AND ", $filters);
    }
    
    $query .= " ORDER BY b.created_at DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cast types properly for JSON output
foreach ($bookings as &$booking) {
    $booking['booking_id'] = (int)$booking['booking_id'];
    $booking['user_id'] = (int)$booking['user_id'];
    $booking['room_id'] = (int)$booking['room_id'];
    $booking['guests'] = (int)$booking['guests'];
    $booking['total_price'] = (float)$booking['total_price'];
    $booking['room_price'] = (float)$booking['room_price'];
}

sendJSON($bookings);
?>
