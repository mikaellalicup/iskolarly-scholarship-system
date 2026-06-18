<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['username']) || !isset($data['password'])) {
    sendError('Username and password are required');
}

$username = sanitize($data['username']);
$password = $data['password'];

// Find user (PostgreSQL uses ILIKE for case-insensitive, but we use exact match)
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $username]);
$user = $stmt->fetch();

if (!$user) {
    sendError('Invalid username or password', 401);
}

// Verify password
if (!password_verify($password, $user['password_hash'])) {
    sendError('Invalid username or password', 401);
}

// Check account status
if ($user['status'] === 'suspended') {
    sendError('Your account has been suspended. Please contact support.', 403);
}

if ($user['status'] === 'pending') {
    sendError('Your account is pending verification. Please check your email.', 403);
}

// Start session
startSecureSession();
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['user_type'] = $user['user_type'];
$_SESSION['email'] = $user['email'];

// Update last login (PostgreSQL uses NOW())
$stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
$stmt->execute([$user['user_id']]);

// Get student profile if exists
$profile = null;
if ($user['user_type'] === 'student') {
    $stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);
    $profile = $stmt->fetch();
}

sendSuccess([
    'user_id' => $user['user_id'],
    'username' => $user['username'],
    'email' => $user['email'],
    'user_type' => $user['user_type'],
    'profile' => $profile
], 'Login successful');
?>