<?php
require_once 'includes/config.php';
requireGuest();

// Handle booking cancellation from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = $_POST['booking_id'];
    $user_id = $_SESSION['user_id'];
    
    // Users can only cancel their own bookings
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ? AND user_id = ?");
    if ($stmt->execute([$booking_id, $user_id])) {
        $_SESSION['success'] = "Booking cancelled successfully!";
    } else {
        $_SESSION['error'] = "Failed to cancel booking.";
    }
    header("Location: guest_dashboard.php");
    exit();
}

// Get user bookings
$stmt = $pdo->prepare("
    SELECT b.*, r.type, r.price 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.room_id 
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$total_bookings = count($bookings);
$confirmed_bookings = count(array_filter($bookings, function($booking) {
    return $booking['status'] === 'confirmed';
}));
$pending_bookings = count(array_filter($bookings, function($booking) {
    return $booking['status'] === 'pending';
}));
$total_spent = array_sum(array_column($bookings, 'total_price'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Hotel MS</title>
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
                <li><a href="guest_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="rooms.php"><i class="fas fa-bed"></i> Book Rooms</a></li>
                <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="content">
            <div class="page-header">
                <h1>Welcome back, <?php echo $_SESSION['name']; ?>!</h1>
                <p>Here's an overview of your bookings and account</p>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_bookings; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo $confirmed_bookings; ?></div>
                    <div class="stat-label">Confirmed</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo $pending_bookings; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number">Rs.<?php echo number_format($total_spent, 2); ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
            </div>
            
            <!-- Recent Bookings -->
            <section class="card">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <?php if (empty($bookings)): ?>
                    <div style="text-align: center; padding: 40px;">
                        <h3 style="color: #6c757d; margin-bottom: 15px;">No Bookings Yet</h3>
                        <p style="color: #6c757d; margin-bottom: 20px;">You haven't made any bookings yet.</p>
                        <a href="rooms.php" class="btn btn-primary">Book Your First Room</a>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Room Type</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Guests</th>
                                    <th>Total Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($bookings, 0, 5) as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['booking_id']; ?></td>
                                    <td><?php echo $booking['type']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking['checkin'])); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking['checkout'])); ?></td>
                                    <td><?php echo $booking['guests']; ?></td>
                                    <td>Rs.<?php echo number_format($booking['total_price'], 2); ?></td>
                                    <td>
                                        <span class="status <?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <button type="submit" name="cancel_booking" class="btn btn-danger" 
                                                        onclick="return confirm('Are you sure you want to cancel this booking?')">
                                                    Cancel
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #6c757d;"><?php echo ucfirst($booking['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="bookings.php" class="btn btn-outline">View All Bookings</a>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>