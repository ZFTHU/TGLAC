<?php
/**
 * 用户注册API
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
    $input = $_POST;
}

$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

if (!$username || !$email || !$password) {
    jsonResponse(['success' => false, 'message' => '请填写完整信息'], 400);
}

if (strlen($username) < 3 || strlen($username) > 20) {
    jsonResponse(['success' => false, 'message' => '用户名长度应为3-20个字符'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => '邮箱格式不正确'], 400);
}

if (strlen($password) < 6) {
    jsonResponse(['success' => false, 'message' => '密码长度不能少于6位'], 400);
}

if ($password !== $confirmPassword) {
    jsonResponse(['success' => false, 'message' => '两次密码输入不一致'], 400);
}

try {
    $dbConfig = getDatabaseConfig();
    if (!$dbConfig) {
        jsonResponse(['success' => false, 'message' => '数据库配置不存在'], 500);
    }

    $db = Database::create($dbConfig);
    $userModel = new User($db);

    // 检查用户名是否已存在
    if ($userModel->getByUsername($username)) {
        jsonResponse(['success' => false, 'message' => '用户名已被注册'], 400);
    }

    // 检查邮箱是否已存在
    if ($userModel->getByEmail($email)) {
        jsonResponse(['success' => false, 'message' => '邮箱已被注册'], 400);
    }

    // 创建用户（create 方法内部会自动 hash 密码）
    $userId = $userModel->create([
        'id' => generateUUID(),
        'username' => $username,
        'email' => $email,
        'password' => $password,
        'role' => 'user'
    ]);

    // 自动登录
    session_regenerate_id(true);

    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['user_name'] = $username;
    $_SESSION['user_role'] = 'user';
    $_SESSION['is_admin'] = false;
    $_SESSION['login_time'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';

    generateCSRFToken();

    jsonResponse([
        'success' => true,
        'message' => '注册成功',
        'user' => [
            'id' => $userId,
            'username' => $username,
            'role' => 'user'
        ]
    ]);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
