<?php
// api/bookings/cancel.php
require_once '../api_helper.php';
apiRequireGuest();

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
$booking_id = intval($input['booking_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$booking_id) {
    sendError('Booking ID is required.');
}

// Check booking ownership and status
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = ? AND user_id = ?");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    sendError('Booking not found or access denied.');
}

if (!in_array($booking['status'], ['pending', 'confirmed'])) {
    sendError('Only pending or confirmed bookings can be cancelled.');
}

// Perform cancellation
$stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?");
if ($stmt->execute([$booking_id])) {
    // Send email notification
    require_once '../../includes/email_helper.php';
    $guestEmail = $_SESSION['email'] ?? '';
    $guestName = $_SESSION['name'] ?? 'Guest';

    if ($guestEmail) {
        $subject = 'Booking Cancelled – HRMS';
        $content = "Dear <strong>" . htmlspecialchars($guestName) . "</strong>,<br><br>";
        $content .= "Your booking (ID: #{$booking_id}) has been successfully cancelled.<br><br>";
        $content .= "If this was a mistake or you wish to book another room, please visit our portal.<br><br>";
        $content .= "Thank you,<br>HRMS Support";
        
        $body = buildEmailTemplate('Booking Cancellation', $content);
        sendNotificationEmail($guestEmail, $subject, $body);
    }

    sendSuccess('Booking cancelled successfully!');
} else {
    sendError('Failed to cancel booking.');
}
?>
