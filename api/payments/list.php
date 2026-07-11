<?php
// api/payments/list.php
require_once '../api_helper.php';
apiRequireAdmin();

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

// Calculate total revenue from completed payments
$stmt = $pdo->query("SELECT SUM(amount) as total_revenue FROM payments WHERE status = 'completed'");
$total_revenue = (float)($stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0);

// Format payments
foreach ($payments as &$payment) {
    $payment['payment_id'] = (int)$payment['payment_id'];
    $payment['booking_id'] = (int)$payment['booking_id'];
    $payment['amount'] = (float)$payment['amount'];
    $payment['total_price'] = (float)$payment['total_price'];
}

sendJSON([
    'payments' => $payments,
    'stats' => [
        'totalRevenue' => $total_revenue,
        'totalTransactions' => count($payments),
        'averageTransaction' => count($payments) > 0 ? $total_revenue / count($payments) : 0.0
    ]
]);
?>
