<?php
require_once '../includes/config.php';
requireLogin();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];
    
    // Validate name (letters only)
    if (empty($name)) {
        $errors[] = "Name is required.";
    } elseif (preg_match('/[0-9]/', $name)) {
        $errors[] = "Name should contain only letters.";
    }
    
    // Validate phone (10 digits)
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = "Phone number must be exactly 10 digits.";
    }
    
    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // Check if email already exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $errors[] = "Email already exists.";
        }
    }
    
    // Validate password change if provided
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to change password.";
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($current_password, $user['password'])) {
                $errors[] = "Current password is incorrect.";
            } elseif (strlen($new_password) < 6) {
                $errors[] = "New password must be at least 6 characters.";
            } elseif (!preg_match('/[0-9]/', $new_password) || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
                $errors[] = "New password must contain at least one number and one symbol.";
            } elseif ($new_password !== $confirm_password) {
                $errors[] = "New passwords do not match.";
            }
        }
    }

    if (empty($errors)) {
        if (!empty($new_password)) {
            // Update with new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
            $success = $stmt->execute([$name, $email, $phone, $hashed_password, $_SESSION['user_id']]);
        } else {
            // Update without changing password
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
            $success = $stmt->execute([$name, $email, $phone, $_SESSION['user_id']]);
        }
        
        if ($success) {
            // Update session
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['success'] = "Profile updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update profile.";
        }
        header("Location: profile.php");
        exit();
    } else {
        $_SESSION['error'] = implode(" ", $errors);
    }
}

// Get current user data
$stmt = $pdo->prepare("SELECT name, username, email, phone, role, status, created_at FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - HRMS</title>
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
                <div class="logo-subtitle"><?php echo isAdmin() ? 'Admin Panel' : htmlspecialchars($_SESSION['name']); ?></div>
            </div>
            <ul class="sidebar-menu">
                <?php if (isAdmin()): ?>
                    <li><a href="/version2/admin/admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="/version2/admin/manage_staff.php"><i class="fas fa-users-cog"></i> Manage Staff</a></li>
                    <li><a href="/version2/admin/manage_rooms.php"><i class="fas fa-bed"></i> Manage Rooms</a></li>
                    <li><a href="/version2/bookings.php"><i class="fas fa-calendar-check"></i> All Bookings</a></li>
                    <li><a href="/version2/admin/payments.php"><i class="fas fa-credit-card"></i> Payment Records</a></li>
                    <li><a href="/version2/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <?php elseif (isStaff()): ?>
                    <li><a href="/version2/hotel_staff/staff_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="/version2/hotel_staff/staff_checkin.php"><i class="fas fa-sign-in-alt"></i> Check-in</a></li>
                    <li><a href="/version2/hotel_staff/staff_checkout.php"><i class="fas fa-sign-out-alt"></i> Check-out</a></li>
                    <li><a href="/version2/hotel_staff/staff_reservations.php"><i class="fas fa-calendar-check"></i> Reservations</a></li>
                    <li><a href="/version2/hotel_staff/staff_payments.php"><i class="fas fa-credit-card"></i> Process Payments</a></li>
                    <li><a href="/version2/bookings.php"><i class="fas fa-calendar-check"></i> All Bookings</a></li>
                <?php else: ?>
                    <li><a href="/version2/guest/guest_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="/version2/guest/rooms.php"><i class="fas fa-bed"></i> Book Rooms</a></li>
                    <li><a href="/version2/bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
                    <li><a href="/version2/guest/profile.php" class="active"><i class="fas fa-user"></i> Profile</a></li>
                <?php endif; ?>
                <li><a href="/version2/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="content">
            <div class="page-header">
                <h1>My Profile</h1>
                <p>Manage your account information and preferences</p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>Profile Information</h2>
                </div>
                <form method="POST" style="max-width: 600px;">
                    <div class="form-group">
                        <label for="name">Full Name:</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($user['name']); ?>" required pattern="[A-Za-z\s]+" title="Name should contain only letters">
                        <small class="form-text">Only letters and spaces allowed</small>
                    </div>

                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" class="form-control" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                        <small class="form-text">Username cannot be changed</small>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number:</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($user['phone']); ?>" required pattern="[0-9]{10}" title="Phone number must be exactly 10 digits">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address:</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Role:</label>
                        <p style="color: #6c757d; margin: 5px 0;">
                            <?php echo ucfirst($user['role']); ?>
                            <?php if ($user['status'] === 'pending'): ?>
                                <span class="status pending" style="margin-left: 10px;">Pending Approval</span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="form-group">
                        <label>Member Since:</label>
                        <p style="color: #6c757d; margin: 5px 0;">
                            <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                        </p>
                    </div>

                    <hr style="margin: 30px 0; border: none; border-top: 1px solid #e9ecef;">

                    <h3 style="margin-bottom: 20px; color: var(--primary);">Change Password</h3>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password:</label>
                        <input type="password" id="current_password" name="current_password" class="form-control">
                        <small style="color: #6c757d;">Leave blank to keep current password</small>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" minlength="6">
                        <small style="color: #6c757d;">Minimum 6 characters with one number and one symbol</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password validation
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            function validatePassword() {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity("Passwords don't match");
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
            
            newPassword.addEventListener('change', validatePassword);
            confirmPassword.addEventListener('keyup', validatePassword);
        });
    </script>
</body>
</html>