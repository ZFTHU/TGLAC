<?php
/**
 * 公告 API
 * 获取公告列表
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';

// 获取数据库配置
$dbConfig = getDatabaseConfig();
if (!$dbConfig) {
    echo json_encode([]);
    exit;
}

try {
    $db = Database::create($dbConfig);
    $announcementModel = new Announcement($db);
    $announcements = $announcementModel->getAll(10);
    echo json_encode($announcements, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([]);
}
