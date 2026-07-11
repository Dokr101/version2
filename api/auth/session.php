<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';

if (isLoggedIn()) {
    echo json_encode([
        'isLoggedIn' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'name' => $_SESSION['name'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'],
            'phone' => $_SESSION['phone'] ?? null
        ]
    ]);
} else {
    echo json_encode([
        'isLoggedIn' => false
    ]);
}
?>
