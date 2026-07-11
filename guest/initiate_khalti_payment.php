<?php
require_once '../includes/config.php';
require_once '../includes/khalti_config.php';
requireLogin();
requireGuest();

// Check if booking_id is provided
if (!isset($_GET['booking_id'])) {
    $_SESSION['error'] = "Invalid booking request.";
    header("Location: /version2/app/guest/bookings");
    exit();
}

$booking_id = $_GET['booking_id'];
$user_id = $_SESSION['user_id'];

// Get booking details
$stmt = $pdo->prepare("
    SELECT b.*, r.type as room_type, r.price, u.name as user_name, u.email, u.phone
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
    header("Location: /version2/app/guest/bookings");
    exit();
}

// Check if already paid
if ($booking['payment_status'] === 'paid') {
    $_SESSION['success'] = "This booking has already been paid.";
    header("Location: /version2/app/guest/bookings");
    exit();
}

// Determine the correct server URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . '://' . $host . '/version2';

// Initialize Khalti payment
$amount_in_paisa = $booking['total_price'] * 100; // Convert to paisa

// Use the user's phone from the database, or a default if not available
$user_phone = !empty($booking['phone']) ? $booking['phone'] : '9800000000';

// Khalti payment initiation data
$data = array(
    "return_url" => $base_url . "/guest/payment_verify.php?booking_id=" . $booking_id,
    "website_url" => $base_url . "/",
    "amount" => $amount_in_paisa,
    "purchase_order_id" => "booking_" . $booking_id,
    "purchase_order_name" => "Hotel Room Booking #" . $booking_id,
    "customer_info" => array(
        "name" => $booking['user_name'],
        "email" => $booking['email'],
        "phone" => $user_phone
    )
);

// Initialize payment with Khalti API v2
$endpoint = KHALTI_LIVE_MODE ? "https://khalti.com/api/v2/epayment/initiate/" : "https://a.khalti.com/api/v2/epayment/initiate/";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

$headers = [
    'Authorization: key ' . KHALTI_SECRET_KEY,
    'Content-Type: application/json'
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch); // Capture before closing
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

    // Check HTTP status code first
    if ($status_code === 0) {
        $error_message .= "Could not connect to the payment gateway. Please check your internet connection or try again later. (cURL error: " . $curl_error . ")";
    } elseif (isset($response_data['error_message'])) {
        $error_message .= "Error: " . $response_data['error_message'];
    } elseif (isset($response_data['detail'])) {
        $error_message .= "Detail: " . $response_data['detail'];
    } elseif (isset($response_data['error_key']) && $response_data['error_key'] === 'InvalidAmount') {
        $error_message .= "Invalid payment amount. Please contact support.";
    } else {
        $error_message .= "Unexpected response (HTTP " . $status_code . "). Please try again.";
    }

    error_log("Khalti Payment Initiation Failed: " . $error_message);
    header("Location: /version2/app/guest/payment-error?error=" . urlencode($error_message));
    exit();
}
?>