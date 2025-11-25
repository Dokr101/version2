<?php
require_once '../includes/config.php';
requireLogin();
requireGuest();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Error - HRMS</title>
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
                <div class="logo-subtitle"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="/version2/guest/guest_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="/version2/guest/rooms.php"><i class="fas fa-bed"></i> Book Rooms</a></li>
                <li><a href="/version2/bookings.php" class="active"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
                <li><a href="/version2/guest/profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="/version2/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="content">
            <div class="card" style="max-width: 600px; margin: 100px auto; text-align: center; padding: 60px 40px;">
                <div style="font-size: 4rem; color: #dc3545; margin-bottom: 20px;">
                    <i class="fas fa-times-circle"></i>
                </div>
                
                <h1 style="color: #dc3545; margin-bottom: 15px;">Payment Failed</h1>
                
                <p style="color: var(--secondary); font-size: 1.1rem; line-height: 1.6; margin-bottom: 30px;">
                    Unfortunately, your payment could not be processed. This may be due to:
                </p>
                
                <ul style="text-align: left; color: var(--secondary); margin-bottom: 30px; line-height: 2;">
                    <li>Insufficient balance in your account</li>
                    <li>Payment gateway timeout</li>
                    <li>Network connection issues</li>
                    <li>Cancelled payment</li>
                </ul>
                
                <p style="color: var(--secondary); margin-bottom: 30px;">
                    Your booking is still reserved. You can try payment again from the bookings page.
                </p>
                
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <a href="/version2/bookings.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Bookings
                    </a>
                    <a href="/version2/guest/guest_dashboard.php" class="btn btn-outline">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
