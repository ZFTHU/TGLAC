<?php
/**
 * 获取分类列表API
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

// 只接受GET请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => '无效的请求方法'], 405);
}

try {
    $dbConfig = getDatabaseConfig();
    if (!$dbConfig) {
        jsonResponse(['success' => false, 'message' => '数据库配置不存在'], 500);
    }

    $db = Database::create($dbConfig);
    $categoryModel = new Category($db);
    
    $categories = $categoryModel->getAll();

    jsonResponse([
        'success' => true,
        'categories' => $categories
    ]);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
