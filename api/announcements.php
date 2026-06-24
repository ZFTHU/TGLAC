<?php
/**
 * 公告详情 API
 * GET /api/announcements.php?id=xxx -> 获取单条公告详情
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$id = $_GET['id'] ?? '';

if (empty($id)) {
    echo json_encode(['error' => '缺少公告ID']);
    exit;
}

$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);
$announcementModel = new Announcement($db);
$announcementModel->ensureTableExists();

$ann = $announcementModel->getById($id);

if ($ann) {
    echo json_encode([
        'id' => $ann['id'],
        'title' => $ann['title'],
        'content' => $ann['content'],
        'image_url' => $ann['image_url'] ?? '',
        'created_at' => $ann['created_at']
    ]);
} else {
    echo json_encode(['error' => '公告不存在']);
}
