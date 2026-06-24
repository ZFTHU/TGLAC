<?php
/**
 * 更新文章API
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

    $articleModel = new Article($db);
    $article = $articleModel->getById($input['id']);

    if (!$article) {
        jsonResponse(['success' => false, 'message' => '文章不存在'], 404);
    }

    // 准备更新数据
    $updateData = [];

    if (isset($input['title'])) $updateData['title'] = $input['title'];
    if (isset($input['slug'])) $updateData['slug'] = $input['slug'];
    if (isset($input['content'])) $updateData['content'] = $input['content'];
    if (isset($input['excerpt'])) $updateData['excerpt'] = $input['excerpt'];
    if (isset($input['cover_image'])) $updateData['cover_image'] = $input['cover_image'];
    if (isset($input['category_id'])) $updateData['category_id'] = $input['category_id'];
    if (isset($input['tags'])) $updateData['tags'] = $input['tags'];
    if (isset($input['published'])) $updateData['published'] = $input['published'] ? 1 : 0;

    $articleModel->update($input['id'], $updateData);

    // 更新分类文章计数
    if (isset($input['category_id'])) {
        $categoryModel = new Category($db);
        $categoryModel->updateArticleCount($input['category_id']);
        if ($article['category_id'] !== $input['category_id']) {
            $categoryModel->updateArticleCount($article['category_id']);
        }
    }

    jsonResponse([
        'success' => true,
        'message' => '文章更新成功'
    ]);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
