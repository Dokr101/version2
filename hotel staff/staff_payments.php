<?php
require_once 'includes/config.php';
requireStaff();

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $booking_id = $_POST['booking_id'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Update booking payment status
        $stmt = $pdo->prepare("UPDATE bookings SET payment_status = 'paid' WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        
        // Insert payment record
        $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, payment_method, status) VALUES (?, ?, ?, 'completed')");
        $stmt->execute([$booking_id, $amount, $payment_method]);
        
        $pdo->commit();
        $_SESSION['success'] = "Payment processed successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to process payment: " . $e->getMessage();
    }
    
    header("Location: staff_payments.php");
    exit();
}

// Get bookings with pending payments
$stmt = $pdo->prepare("
    SELECT b.*, u.name as guest_name, r.type as room_type
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN rooms r ON b.room_id = r.room_id 
    WHERE b.payment_status = 'pending' AND b.status IN ('confirmed', 'checked_in')
    ORDER BY b.created_at DESC
");
$stmt->execute();
$pending_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Payments - Hotel MS</title>
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
                <li><a href="staff_reservations.php"><i class="fas fa-calendar-check"></i> Reservations</a></li>
                <li><a href="staff_payments.php" class="active"><i class="fas fa-credit-card"></i> Process Payments</a></li>
                <li><a href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="content">
            <div class="page-header">
                <h1>Process Payments</h1>
                <p>Manage guest payments and invoices</p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <!-- Pending Payments -->
            <section class="card">
                <div class="card-header">
                    <h2>Pending Payments</h2>
                </div>
                <?php if (empty($pending_payments)): ?>
                    <p>No pending payments found.</p>
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
                                    <th>Total Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_payments as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['booking_id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                                    <td><?php echo $booking['room_type']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking['checkin'])); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking['checkout'])); ?></td>
                                    <td>Rs.<?php echo number_format($booking['total_price'], 2); ?></td>
                                    <td>
                                        <button class="btn btn-primary process-payment-btn" 
                                                data-booking-id="<?php echo $booking['booking_id']; ?>"
                                                data-amount="<?php echo $booking['total_price']; ?>"
                                                data-guest-name="<?php echo htmlspecialchars($booking['guest_name']); ?>">
                                            Process Payment
                                        </button>
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

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Process Payment</h2>
            <form id="paymentForm" method="POST">
                <input type="hidden" name="booking_id" id="payment_booking_id">
                <input type="hidden" name="amount" id="payment_amount">
                
                <div class="form-group">
                    <label for="guest_name">Guest Name:</label>
                    <input type="text" id="guest_name" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label for="amount_display">Amount (Rs.):</label>
                    <input type="text" id="amount_display" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label for="payment_method">Payment Method:</label>
                    <select id="payment_method" name="payment_method" class="form-control" required>
                        <option value="">Select Payment Method</option>
                        <option value="cash">Cash</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="debit_card">Debit Card</option>
                        <option value="digital_wallet">Digital Wallet</option>
                    </select>
                </div>
                
                <button type="submit" name="process_payment" class="btn btn-primary" style="width: 100%;">Confirm Payment</button>
            </form>
        </div>
    </div>

    <script>
        // Payment Modal functionality
        const paymentModal = document.getElementById('paymentModal');
        const paymentCloseBtn = paymentModal.querySelector('.close');
        const processPaymentBtns = document.querySelectorAll('.process-payment-btn');

        processPaymentBtns.forEach(button => {
            button.addEventListener('click', function() {
                const bookingId = this.getAttribute('data-booking-id');
                const amount = this.getAttribute('data-amount');
                const guestName = this.getAttribute('data-guest-name');

                document.getElementById('payment_booking_id').value = bookingId;
                document.getElementById('payment_amount').value = amount;
                document.getElementById('amount_display').value = amount;
                document.getElementById('guest_name').value = guestName;

                paymentModal.style.display = 'block';
            });
        });

        paymentCloseBtn.addEventListener('click', function() {
            paymentModal.style.display = 'none';
        });

        window.addEventListener('click', function(event) {
            if (event.target === paymentModal) {
                paymentModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>