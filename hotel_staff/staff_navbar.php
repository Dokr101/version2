<?php
// staff_navbar.php
require_once '../includes/config.php';
requireStaff();
?>

<!-- Staff Navigation -->
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
        <li><a href="/version2/hotel_staff/staff_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'staff_dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a></li>
        
        <li><a href="/version2/hotel_staff/staff_rooms.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'staff_rooms.php' ? 'active' : ''; ?>">
            <i class="fas fa-bed"></i> Assigned Rooms
        </a></li>
        
        <li><a href="/version2/hotel_staff/staff_checkin.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'staff_checkin.php' ? 'active' : ''; ?>">
            <i class="fas fa-sign-in-alt"></i> Check-in
        </a></li>
        
        <li><a href="/version2/hotel_staff/staff_checkout.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'staff_checkout.php' ? 'active' : ''; ?>">
            <i class="fas fa-sign-out-alt"></i> Check-out
        </a></li>
        
        <li><a href="/version2/hotel_staff/staff_reservations.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'staff_reservations.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i> Reservations
        </a></li>
        
        
        <li><a href="/version2/auth/logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a></li>
    </ul>
</aside>