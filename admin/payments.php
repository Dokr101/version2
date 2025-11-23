<?php
require_once 'includes/config.php';
requireAdmin();

// Get all payments
$stmt = $pdo->query("
    SELECT p.*, b.booking_id, b.total_price, u.name as guest_name, r.type as room_type
    FROM payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON b.room_id = r.room_id
    ORDER BY p.created_at DESC
");
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total revenue
$stmt = $pdo->query("SELECT SUM(amount) as total_revenue FROM payments WHERE status = 'completed'");
$total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Records - Hotel MS</title>
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
                <div class="logo-subtitle">Admin Panel</div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_staff.php"><i class="fas fa-users-cog"></i> Manage Staff</a></li>
                <li><a href="manage_rooms.php"><i class="fas fa-bed"></i> Manage Rooms</a></li>
                <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> All Bookings</a></li>
                <li><a href="payments.php" class="active"><i class="fas fa-credit-card"></i> Payment Records</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="content">
            <div class="page-header">
                <h1>Payment Records</h1>
                <p>View all payment transactions and revenue</p>
            </div>

            <!-- Revenue Summary -->
            <section class="card">
                <div class="card-header">
                    <h2>Revenue Summary</h2>
                </div>
                <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="stat-card">
                        <div class="stat-number">Rs.<?php echo number_format($total_revenue, 2); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($payments); ?></div>
                        <div class="stat-label">Total Transactions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">Rs.<?php echo count($payments) > 0 ? number_format($total_revenue / count($payments), 2) : 0; ?></div>
                        <div class="stat-label">Average Transaction</div>
                    </div>
                </div>
            </section>

            <!-- Payment Records -->
            <section class="card">
                <div class="card-header">
                    <h2>All Payment Records</h2>
                </div>
                <?php if (empty($payments)): ?>
                    <p>No payment records found.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Booking ID</th>
                                    <th>Guest Name</th>
                                    <th>Room Type</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>#<?php echo $payment['payment_id']; ?></td>
                                    <td>#<?php echo $payment['booking_id']; ?></td>
                                    <td><?php echo htmlspecialchars($payment['guest_name']); ?></td>
                                    <td><?php echo $payment['room_type']; ?></td>
                                    <td>Rs.<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                    <td>
                                        <span class="status <?php echo $payment['status']; ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($payment['created_at'])); ?></td>
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