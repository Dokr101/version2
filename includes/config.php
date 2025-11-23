<?php
// Improved session handling
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Base path configuration for assets
define('BASE_PATH', '/version2');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'HRMS_9');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper functions for authentication and authorization
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && isLoggedIn();
}

function isStaff() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'staff' && isLoggedIn() && $_SESSION['status'] === 'active';
}

function isGuest() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'guest' && isLoggedIn();
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: auth/login.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: ../index.php");
        exit();
    }
}

function requireStaff() {
    requireLogin();
    if (!isStaff()) {
        if (isAdmin()) {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: guest_dashboard.php");
        }
        exit();
    }
}

function requireGuest() {
    requireLogin();
    if (!isGuest()) {
        if (isAdmin()) {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: staff_dashboard.php");
        }
        exit();
    }
}

function setMessage($type, $message) {
    if (!isset($_SESSION['messages'])) {
        $_SESSION['messages'] = [];
    }
    $_SESSION['messages'][] = ['type' => $type, 'text' => $message];
}

function displayMessages() {
    if (isset($_SESSION['messages']) && !empty($_SESSION['messages'])) {
        foreach ($_SESSION['messages'] as $message) {
            echo "<div class='alert alert-{$message['type']}'>{$message['text']}</div>";
        }
        unset($_SESSION['messages']);
    }
}

// Display any stored messages
displayMessages();
?>