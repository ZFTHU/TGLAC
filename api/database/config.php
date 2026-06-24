<?php
/**
 * 更新数据库配置API
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

// 检查登录状态
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '请先登录'], 401);
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

try {
    $config = [
        'type' => $input['type'] ?? 'sqlite'
    ];

    switch ($config['type']) {
        case 'mysql':
            $config['host'] = $input['host'] ?? 'localhost';
            $config['port'] = $input['port'] ?? 3306;
            $config['username'] = $input['username'] ?? '';
            $config['password'] = $input['password'] ?? '';
            $config['database'] = $input['database'] ?? '';
            break;

        case 'sqlite':
            $config['database'] = $input['database'] ?? 'data/blog.db';
            break;

        case 'mongodb':
            $config['host'] = $input['host'] ?? 'localhost';
            $config['port'] = $input['port'] ?? 27017;
            $config['username'] = $input['username'] ?? '';
            $config['password'] = $input['password'] ?? '';
            $config['database'] = $input['database'] ?? '';
            break;
    }

    // 测试连接
    $db = Database::create($config);
    if (!$db->testConnection()) {
        jsonResponse(['success' => false, 'message' => '数据库连接失败']);
    }

    // 保存配置
    file_put_contents(CONFIG_PATH . '/database.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    jsonResponse([
        'success' => true,
        'message' => '数据库配置保存成功'
    ]);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
