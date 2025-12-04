<?php
require_once '../includes/config.php';
requireAdmin();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_rooms FROM rooms");
$total_rooms = $stmt->fetch(PDO::FETCH_ASSOC)['total_rooms'];

$stmt = $pdo->query("SELECT COUNT(*) as total_bookings FROM bookings");
$total_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['total_bookings'];

$stmt = $pdo->query("SELECT COUNT(*) as pending_bookings FROM bookings WHERE status = 'pending'");
$pending_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['pending_bookings'];

$stmt = $pdo->query("SELECT SUM(total_price) as revenue FROM bookings WHERE status IN ('confirmed', 'checked_in', 'checked_out')");
$revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'guest'");
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as pending_staff FROM users WHERE role = 'staff' AND status = 'pending'");
$pending_staff = $stmt->fetch(PDO::FETCH_ASSOC)['pending_staff'];

// Get recent bookings
$stmt = $pdo->query("
    SELECT b.*, u.name as user_name, r.type as room_type 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN rooms r ON b.room_id = r.room_id 
    ORDER BY b.created_at DESC 
    LIMIT 5
");
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get room occupancy
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HRMS</title>
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
                <div class="logo-subtitle">Admin Panel</div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="/version2/admin/admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="/version2/admin/manage_staff.php"><i class="fas fa-users-cog"></i> Manage Staff
                    <?php if ($pending_staff > 0): ?>
                        <span style="background: #ffc107; color: #000; padding: 2px 6px; border-radius: 10px; font-size: 0.75rem; margin-left: 5px;"><?php echo $pending_staff; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="/version2/admin/manage_rooms.php"><i class="fas fa-bed"></i> Manage Rooms</a></li>
                <li><a href="/version2/bookings.php"><i class="fas fa-calendar-check"></i> All Bookings</a></li>
                <li><a href="/version2/admin/payments.php"><i class="fas fa-credit-card"></i> Payment Records</a></li>
                <li><a href="/version2/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="/version2/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="content">
            <div class="page-header">
                <h1>Admin Dashboard</h1>
                <p>Welcome, <?php echo $_SESSION['name']; ?>. Here's your system overview.</p>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_rooms; ?></div>
                    <div class="stat-label">Total Rooms</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_bookings; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $pending_bookings; ?></div>
                    <div class="stat-label">Pending Bookings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">Rs.<?php echo number_format($revenue, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Registered Guests</div>
                </div>
                <a href="/version2/admin/manage_staff.php" style="text-decoration: none; color: inherit;">
                    <div class="stat-card" style="cursor: pointer; <?php echo $pending_staff > 0 ? 'border-left: 4px solid #ffc107;' : ''; ?>">
                        <div class="stat-number" style="<?php echo $pending_staff > 0 ? 'color: #ffc107;' : ''; ?>"><?php echo $pending_staff; ?></div>
                        <div class="stat-label">
                            Pending Staff
                            <?php if ($pending_staff > 0): ?>
                                <i class="fas fa-exclamation-circle" style="color: #ffc107; margin-left: 5px;"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Recent Bookings -->
            <section class="card">
                <div class="card-header">
                    <h2>Recent Bookings</h2>
                    <a href="/version2/bookings.php" class="btn btn-outline">View All</a>
                </div>
                <?php if (empty($recent_bookings)): ?>
                    <p>No recent bookings found.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Guest</th>
                                    <th>Room Type</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['booking_id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                    <td><?php echo $booking['room_type']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking['checkin'])); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking['checkout'])); ?></td>
                                    <td>
                                        <span class="status <?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Room Status -->
            <section class="card">
                <div class="card-header">
                    <h2>Room Status</h2>
                </div>
                <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
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
        </main>
    </div>
</body>
</html>