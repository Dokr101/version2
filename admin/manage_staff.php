<?php
require_once 'includes/config.php';
requireAdmin();

// Handle staff actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_staff'])) {
        $user_id = $_POST['user_id'];
        
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ? AND role = 'staff'");
        if ($stmt->execute([$user_id])) {
            $_SESSION['success'] = "Staff member approved successfully!";
        } else {
            $_SESSION['error'] = "Failed to approve staff member.";
        }
    } elseif (isset($_POST['delete_staff'])) {
        $user_id = $_POST['user_id'];
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'staff'");
        if ($stmt->execute([$user_id])) {
            $_SESSION['success'] = "Staff member deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete staff member.";
        }
    }
    header("Location: manage_staff.php");
    exit();
}

// Get all staff members
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'staff' ORDER BY created_at DESC");
$staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff - Hotel MS</title>
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
                <li><a href="manage_staff.php" class="active"><i class="fas fa-users-cog"></i> Manage Staff</a></li>
                <li><a href="manage_rooms.php"><i class="fas fa-bed"></i> Manage Rooms</a></li>
                <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> All Bookings</a></li>
                <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payment Records</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="content">
            <div class="page-header">
                <h1>Manage Staff</h1>
                <p>Approve or remove staff accounts</p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <!-- Staff List -->
            <section class="card">
                <div class="card-header">
                    <h2>Staff Accounts</h2>
                </div>
                
                <?php if (empty($staff_members)): ?>
                    <div style="text-align: center; padding: 40px;">
                        <h3 style="color: #6c757d; margin-bottom: 15px;">No Staff Members Found</h3>
                        <p style="color: #6c757d;">No staff accounts have been created yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staff_members as $staff): ?>
                                <tr>
                                    <td><?php echo $staff['id']; ?></td>
                                    <td><strong><?php echo $staff['name']; ?></strong></td>
                                    <td><?php echo $staff['username']; ?></td>
                                    <td><?php echo $staff['email']; ?></td>
                                    <td><?php echo $staff['phone']; ?></td>
                                    <td>
                                        <span class="status <?php echo $staff['status']; ?>">
                                            <?php echo ucfirst($staff['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($staff['created_at'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <?php if ($staff['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $staff['id']; ?>">
                                                    <button type="submit" name="approve_staff" class="btn btn-primary">
                                                        Approve
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $staff['id']; ?>">
                                                <button type="submit" name="delete_staff" class="btn btn-danger" 
                                                        onclick="return confirm('Are you sure you want to delete this staff member?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
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
</body>
</html>