<?php
require_once 'includes/config.php';
requireAdmin();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_rooms FROM rooms");
$total_rooms = $stmt->fetch(PDO::FETCH_ASSOC)['total_rooms'];

$stmt = $pdo->query("SELECT COUNT(*) as total_bookings FROM bookings");
$total_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['total_bookings'];

$stmt = $pdo->query("SELECT COUNT(*) as pending_bookings FROM bookings WHERE status = 'pending'");
$pending_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['pending_bookings'];

$stmt = $pdo->query("SELECT SUM(total_price) as revenue FROM bookings WHERE status = 'confirmed'");
$revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'guest'");
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

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
        SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as booked_rooms
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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    
    <div class="main-content">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_rooms.php"><i class="fas fa-bed"></i> Manage Rooms</a></li>
                <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> All Bookings</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="content">
            <div class="page-header">
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?php echo $_SESSION['name']; ?>. Here's your system overview.</p>
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
                    <div class="stat-label">Registered Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $room_stats['available_rooms'] . '/' . $room_stats['total_rooms']; ?></div>
                    <div class="stat-label">Available Rooms</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <!--<div class="card">
                <div class="card-header">
                    <h2>Quick Actions</h2>
                </div>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="manage_rooms.php" class="btn btn-primary">Manage Rooms</a>
                    <a href="bookings.php" class="btn btn-outline">View All Bookings</a>
                    <a href="reports.php" class="btn btn-outline">Generate Reports</a>
                    <a href="users.php" class="btn btn-outline">Manage Users</a>
                </div>
            </div>-->


            <!-- Recent Bookings -->
            

            <!-- Room Status -->

        </main>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>