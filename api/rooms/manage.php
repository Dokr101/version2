<?php
// api/rooms/manage.php
require_once '../api_helper.php';
apiRequireAdmin();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : 'add';

    if ($action === 'add' || $action === 'edit') {
        $type = trim($_POST['type'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $description = strip_tags(trim($_POST['description'] ?? ''));
        $amenities = strip_tags(trim($_POST['amenities'] ?? ''));
        $status = $_POST['status'] ?? 'available';
        
        if (empty($type) || $price <= 0 || empty($description) || empty($amenities)) {
            sendError('All fields (type, price, description, amenities) are required.');
        }

        // Image upload validation
        $image_uploaded = false;
        if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB

            $file_type = mime_content_type($_FILES['room_image']['tmp_name']);
            $file_size = $_FILES['room_image']['size'];

            if (!in_array($file_type, $allowed_types)) {
                sendError('Invalid file type. Only JPG, PNG, and WEBP are allowed.');
            }
            if ($file_size > $max_size) {
                sendError('File size too large. Maximum 5MB allowed.');
            }
            $image_uploaded = true;
        }

        if ($action === 'add') {
            // Insert room
            $stmt = $pdo->prepare("INSERT INTO rooms (type, price, description, amenities, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$type, $price, $description, $amenities, $status]);
            $room_id = $pdo->lastInsertId();

            // Handle image upload if provided
            if ($image_uploaded) {
                $extension = pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION);
                $type_folder = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $type));
                $filename = "room_{$room_id}_" . time() . ".{$extension}";

                $upload_dir = "../../uploads/rooms/{$type_folder}/";
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Path traversal check
                $real_upload = realpath('../../uploads/rooms/');
                $real_dest   = realpath($upload_dir);
                if ($real_upload === false || $real_dest === false || strpos($real_dest, $real_upload) !== 0) {
                    sendError('Invalid upload path.');
                }

                $upload_path = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['room_image']['tmp_name'], $upload_path)) {
                    $image_url = "/version2/uploads/rooms/{$type_folder}/{$filename}";
                    $stmt = $pdo->prepare("UPDATE rooms SET image_url = ? WHERE room_id = ?");
                    $stmt->execute([$image_url, $room_id]);
                } else {
                    sendSuccess('Room added, but image upload failed.', ['room_id' => $room_id]);
                }
            }

            sendSuccess('Room added successfully!', ['room_id' => $room_id]);

        } else { // edit
            $room_id = intval($_POST['room_id'] ?? 0);
            if (!$room_id) {
                sendError('Room ID is required for editing.');
            }

            // Check if room exists
            $stmt = $pdo->prepare("SELECT image_url FROM rooms WHERE room_id = ?");
            $stmt->execute([$room_id]);
            $old_image = $stmt->fetchColumn();

            if ($old_image === false) {
                sendError('Room not found.');
            }

            if ($image_uploaded) {
                $extension = pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION);
                $type_folder = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $type));
                $filename = "room_{$room_id}_" . time() . ".{$extension}";

                $upload_dir = "../../uploads/rooms/{$type_folder}/";
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Path traversal check
                $real_upload = realpath('../../uploads/rooms/');
                $real_dest   = realpath($upload_dir);
                if ($real_upload === false || $real_dest === false || strpos($real_dest, $real_upload) !== 0) {
                    sendError('Invalid upload path.');
                }

                $upload_path = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['room_image']['tmp_name'], $upload_path)) {
                    $image_url = "/version2/uploads/rooms/{$type_folder}/{$filename}";

                    // Delete old image file
                    if ($old_image && file_exists("../.." . $old_image)) {
                        unlink("../.." . $old_image);
                    }

                    $stmt = $pdo->prepare("UPDATE rooms SET type = ?, price = ?, description = ?, amenities = ?, status = ?, image_url = ? WHERE room_id = ?");
                    $stmt->execute([$type, $price, $description, $amenities, $status, $image_url, $room_id]);
                } else {
                    sendError('Image upload failed.');
                }
            } else {
                // Update without image change
                $stmt = $pdo->prepare("UPDATE rooms SET type = ?, price = ?, description = ?, amenities = ?, status = ? WHERE room_id = ?");
                $stmt->execute([$type, $price, $description, $amenities, $status, $room_id]);
            }

            sendSuccess('Room updated successfully!');
        }
    } elseif ($action === 'delete') {
        $room_id = intval($_POST['room_id'] ?? 0);
        if (!$room_id) {
            sendError('Room ID is required.');
        }

        // Check bookings
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ?");
        $stmt->execute([$room_id]);
        $booking_count = $stmt->fetchColumn();

        if ($booking_count > 0) {
            sendError('Cannot delete room with existing bookings.');
        }

        // Get image to delete it
        $stmt = $pdo->prepare("SELECT image_url FROM rooms WHERE room_id = ?");
        $stmt->execute([$room_id]);
        $image_url = $stmt->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM rooms WHERE room_id = ?");
        if ($stmt->execute([$room_id])) {
            if ($image_url && file_exists("../.." . $image_url)) {
                unlink("../.." . $image_url);
            }
            sendSuccess('Room deleted successfully!');
        } else {
            sendError('Failed to delete room.');
        }
    } else {
        sendError('Invalid action.');
    }
} else {
    sendError('Method not allowed.');
}
?>
