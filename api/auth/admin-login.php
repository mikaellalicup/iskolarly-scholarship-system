<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['admin_id']) || !isset($data['password']) || !isset($data['role'])) {
    sendError('Administrator ID, password, and role are required');
}

$admin_id = sanitize($data['admin_id']);
$password = $data['password'];
$role = sanitize($data['role']);

// Map role to user_type
$user_type = '';
if ($role === 'Administrator') {
    $user_type = 'admin';
} elseif ($role === 'Super Administrator') {
    $user_type = 'super_admin';
} else {
    sendError('Invalid role specified');
}

try {
    // Find user in database (allow username or email lookup)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND user_type = ?");
    $stmt->execute([$admin_id, $admin_id, $user_type]);
    $user = $stmt->fetch();

    if (!$user) {
        sendError('Invalid credentials. Please try again.', 401);
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        sendError('Invalid credentials. Please try again.', 401);
    }

    // Check account status
    if ($user['status'] === 'suspended') {
        sendError('Your account has been suspended. Please contact support.', 403);
    }

    // Start secure session
    startSecureSession();
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['email'] = $user['email'];

    // Update last login
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);

    sendSuccess([
        'user_id' => $user['user_id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'user_type' => $user['user_type']
    ], 'Login successful');

} catch (PDOException $e) {
    error_log("Admin login error: " . $e->getMessage());
    sendError('Authentication failed. Please try again later.', 500);
}
?>
