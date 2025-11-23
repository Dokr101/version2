<?php
require_once '../includes/config.php';
require_once '../includes/khalti_config.php';
requireLogin();
requireGuest();

// Check if booking_id is provided
if (!isset($_GET['booking_id'])) {
    $_SESSION['error'] = "Invalid booking request.";
    header("Location: /version2/bookings.php");
    exit();
}

$booking_id = $_GET['booking_id'];
$user_id = $_SESSION['user_id'];

// Get booking details
$stmt = $pdo->prepare("
    SELECT b.*, r.type as room_type, r.price, u.name as user_name, u.email
    FROM bookings b
    JOIN rooms r ON b.room_id = r.room_id
    JOIN users u ON b.user_id = u.id
    WHERE b.booking_id = ? AND b.user_id = ?
");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

// Verify booking exists and belongs to user
if (!$booking) {
    $_SESSION['error'] = "Booking not found or you don't have permission to access it.";
    header("Location: /version2/bookings.php");
    exit();
}

// Check if already paid
if ($booking['payment_status'] === 'paid') {
    $_SESSION['success'] = "This booking has already been paid.";
    header("Location: /version2/bookings.php");
    exit();
}

// Initialize Khalti payment
$amount_in_paisa = $booking['total_price'] * 100; // Convert to paisa

// Khalti payment initiation data
$data = array(
    "return_url" => "http://localhost/version2/guest/payment_verify.php?booking_id=" . $booking_id,
    "website_url" => "http://localhost/version2/",
    "amount" => $amount_in_paisa,
    "purchase_order_id" => "booking_" . $booking_id,
    "purchase_order_name" => "Hotel Room Booking #" . $booking_id,
    "customer_info" => array(
        "name" => $booking['user_name'],
        "email" => $booking['email'],
        "phone" => "9800000000" // You can get this from user profile if available
    )
);

// Initialize payment with Khalti API v2
// TEST/SANDBOX MODE - Using test keys with sandbox endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://a.khalti.com/api/v2/epayment/initiate/");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$headers = [
    'Authorization: key ' . KHALTI_SECRET_KEY,
    'Content-Type: application/json'
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$response_data = json_decode($response, true);

// Debug: Log the response for troubleshooting
error_log("Khalti API Response: " . $response);
error_log("Status Code: " . $status_code);

// Check if payment initiation was successful
if ($status_code == 200 && isset($response_data['payment_url'])) {
    // Store pidx for verification later
    if (isset($response_data['pidx'])) {
        $_SESSION['khalti_pidx_' . $booking_id] = $response_data['pidx'];
    }
    
    // Redirect to Khalti payment page
    header("Location: " . $response_data['payment_url']);
    exit();
} else {
    // Payment initiation failed - show detailed error
    $error_message = "Failed to initiate payment. ";
    
    if (isset($response_data['error_message'])) {
        $error_message .= "Error: " . $response_data['error_message'];
    } elseif (isset($response_data['detail'])) {
        $error_message .= "Detail: " . $response_data['detail'];
    } else {
        $error_message .= "Status code: " . $status_code . ". Response: " . $response;
    }
    
    error_log("Khalti Payment Initiation Failed: " . $error_message);
    $_SESSION['error'] = $error_message;
    header("Location: /version2/guest/process_payment.php?booking_id=" . $booking_id);
    exit();
}
?>
