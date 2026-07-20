<?php
require_once '../api_helper.php';
apiRequireGuest();

$user_id = $_SESSION['user_id'];

// Get bookings
$stmt = $pdo->prepare("
    SELECT
        b.*,
        r.type as room_type,
        r.price as room_price,
        p.transaction_id,
        p.created_at AS payment_date
    FROM bookings b
    JOIN rooms r ON b.room_id = r.room_id
    LEFT JOIN payments p ON p.booking_id = b.booking_id
        AND p.status = 'completed'
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC

");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_bookings = count($bookings);
$confirmed_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'confirmed'; }));
$pending_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'pending'; }));
$total_spent = array_sum(array_column($bookings, 'total_price'));

sendJSON([
    'stats' => [
        'totalBookings' => $total_bookings,
        'confirmedBookings' => $confirmed_bookings,
        'pendingBookings' => $pending_bookings,
        'totalSpent' => (float)$total_spent
    ],
    'recentBookings' => array_slice($bookings, 0, 5),
    'allBookings' => $bookings
]);
?>
