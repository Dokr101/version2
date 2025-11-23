<?php
require_once '../includes/config.php';
requireStaff();

// Handle check-in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkin'])) {
    $booking_id = $_POST['booking_id'];
    
    // Update booking status to checked_in
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'checked_in' WHERE booking_id = ?");
    if ($stmt->execute([$booking_id])) {
        // Update room status to occupied
        $stmt = $pdo->prepare("UPDATE rooms r JOIN bookings b ON r.room_id = b.room_id SET r.status = 'occupied' WHERE b.booking_id = ?");
        $stmt->execute([$booking_id]);
        
        $_SESSION['success'] = "Guest checked in successfully!";
    } else {
        $_SESSION['error'] = "Failed to check in guest.";
    }
    header("Location: /version2/hotel staff/staff_chekin.php");
    exit();
}

// Get today's check-ins
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT b.*, u.name as guest_name, r.type as room_type, r.room_id 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN rooms r ON b.room_id = r.room_id 
    WHERE b.checkin = ? AND b.status = 'confirmed'
    ORDER BY b.checkin
");
$stmt->execute([$today]);
$checkins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in Guests - Hotel MS</title>
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
                <div class="logo-subtitle">Staff Panel</div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="/version2/hotel staff/staff_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="/version2/hotel staff/staff_chekin.php" class="active"><i class="fas fa-sign-in-alt"></i> Check-in</a></li>
                <li><a href="/version2/hotel staff/staff_checkout.php"><i class="fas fa-sign-out-alt"></i> Check-out</a></li>
                <li><a href="/version2/hotel staff/staff_reservations.php"><i class="fas fa-calendar-check"></i> Reservations</a></li>
                <li><a href="/version2/hotel staff/staff_payments.php"><i class="fas fa-credit-card"></i> Process Payments</a></li>
                <li><a href="/version2/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="content">
            <div class="page-header">
                <h1>Check-in Guests</h1>
                <p>Manage guest check-ins for today (<?php echo date('F j, Y'); ?>)</p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <!-- Check-in List -->
            <section class="card">
                <div class="card-header">
                    <h2>Today's Check-ins</h2>
                </div>
                <?php if (empty($checkins)): ?>
                    <p>No check-ins scheduled for today.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Guest Name</th>
                                    <th>Room Type</th>
                                    <th>Room Number</th>
                                    <th>Check-in Date</th>
                                    <th>Check-out Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($checkins as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['booking_id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                                    <td><?php echo $booking['room_type']; ?></td>
                                    <td>Room #<?php echo $booking['room_id']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking['checkin'])); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking['checkout'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                            <button type="submit" name="checkin" class="btn btn-primary">Check-in</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>