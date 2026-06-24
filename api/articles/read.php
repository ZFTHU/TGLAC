<?php
/**
 * 获取单篇文章API
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

// 只接受GET请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => '无效的请求方法'], 405);
}

// 获取文章ID或slug
$slug = $_GET['slug'] ?? '';
$id = $_GET['id'] ?? '';

if (!$slug && !$id) {
    jsonResponse(['success' => false, 'message' => '请提供文章ID或slug'], 400);
}

try {
    $dbConfig = getDatabaseConfig();
    if (!$dbConfig) {
        jsonResponse(['success' => false, 'message' => '数据库配置不存在'], 500);
    }

    $db = Database::create($dbConfig);
    $articleModel = new Article($db);

    if ($slug) {
        $article = $articleModel->getBySlug($slug);
    } else {
        $article = $articleModel->getById($id);
    }

    if (!$article) {
        jsonResponse(['success' => false, 'message' => '文章不存在'], 404);
    }

    // 增加浏览次数
    $articleModel->incrementViewCount($article['id']);

    jsonResponse([
        'success' => true,
        'article' => $article
    ]);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
