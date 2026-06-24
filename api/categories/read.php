<?php
/**
 * 获取单个分类API
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

// 只接受GET请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => '无效的请求方法'], 405);
}

// 获取分类ID或slug
$slug = $_GET['slug'] ?? '';
$id = $_GET['id'] ?? '';

if (!$slug && !$id) {
    jsonResponse(['success' => false, 'message' => '请提供分类ID或slug'], 400);
}

try {
    $dbConfig = getDatabaseConfig();
    if (!$dbConfig) {
        jsonResponse(['success' => false, 'message' => '数据库配置不存在'], 500);
    }

    $db = Database::create($dbConfig);
    $categoryModel = new Category($db);

    if ($slug) {
        $category = $categoryModel->getBySlug($slug);
    } else {
        $category = $categoryModel->getById($id);
    }

    if (!$category) {
        jsonResponse(['success' => false, 'message' => '分类不存在'], 404);
    }

    jsonResponse([
        'success' => true,
        'category' => $category
    ]);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
