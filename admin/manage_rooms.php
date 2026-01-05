<?php
require_once '../includes/config.php';
requireAdmin();

// Handle room actions (add, edit, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_room'])) {
        $type = trim($_POST['type']);
        $price = $_POST['price'];
        $description = trim($_POST['description']);
        $amenities = trim($_POST['amenities']);

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
        if (empty($amenities)) {
            $errors[] = "Amenities are required.";
        }

        // Handle image upload
        $image_url = null;
        if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_type = mime_content_type($_FILES['room_image']['tmp_name']);
            $file_size = $_FILES['room_image']['size'];
            
            if (!in_array($file_type, $allowed_types)) {
                $errors[] = "Invalid file type. Only JPG, PNG, and WEBP are allowed.";
            } elseif ($file_size > $max_size) {
                $errors[] = "File size too large. Maximum 5MB allowed.";
            }
        }

        if (empty($errors)) {
            // First insert the room to get the room_id
            $stmt = $pdo->prepare("INSERT INTO rooms (type, price, description, amenities) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$type, $price, $description, $amenities])) {
                $room_id = $pdo->lastInsertId();
                
                // Now upload the image if provided
                if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
                    $extension = pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION);
                    $filename = "room_{$room_id}_" . time() . ".{$extension}";
                    $upload_path = "../uploads/rooms/{$filename}";
                    
                    if (move_uploaded_file($_FILES['room_image']['tmp_name'], $upload_path)) {
                        $image_url = "/version2/uploads/rooms/{$filename}";
                        // Update the room with image_url
                        $stmt = $pdo->prepare("UPDATE rooms SET image_url = ? WHERE room_id = ?");
                        $stmt->execute([$image_url, $room_id]);
                    }
                }
                
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
        $amenities = trim($_POST['amenities']);
        $status = $_POST['status'];

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
        if (empty($amenities)) {
            $errors[] = "Amenities are required.";
        }

        // Handle image upload for edit
        if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_type = mime_content_type($_FILES['room_image']['tmp_name']);
            $file_size = $_FILES['room_image']['size'];
            
            if (!in_array($file_type, $allowed_types)) {
                $errors[] = "Invalid file type. Only JPG, PNG, and WEBP are allowed.";
            } elseif ($file_size > $max_size) {
                $errors[] = "File size too large. Maximum 5MB allowed.";
            }
        }

        if (empty($errors)) {
            // Handle new image upload
            $image_url = null;
            if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
                // Get old image to delete it
                $stmt = $pdo->prepare("SELECT image_url FROM rooms WHERE room_id = ?");
                $stmt->execute([$room_id]);
                $old_image = $stmt->fetchColumn();
                
                // Upload new image
                $extension = pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION);
                $filename = "room_{$room_id}_" . time() . ".{$extension}";
                $upload_path = "../uploads/rooms/{$filename}";
                
                if (move_uploaded_file($_FILES['room_image']['tmp_name'], $upload_path)) {
                    $image_url = "/version2/uploads/rooms/{$filename}";
                    
                    // Delete old image file if exists
                    if ($old_image && file_exists(".." . $old_image)) {
                        unlink(".." . $old_image);
                    }
                    
                    // Update with new image
                    $stmt = $pdo->prepare("UPDATE rooms SET type = ?, price = ?, description = ?, amenities = ?, status = ?, image_url = ? WHERE room_id = ?");
                    $stmt->execute([$type, $price, $description, $amenities, $status, $image_url, $room_id]);
                }
            } else {
                // Update without changing image
                $stmt = $pdo->prepare("UPDATE rooms SET type = ?, price = ?, description = ?, amenities = ?, status = ? WHERE room_id = ?");
                $stmt->execute([$type, $price, $description, $amenities, $status, $room_id]);
            }
            
            $_SESSION['success'] = "Room updated successfully!";
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
            // Get image to delete it
            $stmt = $pdo->prepare("SELECT image_url FROM rooms WHERE room_id = ?");
            $stmt->execute([$room_id]);
            $image_url = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("DELETE FROM rooms WHERE room_id = ?");
            if ($stmt->execute([$room_id])) {
                // Delete image file if exists
                if ($image_url && file_exists(".." . $image_url)) {
                    unlink(".." . $image_url);
                }
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
                <li><a href="/version2/admin/manage_staff.php"><i class="fas fa-users-cog"></i> Manage Staff</a></li>
                <li><a href="/version2/admin/manage_rooms.php" class="active"><i class="fas fa-bed"></i> Manage Rooms</a></li>
                <li><a href="/version2/bookings.php"><i class="fas fa-calendar-check"></i> All Bookings</a></li>
                <li><a href="/version2/admin/payments.php"><i class="fas fa-credit-card"></i> Payment Records</a></li>
                <li><a href="/version2/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="/version2/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
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
                <form method="POST" enctype="multipart/form-data" style="max-width: 600px;">
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
                    <div class="form-group">
                        <label for="amenities">Amenities:</label>
                        <textarea id="amenities" name="amenities" class="form-control" rows="3" required 
                                  placeholder="List room amenities (comma separated)"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="room_image">Room Image (Optional):</label>
                        <input type="file" id="room_image" name="room_image" class="form-control" accept="image/jpeg,image/jpg,image/png,image/webp">
                        <small style="color: #6c757d; font-size: 0.85rem;">Max 5MB. Formats: JPG, PNG, WEBP</small>
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
                                    <th>Image</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Description</th>
                                    <th>Amenities</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td><?php echo $room['room_id']; ?></td>
                                    <td>
                                        <?php if (!empty($room['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($room['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($room['type']); ?>" 
                                                 style="width: 100px; height: 70px; object-fit: cover; border-radius: 4px;"
                                                 onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%2270%22%3E%3Crect fill=%22%23ddd%22 width=%22100%22 height=%2270%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%23999%22 font-size=%2212%22%3ENo Image%3C/text%3E%3C/svg%3E';">
                                        <?php else: ?>
                                            <span style="color: #6c757d; font-size: 0.85rem;">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo $room['type']; ?></strong></td>
                                    <td>Rs.<?php echo number_format($room['price'], 2); ?></td>
                                    <td style="max-width: 300px;"><?php echo $room['description']; ?></td>
                                    <td style="max-width: 200px;"><?php echo $room['amenities']; ?></td>
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
                                                    data-description="<?php echo htmlspecialchars($room['description']); ?>"
                                                    data-amenities="<?php echo htmlspecialchars($room['amenities']); ?>"
                                                    data-status="<?php echo $room['status']; ?>"
                                                    data-image="<?php echo htmlspecialchars($room['image_url'] ?? ''); ?>">
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
            <form id="editForm" method="POST" enctype="multipart/form-data">
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
                <div class="form-group">
                    <label for="edit_amenities">Amenities:</label>
                    <textarea id="edit_amenities" name="amenities" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Current Image:</label>
                    <div id="current_image_preview" style="margin-bottom: 10px;">
                        <span style="color: #6c757d; font-size: 0.85rem;">No image uploaded</span>
                    </div>
                    <label for="edit_room_image">Change Image (Optional):</label>
                    <input type="file" id="edit_room_image" name="room_image" class="form-control" accept="image/jpeg,image/jpg,image/png,image/webp">
                    <small style="color: #6c757d; font-size: 0.85rem;">Max 5MB. Formats: JPG, PNG, WEBP. Leave empty to keep current image.</small>
                </div>
                <div class="form-group">
                    <label for="edit_status">Status:</label>
                    <select id="edit_status" name="status" class="form-control" required>
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>
                <button type="submit" name="edit_room" class="btn btn-primary" style="width: 100%;">Update Room</button>
            </form>
        </div>
    </div>

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
                const amenities = this.getAttribute('data-amenities');
                const status = this.getAttribute('data-status');
                const imageUrl = this.getAttribute('data-image');

                document.getElementById('edit_room_id').value = roomId;
                document.getElementById('edit_type').value = type;
                document.getElementById('edit_price').value = price;
                document.getElementById('edit_description').value = description;
                document.getElementById('edit_amenities').value = amenities;
                document.getElementById('edit_status').value = status;
                
                // Update image preview
                const imagePreview = document.getElementById('current_image_preview');
                if (imageUrl) {
                    imagePreview.innerHTML = '<img src="' + imageUrl + '" style="max-width: 200px; height: auto; border-radius: 4px;">';
                } else {
                    imagePreview.innerHTML = '<span style="color: #6c757d; font-size: 0.85rem;">No image uploaded</span>';
                }

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