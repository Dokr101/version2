<?php
require_once 'includes/config.php';
requireAdmin();

// Set default date range (last 30 days)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get report data
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(total_price) as total_revenue,
        AVG(total_price) as average_booking_value
    FROM bookings 
    WHERE status = 'confirmed' 
    AND created_at BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$report_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Get bookings by room type
$stmt = $pdo->prepare("
    SELECT r.type, COUNT(b.booking_id) as booking_count, SUM(b.total_price) as revenue
    FROM bookings b
    JOIN rooms r ON b.room_id = r.room_id
    WHERE b.status = 'confirmed' 
    AND b.created_at BETWEEN ? AND ?
    GROUP BY r.type
    ORDER BY revenue DESC
");
$stmt->execute([$start_date, $end_date]);
$room_type_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get monthly revenue for the current year
$current_year = date('Y');
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as booking_count,
        SUM(total_price) as revenue
    FROM bookings 
    WHERE status = 'confirmed'
    AND YEAR(created_at) = ?
    GROUP BY month
    ORDER BY month
");
$stmt->execute([$current_year]);
$monthly_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get booking status distribution
$stmt = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM bookings 
    GROUP BY status
");
$status_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - HRMS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    
    <div class="main-content">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_rooms.php"><i class="fas fa-bed"></i> Manage Rooms</a></li>
                <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> All Bookings</a></li>
                <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="content">
            <div class="page-header">
                <h1>Reports & Analytics</h1>
                <p>Comprehensive overview of hotel performance and booking statistics</p>
            </div>

            <!-- Date Filter -->
            <section class="card">
                <div class="card-header">
                    <h2>Filter by Date Range</h2>
                </div>
                <form method="GET" style="display: flex; gap: 20px; flex-wrap: wrap; align-items: end;">
                    <div class="form-group" style="flex: 1; min-width: 200px;">
                        <label for="start_date">Start Date:</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 200px;">
                        <label for="end_date">End Date:</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                        <a href="reports.php" class="btn btn-outline">Reset</a>
                    </div>
                </form>
            </section>

            <!-- Summary Statistics -->
            <section class="card">
                <div class="card-header">
                    <h2>Summary Statistics</h2>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $report_data['total_bookings'] ?? 0; ?></div>
                        <div class="stat-label">Confirmed Bookings</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">Rs.<?php echo number_format($report_data['total_revenue'] ?? 0, 2); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">Rs.<?php echo number_format($report_data['average_booking_value'] ?? 0, 2); ?></div>
                        <div class="stat-label">Average Booking Value</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($room_type_data); ?></div>
                        <div class="stat-label">Room Types Booked</div>
                    </div>
                </div>
            </section>

            <!-- Room Type Performance -->
            <section class="card">
                <div class="card-header">
                    <h2>Room Type Performance</h2>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Room Type</th>
                                <th>Number of Bookings</th>
                                <th>Revenue</th>
                                <th>Average Revenue per Booking</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($room_type_data as $data): ?>
                            <tr>
                                <td><strong><?php echo $data['type']; ?></strong></td>
                                <td><?php echo $data['booking_count']; ?></td>
                                <td>Rs.<?php echo number_format($data['revenue'], 2); ?></td>
                                <td>Rs.<?php echo number_format($data['revenue'] / $data['booking_count'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($room_type_data)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #6c757d; padding: 20px;">
                                        No booking data available for the selected period.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Monthly Revenue -->
            <section class="card">
                <div class="card-header">
                    <h2>Monthly Revenue - <?php echo $current_year; ?></h2>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Bookings</th>
                                <th>Revenue</th>
                                <th>Average per Booking</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_revenue as $data): ?>
                            <tr>
                                <td><?php echo date('F Y', strtotime($data['month'] . '-01')); ?></td>
                                <td><?php echo $data['booking_count']; ?></td>
                                <td>Rs.<?php echo number_format($data['revenue'], 2); ?></td>
                                <td>Rs.<?php echo number_format($data['revenue'] / $data['booking_count'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($monthly_revenue)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #6c757d; padding: 20px;">
                                        No revenue data available for <?php echo $current_year; ?>.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Booking Status Distribution -->
            <section class="card">
                <div class="card-header">
                    <h2>Booking Status Distribution</h2>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <?php foreach ($status_distribution as $status): ?>
                    <div style="text-align: center; padding: 20px;">
                        <div style="font-size: 2rem; font-weight: bold; color: 
                            <?php 
                            switch($status['status']) {
                                case 'confirmed': echo '#28a745'; break;
                                case 'pending': echo '#ffc107'; break;
                                case 'cancelled': echo '#dc3545'; break;
                                default: echo '#6c757d';
                            }
                            ?>;">
                            <?php echo $status['count']; ?>
                        </div>
                        <div style="color: #6c757d; text-transform: capitalize;"><?php echo $status['status']; ?> Bookings</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Export Options -->
            <!--<section class="card">
                <div class="card-header">
                    <h2>Export Data</h2>
                </div>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <button class="btn btn-outline" onclick="exportToCSV()">
                        <i class="fas fa-download"></i> Export to CSV
                    </button>
                    <button class="btn btn-outline" onclick="printReport()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </section>-->
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        function exportToCSV() {
            alert('CSV export functionality would be implemented here. This would generate a CSV file with all report data.');
            // In a real implementation, this would make an AJAX call to generate and download a CSV file
        }

        function printReport() {
            window.print();
        }

        // Set maximum end date to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('end_date').max = today;
            document.getElementById('start_date').max = today;
        });
    </script>
</body>
</html>