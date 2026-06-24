<?php
/**
 * 获取评论列表API
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

// 只接受GET请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => '无效的请求方法'], 405);
}

// 获取文章ID
$articleId = $_GET['article_id'] ?? '';

if (!$articleId) {
    jsonResponse(['success' => false, 'message' => '请提供文章ID'], 400);
}

try {
    $dbConfig = getDatabaseConfig();
    if (!$dbConfig) {
        jsonResponse(['success' => false, 'message' => '数据库配置不存在'], 500);
    }

    $db = Database::create($dbConfig);
    $commentModel = new Comment($db);

    $comments = $commentModel->getByArticle($articleId);
    $count = $commentModel->getCount($articleId);

    jsonResponse([
        'success' => true,
        'comments' => $comments,
        'total' => $count
    ]);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
