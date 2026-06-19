<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

echo json_encode([
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? null,
    'PATH_INFO' => $_SERVER['PATH_INFO'] ?? null,
    'cwd' => getcwd(),
    'dir' => __DIR__,
    'files_in_auth' => scandir(__DIR__ . '/auth'),
    'admin_login_exists' => file_exists(__DIR__ . '/auth/admin-login.php'),
]);
?>
