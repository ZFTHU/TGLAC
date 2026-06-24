<?php
/**
 * 通知 API
 * GET  /api/notifications.php           -> 获取当前用户通知列表
 * POST /api/notifications.php?action=mark_read&id=xxx -> 标记已读
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

// 获取当前用户ID（未登录返回空数组）
$userId = null;
if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
}

$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);
$notificationModel = new Notification($db);
$notificationModel->ensureTableExists();

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'mark_read' && $userId) {
    $id = $_GET['id'] ?? '';
    if ($id) {
        $notificationModel->markAsRead($id, $userId);
    }
    echo json_encode(['success' => true]);
    exit;
}

// GET: 获取通知列表
if (!$userId) {
    echo json_encode(['notifications' => []]);
    exit;
}

$notifications = $notificationModel->getForUser($userId, 50);
echo json_encode(['notifications' => $notifications]);
