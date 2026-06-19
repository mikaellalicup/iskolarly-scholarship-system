<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enable CORS for all requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the request URI
$request_uri = $_SERVER['REQUEST_URI'];
$request_uri = strtok($request_uri, '?');
$request_uri = rtrim($request_uri, '/');
$request_uri = str_replace('/index.php', '', $request_uri);

// Map API routes to files using absolute paths relative to this directory (__DIR__)
$routes = [
    '/api/auth/login' => __DIR__ . '/auth/login.php',
    '/api/admin/login' => __DIR__ . '/auth/admin-login.php',
    '/api/auth/register' => __DIR__ . '/auth/register.php',
    '/api/auth/logout' => __DIR__ . '/auth/logout.php',
    '/api/auth/check-session' => __DIR__ . '/auth/check-session.php',
    '/api/applications/list' => __DIR__ . '/applications/list.php',
    '/api/documents/upload' => __DIR__ . '/documents/upload.php',
    '/api/notifications/fetch' => __DIR__ . '/notifications/fetch.php',
    '/api/notifications/mark-read' => __DIR__ . '/notifications/mark-read.php',
    '/api/public/stats' => __DIR__ . '/public/stats.php',
];

// Check if route exists
if (isset($routes[$request_uri])) {
    require_once $routes[$request_uri];
    exit;
}

// Serve HTML/CSS/JS files if they exist in the project directory relative to the project root
$file_path = ltrim($request_uri, '/');
$root_dir = dirname(__DIR__);
$absolute_file_path = $root_dir . '/' . $file_path;
if (!empty($file_path) && file_exists($absolute_file_path) && !is_dir($absolute_file_path)) {
    return false; // Let the server serve the file
}

// Default to index.html in the project root
require_once $root_dir . '/index.html';
?>