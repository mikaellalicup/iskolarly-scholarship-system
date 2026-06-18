<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if (!isLoggedIn()) {
    sendError('Unauthorized', 401);
}

$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['notification_id'] ?? null;

if (!$notification_id) {
    sendError('Notification ID required');
}

$user_id = getCurrentUserId();

try {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE notification_id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        sendSuccess(null, 'Notification marked as read');
    } else {
        sendError('Notification not found', 404);
    }
} catch (PDOException $e) {
    sendError('Failed to update notification', 500);
}
?>