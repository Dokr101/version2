<?php
require_once 'includes/config.php';
requireStaff();

// Handle reservation actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm'])) {
        $booking_id = $_POST['booking_id'];
        
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE booking_id = ?");
        if ($stmt->execute([$booking_id])) {
            $_SESSION['success'] = "Reservation confirmed successfully!";
        } else {
            $_SESSION['error'] = "Failed to confirm reservation.";
        }
    } elseif (isset($_POST['cancel'])) {
        $booking_id = $_POST['booking_id'];
        
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?");
        if ($stmt->execute([$booking_id])) {
            $_SESSION['success'] = "Reservation cancelled successfully!";
        } else {
            $_SESSION['error'] = "Failed to cancel reservation.";
        }
    }
    header("Location: staff_reservations.php");
    exit();
}

// Get all pending and confirmed reservations
$stmt = $pdo->prepare("
    SELECT b.*, u.name as guest_name, u.email, u.phone, r.type as room_type, r.price
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN rooms r ON b.room_id = r.room_id 
    WHERE b.status IN ('pending', 'confirmed')
    ORDER BY b.created_at DESC
");
$stmt->execute();
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations - Hotel MS</title>
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
                <li><a href="staff_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="staff_checkin.php"><i class="fas fa-sign-in-alt"></i> Check-in</a></li>
                <li><a href="staff_checkout.php"><i class="fas fa-sign-out-alt"></i> Check-out</a></li>
                <li><a href="staff_reservations.php" class="active"><i class="fas fa-calendar-check"></i> Reservations</a></li>
                <li><a href="staff_payments.php"><i class="fas fa-credit-card"></i> Process Payments</a></li>
                <li><a href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="content">
            <div class="page-header">
                <h1>Manage Reservations</h1>
                <p>Confirm or cancel guest reservations</p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <!-- Reservations List -->
            <section class="card">
                <div class="card-header">
                    <h2>All Reservations</h2>
                </div>
                <?php if (empty($reservations)): ?>
                    <p>No reservations found.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Guest Name</th>
                                    <th>Contact</th>
                                    <th>Room Type</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Total Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reservations as $reservation): ?>
                                <tr>
                                    <td>#<?php echo $reservation['booking_id']; ?></td>
                                    <td><?php echo htmlspecialchars($reservation['guest_name']); ?></td>
                                    <td>
                                        <div><?php echo $reservation['email']; ?></div>
                                        <div><?php echo $reservation['phone']; ?></div>
                                    </td>
                                    <td><?php echo $reservation['room_type']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($reservation['checkin'])); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($reservation['checkout'])); ?></td>
                                    <td>Rs.<?php echo number_format($reservation['total_price'], 2); ?></td>
                                    <td>
                                        <span class="status <?php echo $reservation['status']; ?>">
                                            <?php echo ucfirst($reservation['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <?php if ($reservation['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $reservation['booking_id']; ?>">
                                                    <button type="submit" name="confirm" class="btn btn-primary">Confirm</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="booking_id" value="<?php echo $reservation['booking_id']; ?>">
                                                <button type="submit" name="cancel" class="btn btn-danger">Cancel</button>
                                            </form>
                                        </div>
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