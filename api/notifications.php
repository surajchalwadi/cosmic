<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include '../config/db.php';
include '../includes/notification_functions.php';

$action = $_GET['action'] ?? 'get';

switch ($action) {
    case 'get':
        // Get recent notifications
        $notifications = getRecentNotifications(15);
        $unreadCount = getUnreadNotificationCount();
        
        // Format notifications for display
        $formattedNotifications = array_map(function($notification) {
            return [
                'id' => $notification['notification_id'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'user_name' => $notification['user_name'],
                'user_role' => ucfirst($notification['user_role']),
                'action_type' => $notification['action_type'],
                'priority' => $notification['priority'],
                'is_read' => (bool)$notification['is_read'],
                'created_at' => $notification['created_at'],
                'time_ago' => timeAgo($notification['created_at']),
                'icon' => getNotificationIcon($notification['action_type']),
                'color' => getNotificationColor($notification['priority']),
                'reference_id' => $notification['reference_id'],
                'reference_type' => $notification['reference_type']
            ];
        }, $notifications);
        
        echo json_encode([
            'notifications' => $formattedNotifications,
            'unread_count' => $unreadCount,
            'status' => 'success'
        ]);
        break;
        
    case 'mark_read':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $notificationId = $_POST['notification_id'] ?? null;
            if ($notificationId) {
                $success = markNotificationAsRead($notificationId);
                echo json_encode(['success' => $success]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Missing notification ID']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        }
        break;
        
    case 'mark_all_read':
        $success = markAllNotificationsAsRead();
        echo json_encode(['success' => $success]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

/**
 * Calculate time ago from timestamp
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    if ($time < 2592000) return floor($time/86400) . 'd ago';
    
    return date('M j', strtotime($datetime));
}
?>
