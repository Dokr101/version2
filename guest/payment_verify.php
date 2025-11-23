<?php
require_once '../includes/config.php';
require_once '../includes/khalti_config.php';
requireLogin();
requireGuest();

// Check if required parameters are present
if (!isset($_GET['pidx']) || !isset($_GET['booking_id'])) {
    $_SESSION['error'] = "Invalid payment verification request.";
    header("Location: /version2/bookings.php");
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
    $_SESSION['error'] = "Booking not found or unauthorized access.";
    header("Location: /version2/bookings.php");
    exit();
}

// Verify payment with Khalti using pidx
// TEST/SANDBOX MODE - Using sandbox endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://a.khalti.com/api/v2/epayment/lookup/");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('pidx' => $pidx)));
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
        
        $_SESSION['success'] = "Payment successful! Your booking has been confirmed.";
        header("Location: /version2/bookings.php");
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Payment verification succeeded but database update failed. Please contact support.";
        header("Location: /version2/bookings.php");
        exit();
    }
} else {
    // Payment verification failed or pending
    $status = isset($response_data['status']) ? $response_data['status'] : 'Unknown';
    
    if ($status === 'Pending') {
        $_SESSION['error'] = "Payment is still pending. Please wait or try again.";
    } else {
        $_SESSION['error'] = "Payment verification failed. Status: " . $status;
    }
    
    header("Location: /version2/bookings.php");
    exit();
}
?>
