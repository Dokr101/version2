<?php
// api/api_helper.php
header('Content-Type: application/json');

$allowed = ['http://localhost', 'http://127.0.0.1', 'http://localhost:5173'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/config.php';

function sendJSON($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

function sendError($message, $status = 400) {
    sendJSON(['error' => $message], $status);
}

function sendSuccess($message, $extra = []) {
    sendJSON(array_merge(['success' => true, 'message' => $message], $extra));
}

function apiRequireLogin() {
    if (!isLoggedIn()) {
        sendError('Unauthorized. Please log in.', 401);
    }
}

function apiRequireAdmin() {
    apiRequireLogin();
    if (!isAdmin()) {
        sendError('Forbidden. Admin access required.', 403);
    }
}

function apiRequireStaff() {
    apiRequireLogin();
    if (!isStaff()) {
        sendError('Forbidden. Staff access required.', 403);
    }
}

function apiRequireGuest() {
    apiRequireLogin();
    if (!isGuest()) {
        sendError('Forbidden. Guest access required.', 403);
    }
}
?>
