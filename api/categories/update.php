<?php
/**
 * 更新分类API
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

if (!$input || empty($input['id'])) {
    jsonResponse(['success' => false, 'message' => '无效的请求数据'], 400);
}

try {
    $dbConfig = getDatabaseConfig();
    $db = Database::create($dbConfig);

    $categoryModel = new Category($db);
    $category = $categoryModel->getById($input['id']);

    if (!$category) {
        jsonResponse(['success' => false, 'message' => '分类不存在'], 404);
    }

    // 准备更新数据
    $updateData = [];

    if (isset($input['name'])) $updateData['name'] = $input['name'];
    if (isset($input['slug'])) $updateData['slug'] = $input['slug'];
    if (isset($input['description'])) $updateData['description'] = $input['description'];

    $categoryModel->update($input['id'], $updateData);

    jsonResponse([
        'success' => true,
        'message' => '分类更新成功'
    ]);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
