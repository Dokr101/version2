<?php
// api/reports/summary.php
require_once '../api_helper.php';
apiRequireAdmin();

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
    WHERE status IN ('checked_in', 'checked_out') 
    AND payment_status = 'paid'
    AND DATE(created_at) >= ? AND DATE(created_at) <= ?
");
$stmt->execute([$start_date, $end_date]);
$report_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Get unique room types booked count
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT r.type) as room_types_booked
    FROM bookings b
    JOIN rooms r ON b.room_id = r.room_id
    WHERE b.status IN ('checked_in', 'checked_out') 
    AND b.payment_status = 'paid'
    AND DATE(b.created_at) >= ? AND DATE(b.created_at) <= ?
");
$stmt->execute([$start_date, $end_date]);
$room_types_booked = (int)$stmt->fetch(PDO::FETCH_ASSOC)['room_types_booked'];

// Get monthly revenue for the current year
$current_year = date('Y');
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as booking_count,
        SUM(total_price) as revenue
    FROM bookings 
    WHERE status IN ('checked_in', 'checked_out')
    AND payment_status = 'paid'
    AND YEAR(created_at) = ?
    GROUP BY month
    ORDER BY month
");
$stmt->execute([$current_year]);
$monthly_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cast types
$total_bookings = (int)($report_data['total_bookings'] ?? 0);
$total_revenue = (float)($report_data['total_revenue'] ?? 0.0);
$average_booking_value = (float)($report_data['average_booking_value'] ?? 0.0);

foreach ($monthly_revenue as &$month_data) {
    $month_data['booking_count'] = (int)$month_data['booking_count'];
    $month_data['revenue'] = (float)$month_data['revenue'];
    $month_data['average'] = $month_data['booking_count'] > 0 ? $month_data['revenue'] / $month_data['booking_count'] : 0.0;
}

sendJSON([
    'startDate' => $start_date,
    'endDate' => $end_date,
    'stats' => [
        'totalBookings' => $total_bookings,
        'totalRevenue' => $total_revenue,
        'averageBookingValue' => $average_booking_value,
        'roomTypesBooked' => $room_types_booked
    ],
    'monthlyRevenue' => $monthly_revenue
]);
?>
