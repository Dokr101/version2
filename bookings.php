<?php
require_once 'includes/config.php';
requireLogin();

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_booking']) && (isAdmin() || isStaff())) {
        $booking_id = $_POST['booking_id'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
        if ($stmt->execute([$status, $booking_id])) {
            $_SESSION['success'] = "Booking status updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update booking status.";
        }
    } elseif (isset($_POST['delete_booking']) && isAdmin()) {
        $booking_id = $_POST['booking_id'];
        
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE booking_id = ?");
        if ($stmt->execute([$booking_id])) {
            $_SESSION['success'] = "Booking deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete booking.";
        }
    } elseif (isset($_POST['cancel_booking']) && isGuest()) {
        $booking_id = $_POST['booking_id'];
        $user_id = $_SESSION['user_id'];
        
        // Users can only cancel their own bookings
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ? AND user_id = ?");
        $stmt->execute([$booking_id, $user_id]);
        $_SESSION['success'] = "Booking cancelled successfully!";
    }
    header("Location: /version2/bookings.php");
    exit();
}

// Get bookings based on user role
if (isAdmin() || isStaff()) {
    // Admin and Staff see all bookings
    $stmt = $pdo->prepare("
        SELECT b.*, u.name as user_name, u.email, r.type as room_type, r.price
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        JOIN rooms r ON b.room_id = r.room_id 
        ORDER BY b.created_at DESC
    ");
    $stmt->execute();
} else {
    // Guest sees only their bookings
    $stmt = $pdo->prepare("
        SELECT b.*, r.type as room_type, r.price
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.room_id 
        WHERE b.user_id = ? 
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
}

$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo (isAdmin() || isStaff()) ? 'All Bookings' : 'My Bookings'; ?> - HRMS</title>
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
                <div class="logo-text">HRMS</div>
                <div class="logo-subtitle"><?php echo isAdmin() ? 'Admin Panel' : htmlspecialchars($_SESSION['name']); ?></div>
            </div>
            <ul class="sidebar-menu">
                <?php if (isAdmin()): ?>
                    <li><a href="/version2/admin/admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="/version2/admin/manage_staff.php"><i class="fas fa-users-cog"></i> Manage Staff</a></li>
                    <li><a href="/version2/admin/manage_rooms.php"><i class="fas fa-bed"></i> Manage Rooms</a></li>
                    <li><a href="/version2/bookings.php" class="active"><i class="fas fa-calendar-check"></i> All Bookings</a></li>
                    <li><a href="/version2/admin/payments.php"><i class="fas fa-credit-card"></i> Payment Records</a></li>
                    <li><a href="/version2/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <?php elseif (isStaff()): ?>
                    <li><a href="/version2/hotel_staff/staff_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="/version2/hotel_staff/staff_checkin.php"><i class="fas fa-sign-in-alt"></i> Check-in</a></li>
                    <li><a href="/version2/hotel_staff/staff_checkout.php"><i class="fas fa-sign-out-alt"></i> Check-out</a></li>
                    <li><a href="/version2/hotel_staff/staff_reservations.php"><i class="fas fa-calendar-check"></i> Reservations</a></li>
                    <li><a href="/version2/hotel_staff/staff_payments.php"><i class="fas fa-credit-card"></i> Process Payments</a></li>
                    <li><a href="/version2/bookings.php" class="active"><i class="fas fa-calendar-check"></i> All Bookings</a></li>
                <?php else: ?>
                    <li><a href="/version2/guest/guest_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="/version2/guest/rooms.php"><i class="fas fa-bed"></i> Book Rooms</a></li>
                    <li><a href="/version2/bookings.php" class="active"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
                    <li><a href="/version2/guest/profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <?php endif; ?>
                <li><a href="/version2/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="content">
            <div class="page-header">
                <h1><?php echo (isAdmin() || isStaff()) ? 'All Bookings' : 'My Bookings'; ?></h1>
                <p><?php echo (isAdmin() || isStaff()) ? 'Manage all hotel bookings' : 'View and manage your bookings'; ?></p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <?php if (empty($bookings)): ?>
                <div class="card" style="text-align: center; padding: 60px 20px;">
                    <h3 style="color: #6c757d; margin-bottom: 15px;">No Bookings Found</h3>
                    <p style="color: #6c757d; margin-bottom: 30px;">
                        <?php echo (isAdmin() || isStaff()) ? 'There are no bookings in the system yet.' : 'You haven\'t made any bookings yet.'; ?>
                    </p>
                    <?php if (isGuest()): ?>
                        <a href="/version2/guest/rooms.php" class="btn btn-primary">Book Your First Room</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="table-container" style="overflow-x: auto;">
                        <table style="min-width: 1400px; width: 100%;">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <?php if (isAdmin() || isStaff()): ?>
                                        <th>Guest Name</th>
                                        <th>Email</th>
                                    <?php endif; ?>
                                    <th>Room Type</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Guests</th>
                                    <th>Total Price</th>
                                    <th>Booking Status</th>
                                    <th>Payment Status</th>
                                    <th>Booked On</th>
                                    <th style="min-width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['booking_id']; ?></td>
                                    <?php if (isAdmin() || isStaff()): ?>
                                        <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['email']); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo $booking['room_type']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking['checkin'])); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking['checkout'])); ?></td>
                                    <td><?php echo $booking['guests']; ?></td>
                                    <td>
                                        <?php if ($booking['status'] === 'cancelled'): ?>
                                            <span style="color: #6c757d;">-</span>
                                        <?php else: ?>
                                            Rs.<?php echo number_format($booking['total_price'], 2); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status <?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $payment_status = $booking['payment_status'] ?? 'pending';
                                        $payment_class = '';
                                        $payment_icon = '';
                                        
                                        // For cancelled bookings, show N/A for admin/staff
                                        if ($booking['status'] === 'cancelled' && (isAdmin() || isStaff())) {
                                            $payment_status = 'N/A';
                                            $payment_class = 'cancelled';
                                            $payment_icon = 'fa-ban';
                                        } 
                                        // For confirmed bookings with pending payment, assume paid for admin/staff
                                        elseif ($booking['status'] === 'confirmed' && $payment_status === 'pending' && (isAdmin() || isStaff())) {
                                            $payment_status = 'paid';
                                            $payment_class = 'confirmed';
                                            $payment_icon = 'fa-check-circle';
                                        } 
                                        else {
                                            switch($payment_status) {
                                                case 'paid':
                                                    $payment_class = 'confirmed';
                                                    $payment_icon = 'fa-check-circle';
                                                    break;
                                                case 'refunded':
                                                    $payment_class = 'cancelled';
                                                    $payment_icon = 'fa-undo';
                                                    break;
                                                default:
                                                    $payment_class = 'pending';
                                                    $payment_icon = 'fa-clock';
                                            }
                                        }
                                        ?>
                                        <span class="status <?php echo $payment_class; ?>" style="display: inline-flex; align-items: center; gap: 5px;">
                                            <i class="fas <?php echo $payment_icon; ?>" style="font-size: 0.85rem;"></i>
                                            <?php echo ucfirst($payment_status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <?php if (isAdmin() || isStaff()): ?>
                                                <!-- Admin and Staff Actions -->
                                                <?php if ($booking['status'] !== 'cancelled'): ?>
                                                    <!-- Only show Edit button for non-cancelled bookings -->
                                                    <button class="btn btn-outline edit-booking-btn" 
                                                            data-booking-id="<?php echo $booking['booking_id']; ?>"
                                                            data-status="<?php echo $booking['status']; ?>">
                                                        Edit
                                                    </button>
                                                <?php endif; ?>
                                                <?php if (isAdmin()): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                        <button type="submit" name="delete_booking" class="btn btn-danger" 
                                                                onclick="return confirm('Are you sure you want to delete this booking?')">
                                                            Delete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <!-- Guest Actions -->
                                                <?php if ($payment_status === 'pending' && $booking['status'] !== 'cancelled'): ?>
                                                    <!-- Pay Now Button for Unpaid Bookings (not shown for cancelled) -->
                                                    <a href="/version2/guest/process_payment.php?booking_id=<?php echo $booking['booking_id']; ?>" 
                                                       class="btn btn-primary" 
                                                       style="background: linear-gradient(135deg, #5C2D91, #7C3AAF); border: none; display: inline-flex; align-items: center; gap: 5px;">
                                                        <i class="fas fa-credit-card"></i>
                                                        Pay Now
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($booking['status'] === 'pending' && $payment_status === 'pending'): ?>
                                                    <!-- Allow cancel only if not paid -->
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                        <button type="submit" name="cancel_booking" class="btn btn-danger" 
                                                                onclick="return confirm('Are you sure you want to cancel this booking?')">
                                                            Cancel
                                                        </button>
                                                    </form>
                                                <?php elseif ($payment_status === 'paid' && ($booking['status'] === 'pending' || $booking['status'] === 'confirmed')): ?>
                                                    <!-- If paid, show contact staff message -->
                                                    <span style="color: #6c757d; font-size: 0.85rem; font-style: italic;">
                                                        <i class="fas fa-info-circle"></i> Contact staff to cancel
                                                    </span>
                                                <?php elseif ($booking['status'] === 'checked_in' || $booking['status'] === 'checked_out'): ?>
                                                    <span style="color: #6c757d; font-size: 0.9rem;"><?php echo ucfirst($booking['status']); ?></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Edit Booking Modal (Admin and Staff Only) -->
    <?php if (isAdmin() || isStaff()): ?>
    <div id="editBookingModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Booking Status</h2>
            <form id="editBookingForm" method="POST">
                <input type="hidden" name="booking_id" id="edit_booking_id">
                <div class="form-group">
                    <label for="edit_status">Status:</label>
                    <select id="edit_status" name="status" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="checked_in">Checked In</option>
                        <option value="checked_out">Checked Out</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <button type="submit" name="update_booking" class="btn btn-primary" style="width: 100%;">Update Booking</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        <?php if (isAdmin() || isStaff()): ?>
        // Edit Booking Modal functionality
        const editModal = document.getElementById('editBookingModal');
        const editCloseBtn = editModal.querySelector('.close');
        const editButtons = document.querySelectorAll('.edit-booking-btn');

        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const bookingId = this.getAttribute('data-booking-id');
                const status = this.getAttribute('data-status');

                document.getElementById('edit_booking_id').value = bookingId;
                document.getElementById('edit_status').value = status;

                editModal.style.display = 'block';
            });
        });

        editCloseBtn.addEventListener('click', function() {
            editModal.style.display = 'none';
        });

        window.addEventListener('click', function(event) {
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>