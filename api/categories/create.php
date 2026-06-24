<?php
/**
 * 创建分类API
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

// 验证必填字段
if (empty($input['name'])) {
    jsonResponse(['success' => false, 'message' => '请填写分类名称'], 400);
}

try {
    $dbConfig = getDatabaseConfig();
    $db = Database::create($dbConfig);

    $categoryModel = new Category($db);

    // 生成ID和Slug
    $categoryId = $input['id'] ?? generateUUID();
    $slug = $input['slug'] ?? '';

    if (empty($slug)) {
        // 自动生成slug
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9\-]/', '-', $input['name']), '-'));
    }

    // 确保slug唯一
    $originalSlug = $slug;
    $counter = 1;
    while ($categoryModel->getBySlug($slug) && ($categoryModel->getBySlug($slug)['id'] !== $categoryId)) {
        $slug = $originalSlug . '-' . $counter++;
    }

    // 检查是更新还是创建
    if (!empty($input['id']) && $categoryModel->getById($input['id'])) {
        // 更新
        $categoryModel->update($input['id'], [
            'name' => $input['name'],
            'slug' => $slug,
            'description' => $input['description'] ?? ''
        ]);
        $categoryId = $input['id'];
    } else {
        // 创建
        $categoryModel->create([
            'id' => $categoryId,
            'name' => $input['name'],
            'slug' => $slug,
            'description' => $input['description'] ?? ''
        ]);
    }

    jsonResponse([
        'success' => true,
        'message' => '分类保存成功',
        'category_id' => $categoryId
    ]);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
