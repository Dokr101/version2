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

// Calculate nights
$checkin = new DateTime($booking['checkin']);
$checkout = new DateTime($booking['checkout']);
$nights = $checkin->diff($checkout)->days;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Payment - Hotel MS</title>
    <link rel="stylesheet" href="/version2/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="main-content">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <div class="logo-circle">
                    <i class="fas fa-hotel"></i>
                </div>
                <div class="logo-text">Hotel MS</div>
                <div class="logo-subtitle">Guest Portal</div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="/version2/guest/guest_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="/version2/guest/rooms.php"><i class="fas fa-bed"></i> Book Rooms</a></li>
                <li><a href="/version2/bookings.php" class="active"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
                <li><a href="/version2/guest/profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="/version2/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="content">
            <div class="page-header">
                <h1><i class="fas fa-credit-card"></i> Process Payment</h1>
                <p>Complete your booking payment securely with Khalti</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px 0; border: 1px solid #f5c6cb;">
                    <strong><i class="fas fa-exclamation-circle"></i> Error:</strong> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 20px 0; border: 1px solid #c3e6cb;">
                    <strong><i class="fas fa-check-circle"></i> Success:</strong> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">
                <!-- Booking Summary -->
                <div class="card">
                    <h2 style="margin-bottom: 20px; color: var(--primary);">
                        <i class="fas fa-file-invoice"></i> Booking Summary
                    </h2>
                    
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--gray-light);">
                            <span style="color: var(--secondary); font-weight: 500;">Booking ID:</span>
                            <span style="font-weight: bold;">#<?php echo $booking['booking_id']; ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--gray-light);">
                            <span style="color: var(--secondary); font-weight: 500;">Room Type:</span>
                            <span><?php echo $booking['room_type']; ?> Room</span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--gray-light);">
                            <span style="color: var(--secondary); font-weight: 500;">Check-in:</span>
                            <span><?php echo date('M j, Y', strtotime($booking['checkin'])); ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--gray-light);">
                            <span style="color: var(--secondary); font-weight: 500;">Check-out:</span>
                            <span><?php echo date('M j, Y', strtotime($booking['checkout'])); ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--gray-light);">
                            <span style="color: var(--secondary); font-weight: 500;">Number of Nights:</span>
                            <span><?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--gray-light);">
                            <span style="color: var(--secondary); font-weight: 500;">Guests:</span>
                            <span><?php echo $booking['guests']; ?> guest<?php echo $booking['guests'] > 1 ? 's' : ''; ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--gray-light);">
                            <span style="color: var(--secondary); font-weight: 500;">Price per Night:</span>
                            <span>Rs. <?php echo number_format($booking['price'], 2); ?></span>
                        </div>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); padding: 20px; border-radius: 8px; margin-top: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 1.2rem; font-weight: 600; color: var(--primary);">Total Amount:</span>
                            <span style="font-size: 1.8rem; font-weight: bold; color: var(--success);">
                                Rs. <?php echo number_format($booking['total_price'], 2); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Payment Section -->
                <div class="card">
                    <h2 style="margin-bottom: 20px; color: var(--primary);">
                        <i class="fas fa-shield-alt"></i> Secure Payment
                    </h2>
                    
                    <div style="text-align: center; padding: 30px 20px;">
                        <img src="https://web.khalti.com/static/img/logo1.png" alt="Khalti" style="max-width: 150px; margin-bottom: 20px;">
                        
                        <p style="color: var(--secondary); margin-bottom: 30px; line-height: 1.6;">
                            Pay securely using Khalti. You can use your Khalti wallet, mobile banking, or connect your bank account.
                        </p>
                        
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                            <p style="font-size: 0.9rem; color: var(--secondary); margin-bottom: 15px;">
                                <i class="fas fa-lock" style="color: var(--success);"></i> Your payment is secured with industry-standard encryption
                            </p>
                            <p style="font-size: 0.9rem; color: var(--secondary); margin: 0;">
                                <i class="fas fa-check-circle" style="color: var(--success);"></i> Booking will be confirmed immediately after payment
                            </p>
                        </div>
                        
                        <button id="payment-button" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1.1rem; background: linear-gradient(135deg, #5C2D91, #7C3AAF); border: none; margin-bottom: 15px;">
                            <i class="fas fa-credit-card"></i> Pay Rs. <?php echo number_format($booking['total_price'], 2); ?>
                        </button>
                        
                        <a href="/version2/bookings.php" class="btn btn-outline" style="width: 100%; display: inline-block; text-align: center;">
                            <i class="fas fa-arrow-left"></i> Back to Bookings
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Direct redirect to Khalti payment when clicking Pay Now button
        console.log("Script loaded");
        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.getElementById("payment-button");
            console.log("Payment button found:", btn);
            
            if (btn) {
                btn.onclick = function () {
                    console.log("Payment button clicked! Booking ID: <?php echo $booking['booking_id']; ?>");
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    
                    // Redirect to Khalti payment initiation (will then redirect to Khalti's page)
                    var url = '/version2/guest/initiate_khalti_payment.php?booking_id=<?php echo $booking['booking_id']; ?>';
                    console.log("Redirecting to:", url);
                    window.location.href = url;
                }
            } else {
                console.error("Payment button not found!");
            }
        });
    </script>
</body>
</html>
