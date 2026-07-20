<?php
// app/index.php
require_once '../includes/config.php';

// Allow public guest room browsing without login
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Allow static assets to be served directly
if (
    str_starts_with($requestPath, '/version2/app/assets/') ||
    str_ends_with($requestPath, '.js') ||
    str_ends_with($requestPath, '.css') ||
    str_ends_with($requestPath, '.svg') ||
    str_ends_with($requestPath, '.png') ||
    str_ends_with($requestPath, '.jpg') ||
    str_ends_with($requestPath, '.jpeg') ||
    str_ends_with($requestPath, '.woff2') ||
    str_ends_with($requestPath, '.ico')
) {
    return false;
}

// Public routes
$publicRoutes = [
    '/version2/app/guest/rooms',
    '/version2/app/guest/rooms/',
];

if (!isLoggedIn() && !in_array($requestPath, $publicRoutes, true)) {
    header(
        'Location: /version2/auth/login.php?redirect=' .
        urlencode($requestPath)
    );
    exit();
}

// Serve the compiled React app
readfile(__DIR__ . '/dist/index.html');