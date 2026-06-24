<?php
/**
 * 用户登出API
 */

require_once __DIR__ . '/../../config/config.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax || isset($_GET['json'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'message' => '已退出登录'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Location: /login.php');
exit;
