<?php
/**
 * Notifications API endpoint
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = sanitize($_GET['action'] ?? '');

switch ($action) {
    case 'mark_all_read':
        db()->update("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$_SESSION['user_id']]);
        echo json_encode(['success' => true]);
        break;

    case 'mark_read':
        $id = intval($_POST['id'] ?? 0);
        db()->update("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?", [$id, $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
        break;

    case 'get_recent':
        $notifications = getRecentNotifications(10);
        $count = unreadNotificationCount();
        echo json_encode(['success' => true, 'notifications' => $notifications, 'unread_count' => $count]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
