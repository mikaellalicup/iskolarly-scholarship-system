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

// Map API routes to files
$routes = [
    '/api/auth/login' => 'api/auth/login.php',
    '/api/auth/register' => 'api/auth/register.php',
    '/api/auth/logout' => 'api/auth/logout.php',
    '/api/auth/check-session' => 'api/auth/check-session.php',
    '/api/applications/list' => 'api/applications/list.php',
    '/api/documents/upload' => 'api/documents/upload.php',
    '/api/notifications/fetch' => 'api/notifications/fetch.php',
    '/api/notifications/mark-read' => 'api/notifications/mark-read.php',
    '/api/public/stats' => 'api/public/stats.php',
];

// Check if route exists
if (isset($routes[$request_uri])) {
    require_once $routes[$request_uri];
    exit;
}

// Serve HTML files if they exist
if (file_exists($request_uri)) {
    return false; // Let the server serve the file
}

// Default to index.html
require_once 'index.html';
?>