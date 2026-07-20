<?php
// api/bookings/create.php
require_once '../api_helper.php';
apiRequireLogin();

// Auto-cancel any unpaid bookings older than 15 minutes before checking availability
$pdo->query("
    UPDATE bookings 
    SET status = 'cancelled' 
    WHERE status = 'pending' 
      AND payment_status = 'pending' 
      AND created_at < NOW() - INTERVAL 15 MINUTE
");

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

$room_id = intval($input['room_id'] ?? 0);
$checkin = $input['checkin'] ?? null;
$checkout = $input['checkout'] ?? null;
$guests = intval($input['guests'] ?? 1);
$user_id = $_SESSION['user_id'];

if (!$room_id || !$checkin || !$checkout || !$guests) {
    sendError('Missing required booking fields (room_id, checkin, checkout, guests).');
}

if (strtotime($checkin) >= strtotime($checkout)) {
    sendError('Check-out date must be after check-in date.');
}

if (strtotime($checkin) < strtotime(date('Y-m-d'))) {
    sendError('Check-in date cannot be in the past.');
}

// Get room details
$stmt = $pdo->prepare("SELECT type, price, status, capacity FROM rooms WHERE room_id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    sendError('Room not found.');
}

if ($room['status'] === 'unavailable') {
    sendError('This room is currently unavailable.');
}

// capacity check
if ($guests > $room['capacity']) {
    sendError("Guest count exceeds room capacity (Max capacity: {$room['capacity']}).");
}

// Check if room is available for the dates
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM bookings b
    WHERE b.room_id = ? 
    AND b.status IN ('pending', 'confirmed', 'checked_in')
    AND (b.payment_status != 'pending' OR b.created_at >= NOW() - INTERVAL 15 MINUTE)
    AND (
        (b.checkin <= ? AND b.checkout > ?) OR 
        (b.checkin < ? AND b.checkout >= ?) OR
        (b.checkin >= ? AND b.checkout <= ?)
    )
");
$stmt->execute([$room_id, $checkin, $checkin, $checkout, $checkout, $checkin, $checkout]);
$existing_booking_count = $stmt->fetchColumn();

if ($existing_booking_count > 0) {
    sendError('Sorry, the room is not available for the selected dates.');
}

// Calculate total price with 13% VAT
$nights = (strtotime($checkout) - strtotime($checkin)) / (60 * 60 * 24);
$total_price       = $room['price'] * $nights;  // pre-tax subtotal
$tax_rate          = 0.13;
$tax_amount        = $total_price * $tax_rate;
$total_price_with_tax = $total_price + $tax_amount;

// Create booking (store tax-inclusive total and tax amount separately)
$stmt = $pdo->prepare("
    INSERT INTO bookings (user_id, room_id, checkin, checkout, guests, total_price, tax_amount, payment_status, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')
");

if ($stmt->execute([$user_id, $room_id, $checkin, $checkout, $guests, $total_price_with_tax, $tax_amount])) {
    $booking_id = $pdo->lastInsertId();
    
    // Fetch user details for email notification
    $stmtUser = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $userObj = $stmtUser->fetch(PDO::FETCH_ASSOC);
    $userEmail = $userObj['email'] ?? '';
    $userName = $userObj['name'] ?? 'Guest';

    // Trigger booking confirmation email
    if ($userEmail) {
        require_once '../../includes/email_helper.php';
        $subject = 'Booking Initialized – HRMS';
        $content = "Dear <strong>{$userName}</strong>,<br><br>";
        $content .= "Thank you for choosing HRMS! Your room booking has been initialized.<br><br>";
        $content .= "<strong>Booking Details:</strong><br>";
        $content .= "• Room Type: {$room['type']} Room<br>";
        $content .= "• Check-in: {$checkin}<br>";
        $content .= "• Check-out: {$checkout}<br>";
        $content .= "• Guests: {$guests}<br>";
        $content .= "• Tax (13% VAT): Rs. " . number_format($tax_amount, 2) . "<br>";
        $content .= "• Total (incl. tax): Rs. " . number_format($total_price_with_tax, 2) . "<br><br>";
        $content .= "Please complete your payment to fully confirm your reservation. Unpaid pending bookings expire after 15 minutes.<br><br>";
        $content .= "We look forward to hosting you!";
        $body = buildEmailTemplate('Booking Confirmation', $content);
        sendNotificationEmail($userEmail, $subject, $body);
    }
    
    // Redirect to the Khalti initiator
    $redirect_url = "/version2/guest/initiate_khalti_payment.php?booking_id=" . $booking_id;
    
    sendSuccess('Booking created successfully! Redirecting to payment...', [
        'booking_id' => (int)$booking_id,
        'redirect_url' => $redirect_url
    ]);
} else {
    sendError('Failed to create booking. Please try again.');
}
?>
