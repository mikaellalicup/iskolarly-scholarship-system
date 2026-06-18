<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if (!isLoggedIn()) {
    sendError('Unauthorized', 401);
}

$user_id = getCurrentUserId();

try {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();
    
    // Count unread
    $stmt = $pdo->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $stmt->execute([$user_id]);
    $unread = $stmt->fetch();
    
    sendSuccess([
        'items' => $notifications,
        'unreadCount' => (int)($unread['unread'] ?? 0),
        'totalCount' => count($notifications)
    ]);
} catch (PDOException $e) {
    error_log("Notifications error: " . $e->getMessage());
    sendError('Failed to fetch notifications', 500);
}
?>