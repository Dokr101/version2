<?php
require_once '../includes/config.php';
requireStaff();

// Get today's check-ins and check-outs
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT b.*, u.name as guest_name, r.type as room_type 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN rooms r ON b.room_id = r.room_id 
    WHERE b.checkin = ? AND b.status IN ('confirmed', 'checked_in')
    ORDER BY b.checkin
");
$stmt->execute([$today]);
$today_checkins = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT b.*, u.name as guest_name, r.type as room_type 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN rooms r ON b.room_id = r.room_id 
    WHERE b.checkout = ? AND b.status IN ('checked_in', 'checked_out')
    ORDER BY b.checkout
");
$stmt->execute([$today]);
$today_checkouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending reservations
$stmt = $pdo->query("
    SELECT b.*, u.name as guest_name, r.type as room_type 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN rooms r ON b.room_id = r.room_id 
    WHERE b.status = 'pending'
    ORDER BY b.created_at DESC 
    LIMIT 5
");
$pending_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current occupied rooms
$stmt = $pdo->query("
    SELECT COUNT(*) as occupied_rooms 
    FROM bookings 
    WHERE status = 'checked_in'
");
$occupied_rooms = $stmt->fetch(PDO::FETCH_ASSOC)['occupied_rooms'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - HRMS</title>
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
                <div class="logo-subtitle" style="font-weight: bold; font-size: 0.75rem; opacity: 0.9; margin-bottom: 3px;">Hotel Staff</div>
                <div class="logo-subtitle"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="/version2/hotel_staff/staff_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="/version2/hotel_staff/staff_checkin.php"><i class="fas fa-sign-in-alt"></i> Check-in</a></li>
                <li><a href="/version2/hotel_staff/staff_checkout.php"><i class="fas fa-sign-out-alt"></i> Check-out</a></li>
                <li><a href="/version2/hotel_staff/staff_reservations.php"><i class="fas fa-calendar-check"></i> Reservations</a></li>
                <li><a href="/version2/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="content">
            <div class="page-header">
                <h1>Staff Dashboard</h1>
                <p>Welcome, <?php echo $_SESSION['name']; ?>. Today's operations overview.</p>
            </div>
            
            <!-- Quick Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($today_checkins); ?></div>
                    <div class="stat-label">Today's Check-ins</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($today_checkouts); ?></div>
                    <div class="stat-label">Today's Check-outs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($pending_reservations); ?></div>
                    <div class="stat-label">Pending Reservations</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $occupied_rooms; ?></div>
                    <div class="stat-label">Occupied Rooms</div>
                </div>
            </div>

            <!-- Today's Check-ins -->
            <section class="card">
                <div class="card-header">
                    <h2>Today's Check-ins</h2>
                    <a href="/version2/hotel_staff/staff_checkin.php" class="btn btn-outline">Manage Check-ins</a>
                </div>
                <?php if (empty($today_checkins)): ?>
                    <p>No check-ins scheduled for today.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Guest Name</th>
                                    <th>Room Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($today_checkins as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['booking_id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                                    <td><?php echo $booking['room_type']; ?></td>
                                    <td>
                                        <span class="status <?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($booking['status'] === 'confirmed'): ?>
                                            <form method="POST" action="/version2/hotel_staff/staff_checkin.php" style="display: inline;">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <button type="submit" name="checkin" class="btn btn-primary">Check-in</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>


            <!-- Room Status Overview -->
<section class="card">
    <div class="card-header">
        <h2>Room Status</h2>
        <a href="/version2/hotel_staff/staff_rooms.php" class="btn btn-outline">Manage Rooms</a>
    </div>
    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
        <?php
        // Get room statistics
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_rooms,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_rooms,
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_rooms,
                SUM(CASE WHEN status = 'unavailable' THEN 1 ELSE 0 END) as unavailable_rooms
            FROM rooms
        ");
        $room_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        <div class="stat-card">
            <div class="stat-number"><?php echo $room_stats['available_rooms']; ?></div>
            <div class="stat-label">Available</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $room_stats['occupied_rooms']; ?></div>
            <div class="stat-label">Occupied</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $room_stats['unavailable_rooms']; ?></div>
            <div class="stat-label">Unavailable</div>
        </div>
    </div>
</section>

            <!-- Pending Reservations -->
            <section class="card">
                <div class="card-header">
                    <h2>Pending Reservations</h2>
                    <a href="/version2/hotel_staff/staff_reservations.php" class="btn btn-outline">View All</a>
                </div>
                <?php if (empty($pending_reservations)): ?>
                    <p>No pending reservations.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Guest Name</th>
                                    <th>Room Type</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_reservations as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['booking_id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                                    <td><?php echo $booking['room_type']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking['checkin'])); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking['checkout'])); ?></td>
                                    <td>
                                        <form method="POST" action="/version2/hotel_staff/staff_reservations.php" style="display: inline;">
                                            <input type="hidden" name="confirm_reservation" value="<?php echo $booking['booking_id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm">Confirm</button>
                                        </form>
                                        <form method="POST" action="/version2/hotel_staff/staff_reservations.php" style="display: inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                            <button type="submit" name="cancel" class="btn btn-danger">Cancel</button>
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