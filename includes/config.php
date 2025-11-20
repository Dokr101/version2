<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'hotel_room_management_system');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location:/version2/auth/login.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: ../dashboard.php");
        exit();
    }
}

function setMessage($type, $message) {
    $_SESSION['messages'][] = ['type' => $type, 'text' => $message];
}

function displayMessages() {
    if (isset($_SESSION['messages'])) {
        foreach ($_SESSION['messages'] as $message) {
            echo "<div class='alert alert-{$message['type']}'>{$message['text']}</div>";
        }
        unset($_SESSION['messages']);
    }
}

// Display any stored messages
displayMessages();
?>