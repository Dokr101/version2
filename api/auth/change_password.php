<?php
// api/auth/change_password.php
require_once '../api_helper.php';

// Must be authenticated
apiRequireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$current_password = $data['current_password'] ?? '';
$new_password = $data['new_password'] ?? '';

if (empty($current_password) || empty($new_password)) {
    sendJSON(['error' => 'Both current and new passwords are required.'], 400);
}

// Fetch current user details
$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($current_password, $user['password'])) {
    sendJSON(['error' => 'Incorrect current password.'], 400);
}

// Prevent reusing current password
if (password_verify($new_password, $user['password'])) {
    sendJSON(['error' => 'New password cannot be the same as the current password.'], 400);
}

// Enforce standard password strength rules
if (strlen($new_password) < 6) {
    sendJSON(['error' => 'New password must be at least 6 characters.'], 400);
}

if (!preg_match('/[0-9]/', $new_password)) {
    sendJSON(['error' => 'New password must contain at least one number.'], 400);
}

if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
    sendJSON(['error' => 'New password must contain at least one symbol.'], 400);
}

// Hash and update the password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
    sendJSON(['success' => true, 'message' => 'Password updated successfully.']);
} else {
    sendJSON(['error' => 'Database update failed. Please try again.'], 500);
}
?>
