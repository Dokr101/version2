<?php
// app/index.php
require_once '../includes/config.php';

// Allow public guest room browsing without login
$publicRoutes = [
    '/version2/app/guest/rooms',
    '/version2/app/guest/rooms/',
];
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (!isLoggedIn() && !in_array($requestPath, $publicRoutes, true)) {
    $loginRedirect = '/version2/auth/login.php?redirect=' . urlencode($requestPath);
    header("Location: " . $loginRedirect);
    exit();
}

// Serve the built index.html
if (file_exists('dist/index.html')) {
    // Read and output the index.html
    $html = file_get_contents('dist/index.html');
    echo $html;
} else {
    // Development mode helper
    echo '<div style="font-family: sans-serif; text-align: center; margin-top: 100px; padding: 20px;">';
    echo '<h1 style="color: #5C2D91;">Hotel Room Management System</h1>';
    echo '<h2>React Application (Vite)</h2>';
    echo '<p style="color: #666; font-size: 1.1rem; max-width: 600px; margin: 20px auto;">';
    echo 'The production build is not found. If you are in development, you can run the Vite dev server. ';
    echo 'To build the application, run the following command in your terminal:';
    echo '</p>';
    echo '<pre style="background: #f4f4f4; padding: 15px; border-radius: 5px; display: inline-block; font-size: 1.1rem;">cd app && npm run build</pre>';
    echo '<p style="margin-top: 30px;"><a href="http://localhost:5173" style="background: #5C2D91; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">Go to Vite Dev Server (http://localhost:5173)</a></p>';
    echo '</div>';
}
?>
