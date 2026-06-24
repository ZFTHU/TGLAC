<?php
/**
 * 获取文章列表API
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
    $articleModel = new Article($db);

    // 获取查询参数
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 10;
    $categoryId = $_GET['category_id'] ?? null;
    $published = isset($_GET['published']) ? ($_GET['published'] === 'true' || $_GET['published'] === '1') : null;

    $articles = $articleModel->getList($page, $limit, $categoryId, $published);
    $total = $articleModel->getCount($categoryId, $published);

    jsonResponse([
        'success' => true,
        'articles' => $articles,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ]
    ]);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
