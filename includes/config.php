<?php
// Load local configurations if present
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// Improved session handling
// Set secure session cookie parameters
session_set_cookie_params([
    'lifetime' => 0,            // Session cookie, expires on close
    'path' => '/',
    'domain' => '',             // Bind cookie to current host automatically
    'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'), // Set to true in production (HTTPS)
    'httponly' => true,         // Prevent JS access to session cookie (XSS protection)
    'samesite' => 'Lax'         // CSRF protection (Lax required for payment redirects)
]);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Base path configuration for assets
define('BASE_PATH', '/version2');

// Database configuration (loads from local config if defined, otherwise falls back to defaults)
if (!defined('DB_HOST')) define('DB_HOST', defined('DB_HOST_LOCAL') ? DB_HOST_LOCAL : 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', defined('DB_NAME_LOCAL') ? DB_NAME_LOCAL : 'hrms_9');
if (!defined('DB_USER')) define('DB_USER', defined('DB_USER_LOCAL') ? DB_USER_LOCAL : 'root');
if (!defined('DB_PASS')) define('DB_PASS', defined('DB_PASS_LOCAL') ? DB_PASS_LOCAL : '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper functions for authentication and authorization
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && isLoggedIn();
}

function isStaff()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'staff' && isLoggedIn() && $_SESSION['status'] === 'active';
}

function isGuest()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'guest' && isLoggedIn();
}

function requireLogin()
{
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: /version2/auth/login.php");
        exit();
    }
}

function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        header("Location: /version2/homepage.php");
        exit();
    }
}

function requireStaff()
{
    requireLogin();
    if (!isStaff()) {
        if (isAdmin()) {
            header("Location: /version2/admin/admin_dashboard.php");
        } else {
            header("Location: /version2/guest/guest_dashboard.php");
        }
        exit();
    }
}

function requireGuest()
{
    requireLogin();
    if (!isGuest()) {
        if (isAdmin()) {
            header("Location: /version2/admin/admin_dashboard.php");
        } else {
            header("Location: /version2/hotel_staff/staff_dashboard.php");
        }
        exit();
    }
}

function setMessage($type, $message)
{
    if (!isset($_SESSION['messages'])) {
        $_SESSION['messages'] = [];
    }
    $_SESSION['messages'][] = ['type' => $type, 'text' => $message];
}

function displayMessages()
{
    if (isset($_SESSION['messages']) && !empty($_SESSION['messages'])) {
        foreach ($_SESSION['messages'] as $message) {
            $escapedText = htmlspecialchars($message['text'], ENT_QUOTES, 'UTF-8');
            echo "<div class='alert alert-{$message['type']}'>{$escapedText}</div>";
        }
        unset($_SESSION['messages']);
    }
}
?>