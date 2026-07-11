<?php
require_once '../includes/config.php';
require_once '../includes/khalti_config.php';
requireLogin();
requireGuest();

// Check if required parameters are present
if (!isset($_GET['pidx']) || !isset($_GET['booking_id'])) {
    header("Location: /version2/app/guest/payment-error?error=" . urlencode("Invalid payment verification request."));
    exit();
}

$pidx = $_GET['pidx'];
$booking_id = $_GET['booking_id'];
$user_id = $_SESSION['user_id'];

// Verify the booking belongs to this user
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = ? AND user_id = ?");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header("Location: /version2/app/guest/payment-error?error=" . urlencode("Booking not found or unauthorized access."));
    exit();
}

// Verify payment with Khalti using pidx
$lookup_endpoint = KHALTI_LIVE_MODE ? "https://khalti.com/api/v2/epayment/lookup/" : "https://a.khalti.com/api/v2/epayment/lookup/";
$ch = curl_init();
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $lookup_endpoint);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('pidx' => $pidx)));
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
$curl_error = curl_error($ch);
curl_close($ch);

$response_data = json_decode($response, true);

// Check if payment verification was successful
if ($status_code == 200 && isset($response_data['status']) && $response_data['status'] === 'Completed') {
    // Payment verified successfully
    try {
        $pdo->beginTransaction();

        // Update booking status
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET payment_status = 'paid', status = 'confirmed' 
            WHERE booking_id = ?
        ");
        $stmt->execute([$booking_id]);

        // Insert payment record
        $stmt = $pdo->prepare("
            INSERT INTO payments (booking_id, amount, payment_method, transaction_id, status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $amount_in_rupees = $response_data['total_amount'] / 100; // Convert paisa to rupees
        $stmt->execute([
            $booking_id,
            $amount_in_rupees,
            'Khalti',
            $pidx,
            'completed'
        ]);

        $pdo->commit();

        // Clear session pidx
        unset($_SESSION['khalti_pidx_' . $booking_id]);

        // Redirect to success page in the React application!
        header("Location: /version2/app/guest/payment-success?booking_id=" . $booking_id);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: /version2/app/guest/payment-error?booking_id=" . $booking_id . "&error=" . urlencode("Payment succeeded on gateway but database update failed. Please contact front desk."));
        exit();
    }
} else {
    // Payment verification failed or pending
    if ($response === false) {
        $err = "Could not connect to payment gateway (cURL error: " . $curl_error . ")";
    } else {
        $status = isset($response_data['status']) ? $response_data['status'] : 'Unknown';
        if ($status === 'Pending') {
            $err = "Payment is still pending on Khalti. Please try verifying again shortly.";
        } else {
            $err = "Payment verification failed. Status: " . $status;
        }
    }

    header("Location: /version2/app/guest/payment-error?booking_id=" . $booking_id . "&error=" . urlencode($err));
    exit();
}
?>