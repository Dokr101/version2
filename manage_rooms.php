<?php
require_once 'includes/config.php';
requireAdmin();

// Handle room actions (add, edit, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_room'])) {
        $type = trim($_POST['type']);
        $price = $_POST['price'];
        $description = trim($_POST['description']);

        // Validation
        $errors = [];
        if (empty($type)) {
            $errors[] = "Room type is required.";
        }
        if (empty($price) || $price <= 0) {
            $errors[] = "Valid price is required.";
        }
        if (empty($description)) {
            $errors[] = "Description is required.";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO rooms (type, price, description) VALUES (?, ?, ?)");
            if ($stmt->execute([$type, $price, $description])) {
                $_SESSION['success'] = "Room added successfully!";
            } else {
                $_SESSION['error'] = "Failed to add room.";
            }
        } else {
            $_SESSION['error'] = implode(" ", $errors);
        }
    } elseif (isset($_POST['edit_room'])) {
        $room_id = $_POST['room_id'];
        $type = trim($_POST['type']);
        $price = $_POST['price'];
        $description = trim($_POST['description']);

        // Validation
        $errors = [];
        if (empty($type)) {
            $errors[] = "Room type is required.";
        }
        if (empty($price) || $price <= 0) {
            $errors[] = "Valid price is required.";
        }
        if (empty($description)) {
            $errors[] = "Description is required.";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE rooms SET type = ?, price = ?, description = ? WHERE room_id = ?");
            if ($stmt->execute([$type, $price, $description, $room_id])) {
                $_SESSION['success'] = "Room updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update room.";
            }
        } else {
            $_SESSION['error'] = implode(" ", $errors);
        }
    } elseif (isset($_POST['delete_room'])) {
        $room_id = $_POST['room_id'];

        // Check if room has bookings
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ?");
        $stmt->execute([$room_id]);
        $booking_count = $stmt->fetchColumn();

        if ($booking_count > 0) {
            $_SESSION['error'] = "Cannot delete room with existing bookings.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM rooms WHERE room_id = ?");
            if ($stmt->execute([$room_id])) {
                $_SESSION['success'] = "Room deleted successfully!";
            } else {
                $_SESSION['error'] = "Failed to delete room.";
            }
        }
    }
    header("Location: manage_rooms.php");
    exit();
}

// Get all rooms
$stmt = $pdo->query("SELECT * FROM rooms ORDER BY room_id");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - HRMS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    
    
    <div class="main-content">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_rooms.php" class="active"><i class="fas fa-bed"></i> Manage Rooms</a></li>
                <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> All Bookings</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Content Area -->
        <main class="content">
            <div class="page-header">
                <h1>Manage Rooms</h1>
                <p>Add, edit, or remove rooms from the system</p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <!-- Add Room Form -->
            <section class="card">
                <div class="card-header">
                    <h2>Add New Room</h2>
                </div>
                <form method="POST" style="max-width: 600px;">
                    <div class="form-group">
                        <label for="type">Room Type:</label>
                        <input type="text" id="type" name="type" class="form-control" required 
                               placeholder="e.g., Single, Double, Suite, Deluxe">
                    </div>
                    <div class="form-group">
                        <label for="price">Price per Night (Rs.):</label>
                        <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required 
                               placeholder="Enter price per night">
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" class="form-control" rows="4" required 
                                  placeholder="Describe the room features and amenities"></textarea>
                    </div>
                    <button type="submit" name="add_room" class="btn btn-primary">Add Room</button>
                </form>
            </section>

            <!-- Rooms List -->
            <section class="card">
                <div class="card-header">
                    <h2>Existing Rooms</h2>
                    
                </div>
                
                <?php if (empty($rooms)): ?>
                    <div style="text-align: center; padding: 40px;">
                        <h3 style="color: #6c757d; margin-bottom: 15px;">No Rooms Found</h3>
                        <p style="color: #6c757d;">Add your first room using the form above.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Room ID</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Description</th>
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
                                    <td style="max-width: 300px;"><?php echo $room['description']; ?></td>
                                    <td>
                                        <span class="status <?php echo $room['status']; ?>">
                                            <?php echo ucfirst($room['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <button class="btn btn-outline edit-room-btn" 
                                                    data-room-id="<?php echo $room['room_id']; ?>"
                                                    data-type="<?php echo $room['type']; ?>"
                                                    data-price="<?php echo $room['price']; ?>"
                                                    data-description="<?php echo htmlspecialchars($room['description']); ?>">
                                                Edit
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                                <button type="submit" name="delete_room" class="btn btn-danger" 
                                                        onclick="return confirm('Are you sure you want to delete this room?')">
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

    <!-- Edit Room Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Room</h2>
            <form id="editForm" method="POST">
                <input type="hidden" name="room_id" id="edit_room_id">
                <div class="form-group">
                    <label for="edit_type">Room Type:</label>
                    <input type="text" id="edit_type" name="type" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_price">Price per Night (Rs.):</label>
                    <input type="number" id="edit_price" name="price" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description:</label>
                    <textarea id="edit_description" name="description" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" name="edit_room" class="btn btn-primary" style="width: 100%;">Update Room</button>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Edit Modal functionality
        const editModal = document.getElementById('editModal');
        const editCloseBtn = editModal.querySelector('.close');
        const editButtons = document.querySelectorAll('.edit-room-btn');

        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const roomId = this.getAttribute('data-room-id');
                const type = this.getAttribute('data-type');
                const price = this.getAttribute('data-price');
                const description = this.getAttribute('data-description');

                document.getElementById('edit_room_id').value = roomId;
                document.getElementById('edit_type').value = type;
                document.getElementById('edit_price').value = price;
                document.getElementById('edit_description').value = description;

                editModal.style.display = 'block';
            });
        });

        editCloseBtn.addEventListener('click', function() {
            editModal.style.display = 'none';
        });

        window.addEventListener('click', function(event) {
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>