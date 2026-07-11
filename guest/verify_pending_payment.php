<?php
require_once '../includes/config.php';
require_once '../includes/khalti_config.php';
requireLogin();
requireGuest();

$booking_id = $_GET['booking_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Verify the booking belongs to this user
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = ? AND user_id = ?");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    $_SESSION['error'] = "Booking not found.";
    header("Location: /version2/bookings.php");
    exit();
}

// Check if already paid
if ($booking['payment_status'] === 'paid') {
    $_SESSION['success'] = "This booking is already paid.";
    header("Location: /version2/bookings.php");
    exit();
}

// Check for stored pidx in session (set during initiate_khalti_payment.php)
$pidx = $_SESSION['khalti_pidx_' . $booking_id] ?? null;

if (!$pidx) {
    // No stored pidx - might have expired or never started payment
    $_SESSION['error'] = "No pending payment session found for this booking. Please try paying again.";
    header("Location: /version2/guest/process_payment.php?booking_id=" . $booking_id);
    exit();
}

// Call Khalti lookup API to verify payment status
$lookup_endpoint = KHALTI_LIVE_MODE ? "https://khalti.com/api/v2/epayment/lookup/" : "https://a.khalti.com/api/v2/epayment/lookup/";

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

if ($response === false) {
    // API unreachable
    $_SESSION['error'] = "Could not connect to the payment gateway. Please try again. (cURL error: " . $curl_error . ")";
    header("Location: /version2/bookings.php");
    exit();
}

if ($status_code == 200 && isset($response_data['status']) && $response_data['status'] === 'Completed') {
    // Payment verified! The payment went through on Khalti but the redirect back failed.
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

        $_SESSION['payment_success'] = "Payment verified! Your booking is now confirmed.";
        header("Location: /version2/guest/payment_success.php?booking_id=" . $booking_id);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Payment was verified but database update failed. Please contact support.";
        header("Location: /version2/bookings.php");
        exit();
    }
} elseif ($status_code == 200 && isset($response_data['status']) && $response_data['status'] === 'Pending') {
    // Payment still pending on Khalti
    $_SESSION['error'] = "Your payment is still pending on Khalti. Please complete the payment and try verifying again, or click Pay Now to retry.";
    header("Location: /version2/bookings.php");
    exit();
} else {
    // Payment not found on Khalti or error - pidx might have expired
    $status_detail = isset($response_data['status']) ? $response_data['status'] : 'Unknown';
    $_SESSION['error'] = "Payment could not be verified (status: " . $status_detail . "). The payment session may have expired. Please try paying again.";
    // Clear the stale pidx from session
    unset($_SESSION['khalti_pidx_' . $booking_id]);
    header("Location: /version2/guest/process_payment.php?booking_id=" . $booking_id);
    exit();
}
