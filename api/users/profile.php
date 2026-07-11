<?php
// api/users/profile.php
require_once '../api_helper.php';
apiRequireLogin();

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT id, name, username, email, phone, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendError('User not found.');
    }
    
    sendJSON($user);

} elseif ($method === 'POST') {
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($name) || empty($email) || empty($phone)) {
        sendError('Name, email, and phone are required.');
    }
    
    // Check if email is already taken by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        sendError('Email is already in use by another account.');
    }
    
    try {
        if (!empty($password)) {
            if (strlen($password) < 6) {
                sendError('Password must be at least 6 characters.');
            }
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $hashed_password, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $user_id]);
        }
        
        // Update session name/email/phone
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        $_SESSION['phone'] = $phone;
        
        sendSuccess('Profile updated successfully!');
    } catch (Exception $e) {
        sendError('Failed to update profile: ' . $e->getMessage());
    }
} else {
    sendError('Method not allowed.');
}
?>
