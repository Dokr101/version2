<?php
require_once '../includes/config.php';
requireStaff();

// Handle room status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room_status'])) {
    $room_id = $_POST['room_id'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE rooms SET status = ? WHERE room_id = ?");
    if ($stmt->execute([$status, $room_id])) {
        $_SESSION['success'] = "Room status updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update room status.";
    }
    header("Location: staff_rooms.php");
    exit();
}

// Get all rooms with their current status and bookings
$stmt = $pdo->query("
    SELECT r.*, 
           b.booking_id,
           b.status as booking_status,
           u.name as guest_name,
           b.checkin,
           b.checkout
    FROM rooms r
    LEFT JOIN bookings b ON r.room_id = b.room_id AND b.status IN ('confirmed', 'checked_in')
    LEFT JOIN users u ON b.user_id = u.id
    ORDER BY r.room_id
");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Rooms - Hotel MS</title>
    <link rel="stylesheet" href="/version2/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="main-content">
        <?php include 'staff_navbar.php'; ?>

        <!-- Content Area -->
        <main class="content">
            <div class="page-header">
                <h1>Assigned Rooms</h1>
                <p>View and manage room statuses</p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <!-- Room Status Overview -->
            <section class="card">
                <div class="card-header">
                    <h2>Room Status Overview</h2>
                </div>
                <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                    <?php
                    $available = array_filter($rooms, function($room) { return $room['status'] == 'available'; });
                    $occupied = array_filter($rooms, function($room) { return $room['status'] == 'occupied'; });
                    $unavailable = array_filter($rooms, function($room) { return $room['status'] == 'unavailable'; });
                    ?>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($available); ?></div>
                        <div class="stat-label">Available</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($occupied); ?></div>
                        <div class="stat-label">Occupied</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($unavailable); ?></div>
                        <div class="stat-label">Unavailable</div>
                    </div>
                </div>
            </section>

            <!-- Rooms List -->
            <section class="card">
                <div class="card-header">
                    <h2>All Rooms</h2>
                </div>
                
                <?php if (empty($rooms)): ?>
                    <div style="text-align: center; padding: 40px;">
                        <h3 style="color: #6c757d; margin-bottom: 15px;">No Rooms Found</h3>
                        <p style="color: #6c757d;">No rooms are currently assigned to you.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Room ID</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Current Guest</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td><?php echo $room['room_id']; ?></td>
                                    <td><strong><?php echo $room['type']; ?></strong></td>
                                    <td>Rs.<?php echo number_format($room['price'], 2); ?></td>
                                    <td>
                                        <?php if (!empty($room['guest_name'])): ?>
                                            <?php echo htmlspecialchars($room['guest_name']); ?>
                                        <?php else: ?>
                                            <span style="color: #6c757d;">No guest</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($room['checkin'])): ?>
                                            <?php echo date('M j, Y', strtotime($room['checkin'])); ?>
                                        <?php else: ?>
                                            <span style="color: #6c757d;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($room['checkout'])): ?>
                                            <?php echo date('M j, Y', strtotime($room['checkout'])); ?>
                                        <?php else: ?>
                                            <span style="color: #6c757d;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status <?php echo $room['status']; ?>">
                                            <?php echo ucfirst($room['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-outline update-status-btn" 
                                                data-room-id="<?php echo $room['room_id']; ?>"
                                                data-current-status="<?php echo $room['status']; ?>">
                                            Update Status
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

    <!-- Update Status Modal -->
    <div id="statusModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Update Room Status</h2>
            <form id="statusForm" method="POST">
                <input type="hidden" name="room_id" id="status_room_id">
                
                <div class="form-group">
                    <label for="status">Room Status:</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>
                
                <button type="submit" name="update_room_status" class="btn btn-primary" style="width: 100%;">
                    Update Status
                </button>
            </form>
        </div>
    </div>

    <script>
        // Status Modal functionality
        const statusModal = document.getElementById('statusModal');
        const statusCloseBtn = statusModal.querySelector('.close');
        const statusButtons = document.querySelectorAll('.update-status-btn');

        statusButtons.forEach(button => {
            button.addEventListener('click', function() {
                const roomId = this.getAttribute('data-room-id');
                const currentStatus = this.getAttribute('data-current-status');

                document.getElementById('status_room_id').value = roomId;
                document.getElementById('status').value = currentStatus;

                statusModal.style.display = 'block';
            });
        });

        statusCloseBtn.addEventListener('click', function() {
            statusModal.style.display = 'none';
        });

        window.addEventListener('click', function(event) {
            if (event.target === statusModal) {
                statusModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>