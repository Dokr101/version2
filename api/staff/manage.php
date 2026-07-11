<?php
// api/staff/manage.php
require_once '../api_helper.php';
apiRequireAdmin();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Read JSON input or POST fields
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $action = $input['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($input['name'] ?? '');
        $username = trim($input['username'] ?? '');
        $email = trim($input['email'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $password = $input['password'] ?? '';
        
        if (empty($name) || empty($username) || empty($email) || empty($phone) || empty($password)) {
            sendError('All fields (name, username, email, phone, password) are required.');
        }
        
        if (strlen($password) < 6) {
            sendError('Password must be at least 6 characters.');
        }
        
        // Check if email or username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            sendError('Email or username already exists.');
        }
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (name, username, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, 'staff', 'active')");
        if ($stmt->execute([$name, $username, $email, $phone, $hashed_password])) {
            sendSuccess('Staff member added successfully!');
        } else {
            sendError('Failed to add staff member.');
        }
        
    } elseif ($action === 'approve') {
        $user_id = intval($input['user_id'] ?? 0);
        if (!$user_id) {
            sendError('User ID is required.');
        }
        
        // Fetch staff info before update
        $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ? AND role = 'staff'");
        $stmt->execute([$user_id]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$staff) {
            sendError('Staff member not found.');
        }

        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ? AND role = 'staff'");
        if ($stmt->execute([$user_id])) {
            // Trigger activation email
            require_once '../../includes/email_helper.php';
            $subject = 'Account Activated – HRMS';
            $content = "Dear <strong>" . htmlspecialchars($staff['name']) . "</strong>,<br><br>";
            $content .= "Your staff account on the HRMS Portal has been approved and activated.<br><br>";
            $content .= "You can now log in using your registered credentials.<br><br>";
            $content .= "Thank you,<br>HRMS Administration";

            $body = buildEmailTemplate('Account Activated', $content);
            sendNotificationEmail($staff['email'], $subject, $body);

            sendSuccess('Staff member approved successfully!');
        } else {
            sendError('Failed to approve staff member.');
        }
        
    } elseif ($action === 'delete') {
        $user_id = intval($input['user_id'] ?? 0);
        if (!$user_id) {
            sendError('User ID is required.');
        }
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'staff'");
        if ($stmt->execute([$user_id])) {
            sendSuccess('Staff member removed successfully!');
        } else {
            sendError('Failed to remove staff member.');
        }
    } else {
        sendError('Invalid action.');
    }
} else {
    sendError('Method not allowed.');
}
?>
