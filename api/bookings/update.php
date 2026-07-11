<?php
// api/bookings/update.php
require_once '../api_helper.php';

// Only staff or admin can update booking statuses manually
if (!isAdmin() && !isStaff()) {
    sendError('Forbidden. Staff or Admin access required.', 403);
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
$booking_id = intval($input['booking_id'] ?? 0);
$action = $input['action'] ?? ''; // 'confirm', 'checkin', 'checkout', 'cancel', 'pay'

if (!$booking_id || empty($action)) {
    sendError('Booking ID and action are required.');
}

// Get the booking details to check existence and current state
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = ?");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    sendError('Booking not found.');
}

$room_id = $booking['room_id'];

try {
    $pdo->beginTransaction();

    if ($action === 'confirm') {
        // pending -> confirmed
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        
    } elseif ($action === 'checkin') {
        // confirmed -> checked_in
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'checked_in' WHERE booking_id = ?");
        $stmt->execute([$booking_id]);

        // Update room status to occupied
        $stmt = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE room_id = ?");
        $stmt->execute([$room_id]);
        
    } elseif ($action === 'checkout') {
        // checked_in -> checked_out
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'checked_out' WHERE booking_id = ?");
        $stmt->execute([$booking_id]);

        // Update room status to available
        $stmt = $pdo->prepare("UPDATE rooms SET status = 'available' WHERE room_id = ?");
        $stmt->execute([$room_id]);
        
    } elseif ($action === 'cancel') {
        // any -> cancelled
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?");
        $stmt->execute([$booking_id]);

        // If the guest was checked in, make the room available again
        if ($booking['status'] === 'checked_in') {
            $stmt = $pdo->prepare("UPDATE rooms SET status = 'available' WHERE room_id = ?");
            $stmt->execute([$room_id]);
        }
        
    } elseif ($action === 'pay') {
        // Set payment status to paid
        $stmt = $pdo->prepare("UPDATE bookings SET payment_status = 'paid' WHERE booking_id = ?");
        $stmt->execute([$booking_id]);

        // Also add a payment record if not exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE booking_id = ? AND status = 'completed'");
        $stmt->execute([$booking_id]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, payment_method, status) VALUES (?, ?, 'cash', 'completed')");
            $stmt->execute([$booking_id, $booking['total_price']]);
        }
    } else {
        sendError('Invalid action.');
    }

    $pdo->commit();

    // Send email notifications for relevant actions
    require_once '../../includes/email_helper.php';
    $stmtGuest = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmtGuest->execute([$booking['user_id']]);
    $guest = $stmtGuest->fetch(PDO::FETCH_ASSOC);

    if ($guest && $guest['email']) {
        $guestName = htmlspecialchars($guest['name']);
        $emailContent = '';
        $emailSubject = '';

        if ($action === 'checkin') {
            $emailSubject = 'Check-In Confirmed – HRMS';
            $emailContent = "Dear <strong>{$guestName}</strong>,<br><br>";
            $emailContent .= "Your check-in for Room #{$room_id} has been confirmed by our front desk staff.<br><br>";
            $emailContent .= "We hope you enjoy your stay at HRMS!";
        } elseif ($action === 'checkout') {
            $emailSubject = 'Check-Out Complete – HRMS';
            $emailContent = "Dear <strong>{$guestName}</strong>,<br><br>";
            $emailContent .= "Your check-out from Room #{$room_id} has been processed.<br><br>";
            $emailContent .= "Thank you for staying with HRMS. We hope to see you again!";
        } elseif ($action === 'cancel') {
            $emailSubject = 'Booking Cancelled – HRMS';
            $emailContent = "Dear <strong>{$guestName}</strong>,<br><br>";
            $emailContent .= "Your booking (ID: #{$booking_id}) has been cancelled by the management team.<br><br>";
            $emailContent .= "If you believe this was in error, please contact our front desk.";
        } elseif ($action === 'confirm') {
            $emailSubject = 'Booking Confirmed – HRMS';
            $emailContent = "Dear <strong>{$guestName}</strong>,<br><br>";
            $emailContent .= "Your booking (ID: #{$booking_id}) has been confirmed!<br><br>";
            $emailContent .= "Please arrive on your scheduled check-in date. We look forward to hosting you!";
        }

        if ($emailSubject && $emailContent) {
            $body = buildEmailTemplate(str_replace(' – HRMS', '', $emailSubject), $emailContent);
            sendNotificationEmail($guest['email'], $emailSubject, $body);
        }
    }

    sendSuccess("Booking updated successfully for action: $action");
} catch (Exception $e) {
    $pdo->rollBack();
    sendError('Failed to update booking: ' . $e->getMessage(), 500);
}
?>
