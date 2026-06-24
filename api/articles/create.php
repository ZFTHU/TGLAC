<?php
/**
 * 创建文章API
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

// 检查登录状态和管理员权限
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '请先登录'], 401);
}

if (!isAdmin()) {
    jsonResponse(['success' => false, 'message' => '权限不足'], 403);
}

try {
    $dbConfig = getDatabaseConfig();
    
    if (!$dbConfig) {
        jsonResponse(['success' => false, 'message' => '数据库配置不存在，请重新安装'], 500);
    }
    
    $db = Database::create($dbConfig);
    
    $currentUserId = $_SESSION['user_id'] ?? null;
    $validUser = $db->fetchOne("SELECT id, username, role FROM users WHERE id = ?", [$currentUserId]);
    
    if (!$validUser) {
        jsonResponse(['success' => false, 'message' => '用户不存在'], 401);
    }

    // 只接受POST请求
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => '无效的请求方法'], 405);
    }

    // 获取请求数据
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (!$input) {
        jsonResponse(['success' => false, 'message' => '无效的请求数据: ' . json_last_error_msg()], 400);
    }

    // 验证必填字段
    if (empty($input['title']) || empty($input['content']) || empty($input['category_id'])) {
        jsonResponse(['success' => false, 'message' => '请填写完整信息（标题、内容、分类）'], 400);
    }

    if (mb_strlen($input['title']) > 200) {
        jsonResponse(['success' => false, 'message' => '文章标题不能超过200字符'], 400);
    }

    if (mb_strlen($input['title']) < 1) {
        jsonResponse(['success' => false, 'message' => '文章标题不能为空'], 400);
    }

    if (mb_strlen($input['content']) < 1) {
        jsonResponse(['success' => false, 'message' => '文章内容不能为空'], 400);
    }

    if (!empty($input['excerpt']) && mb_strlen($input['excerpt']) > 500) {
        jsonResponse(['success' => false, 'message' => '文章摘要不能超过500字符'], 400);
    }

    if (!empty($input['slug']) && mb_strlen($input['slug']) > 100) {
        jsonResponse(['success' => false, 'message' => '文章别名不能超过100字符'], 400);
    }
    
    // 验证分类是否存在
    $category = $db->fetchOne("SELECT id FROM categories WHERE id = ?", [$input['category_id']]);
    if (!$category) {
        jsonResponse(['success' => false, 'message' => '选择的分类不存在'], 400);
    }

    $articleModel = new Article($db);

    // 生成ID和Slug
    $articleId = generateUUID();
    $slug = $input['slug'] ?? '';

    if (empty($slug)) {
        $slug = 'article-' . date('YmdHis');
    }

    // 确保slug唯一
    $originalSlug = $slug;
    $counter = 1;
    while ($articleModel->getBySlug($slug)) {
        $slug = $originalSlug . '-' . $counter++;
    }

    $articleModel->create([
        'id' => $articleId,
        'title' => $input['title'],
        'slug' => $slug,
        'content' => $input['content'],
        'excerpt' => $input['excerpt'] ?? '',
        'cover_image' => $input['cover_image'] ?? '',
        'category_id' => $input['category_id'],
        'tags' => $input['tags'] ?? '',
        'author_id' => $validUser['id'],
        'published' => !empty($input['published'])
    ]);

    // 更新分类文章计数
    $categoryModel = new Category($db);
    $categoryModel->updateArticleCount($input['category_id']);

    jsonResponse([
        'success' => true,
        'message' => '文章创建成功',
        'article_id' => $articleId
    ]);

} catch (PDOException $e) {
    error_log("Article Create PDO Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => '数据库错误: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    error_log("Article Create Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => '创建失败: ' . $e->getMessage()], 500);
}
