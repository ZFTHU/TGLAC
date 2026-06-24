<?php
/**
 * 更新系统设置API
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

// 检查登录状态和管理员权限
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '请先登录'], 401);
}

if (!isAdmin()) {
    jsonResponse(['success' => false, 'message' => '权限不足'], 403);
}

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => '无效的请求方法'], 405);
}

// 获取请求数据
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(['success' => false, 'message' => '无效的请求数据'], 400);
}

try {
    $dbConfig = getDatabaseConfig();
    $db = Database::create($dbConfig);

    $settingsModel = new Settings($db);

    // 准备更新数据
    $updateData = [];

    if (isset($input['site_name'])) $updateData['site_name'] = $input['site_name'];
    if (isset($input['site_description'])) $updateData['site_description'] = $input['site_description'];
    if (isset($input['site_keywords'])) $updateData['site_keywords'] = $input['site_keywords'];
    if (isset($input['footer_text'])) $updateData['footer_text'] = $input['footer_text'];
    if (isset($input['posts_per_page'])) $updateData['posts_per_page'] = intval($input['posts_per_page']);

    if (!empty($updateData)) {
        $settingsModel->update($updateData);
    }

    jsonResponse([
        'success' => true,
        'message' => '设置保存成功'
    ]);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
