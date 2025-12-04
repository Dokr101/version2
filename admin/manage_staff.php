<?php
require_once '../includes/config.php';
requireAdmin();

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_staff'])) {
        $user_id = $_POST['user_id'];
        
        // Update user status to active
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ? AND role = 'staff'");
        if ($stmt->execute([$user_id])) {
            $_SESSION['success'] = "Staff account approved successfully!";
        } else {
            $_SESSION['error'] = "Failed to approve staff account.";
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


// Get pending staff (awaiting approval)
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'staff' AND status = 'pending' ORDER BY created_at DESC");
$pending_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get active staff
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'staff' AND status = 'active' ORDER BY created_at DESC");
$active_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff - HRMS</title>
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
                <div class="logo-subtitle">Admin Panel</div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="/version2/admin/admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="/version2/admin/manage_staff.php" class="active"><i class="fas fa-users-cog"></i> Manage Staff</a></li>
                <li><a href="/version2/admin/manage_rooms.php"><i class="fas fa-bed"></i> Manage Rooms</a></li>
                <li><a href="/version2/bookings.php"><i class="fas fa-calendar-check"></i> All Bookings</a></li>
                <li><a href="/version2/admin/payments.php"><i class="fas fa-credit-card"></i> Payment Records</a></li>
                <li><a href="/version2/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="/version2/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
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


            <!-- Pending Staff Approvals -->
            <section class="card">
                <div class="card-header">
                    <h2>Pending Approvals</h2>
                    <span class="status <?php echo count($pending_staff) > 0 ? 'warning' : 'good'; ?>">
                        <?php echo count($pending_staff); ?> Pending
                    </span>
                </div>
                
                <?php if (empty($pending_staff)): ?>
                    <div style="text-align: center; padding: 30px; background: #f8f9fa; border-radius: 8px; margin: 15px;">
                        <i class="fas fa-check-circle" style="font-size: 2.5rem; color: var(--success); margin-bottom: 10px;"></i>
                        <p style="color: #6c757d; margin: 0;">No pending staff applications</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Applied On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_staff as $staff): ?>
                                <tr style="background: #fff3cd;">
                                    <td><strong><?php echo htmlspecialchars($staff['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($staff['username']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['phone']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($staff['created_at'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $staff['id']; ?>">
                                                <button type="submit" name="approve_staff" class="btn btn-primary" 
                                                        onclick="return confirm('Approve this staff member?')">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $staff['id']; ?>">
                                                <button type="submit" name="delete_staff" class="btn btn-danger" 
                                                        onclick="return confirm('Reject and delete this application permanently?')">
                                                    <i class="fas fa-times"></i> Reject
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

            <!-- Active Staff List -->
            <section class="card">
                <div class="card-header">
                    <h2>Active Staff Members</h2>
                    <span class="status good"><?php echo count($active_staff); ?> Active</span>
                </div>
                
                <?php if (empty($active_staff)): ?>
                    <div style="text-align: center; padding: 30px;">
                        <p style="color: #6c757d;">No active staff members yet.</p>
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
                                <?php foreach ($active_staff as $staff): ?>
                                <tr>
                                    <td><?php echo $staff['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($staff['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($staff['username']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['phone']); ?></td>
                                    <td>
                                        <span class="status active">
                                            <?php echo ucfirst($staff['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($staff['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $staff['id']; ?>">
                                            <button type="submit" name="delete_staff" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Are you sure you want to delete this staff member?')">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </form>
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