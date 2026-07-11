<?php
// api/rooms/booked_dates.php
require_once '../api_helper.php';
apiRequireLogin();

$room_id = intval($_GET['room_id'] ?? 0);
if (!$room_id) {
    sendError('Room ID is required.');
}

// Auto-cancel any unpaid bookings older than 15 minutes to release dates
$pdo->query("
    UPDATE bookings 
    SET status = 'cancelled' 
    WHERE status = 'pending' 
      AND payment_status = 'pending'
      AND created_at < NOW() - INTERVAL 15 MINUTE
");

// Fetch active bookings for this room (not cancelled or checked out)
$stmt = $pdo->prepare("
    SELECT checkin, checkout 
    FROM bookings 
    WHERE room_id = ? 
      AND status IN ('pending', 'confirmed', 'checked_in')
      AND checkout >= CURDATE()
    ORDER BY checkin ASC
");
$stmt->execute([$room_id]);
$dates = $stmt->fetchAll(PDO::FETCH_ASSOC);

sendJSON($dates);
?>
