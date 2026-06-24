<?php
/**
 * 发表评论API
 * 支持注册用户和游客评论
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => '无效的请求方法'], 405);
}

// 获取请求数据
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(['success' => false, 'message' => '无效的请求数据'], 400);
}

// 获取数据库连接
$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);

$articleId = $input['article_id'] ?? '';
$content = trim($input['content'] ?? '');

if (!$articleId || !$content) {
    jsonResponse(['success' => false, 'message' => '请填写完整信息'], 400);
}

if (mb_strlen($content) > 2000) {
    jsonResponse(['success' => false, 'message' => '评论内容不能超过2000字符'], 400);
}

if (mb_strlen($content) < 1) {
    jsonResponse(['success' => false, 'message' => '评论内容不能为空'], 400);
}

// 检查文章是否存在
$articleModel = new Article($db);
$article = $articleModel->getById($articleId);
if (!$article) {
    jsonResponse(['success' => false, 'message' => '文章不存在'], 404);
}

// 检查是否已登录
$isLoggedIn = isLoggedIn();
$authorName = '';
$authorEmail = '';
$authorType = 'guest';
$userId = null;
$guestId = null;

if ($isLoggedIn) {
    // 注册用户评论
    $userModel = new User($db);
    $user = $userModel->getById($_SESSION['user_id']);
    if ($user) {
        $authorName = $user['username'];
        $authorEmail = $user['email'];
        $authorType = 'user';
        $userId = $user['id'];
    } else {
        jsonResponse(['success' => false, 'message' => '用户不存在'], 400);
    }
} else {
    // 游客评论
    $authorName = trim($input['author_name'] ?? '');
    $authorEmail = trim($input['author_email'] ?? '');

    if (!$authorName || !$authorEmail) {
        jsonResponse(['success' => false, 'message' => '请填写昵称和邮箱'], 400);
    }

    // 验证邮箱格式
    if (!filter_var($authorEmail, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => '邮箱格式不正确'], 400);
    }

    // 生成游客ID（基于session）
    $guestId = 'guest_' . substr(session_id(), 0, 8);
}

try {
    $commentModel = new Comment($db);
    $commentId = generateUUID();

    $commentModel->create([
        'id' => $commentId,
        'article_id' => $articleId,
        'user_id' => $userId,
        'author_name' => $authorName,
        'author_email' => $authorEmail,
        'author_type' => $authorType,
        'guest_id' => $guestId,
        'content' => $content,
        'parent_id' => $input['parent_id'] ?? null
    ]);

    jsonResponse([
        'success' => true,
        'message' => '评论发表成功',
        'comment_id' => $commentId,
        'author_type' => $authorType,
        'author_name' => $authorName
    ]);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
