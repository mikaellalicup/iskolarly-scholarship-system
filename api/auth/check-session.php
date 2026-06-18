<?php
require_once __DIR__ . '/../config/helpers.php';

startSecureSession();

if (isset($_SESSION['user_id'])) {
    sendSuccess([
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'user_type' => $_SESSION['user_type'],
        'email' => $_SESSION['email']
    ], 'Session active');
} else {
    sendError('No active session', 401);
}
?>