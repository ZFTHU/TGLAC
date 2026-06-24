<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => '无效的请求方法'], 405);
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input) {
    jsonResponse(['success' => false, 'message' => '无效的请求数据: ' . $rawInput], 400);
}

$dbType = $input['type'] ?? 'sqlite';

try {
    $config = [
        'type' => $dbType
    ];

    switch ($dbType) {
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

        default:
            throw new Exception("不支持的数据库类型: {$dbType}");
    }

    $db = Database::create($config);
    
    if (!method_exists($db, 'testConnection')) {
        jsonResponse(['success' => false, 'message' => '数据库对象没有 testConnection 方法'], 500);
    }
    
    $connected = $db->testConnection();

    if ($connected) {
        jsonResponse(['success' => true, 'message' => '数据库连接成功']);
    } else {
        jsonResponse(['success' => false, 'message' => '数据库连接失败']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Error $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '致命错误: ' . $e->getMessage(), 'trace' => $e->getTraceAsString()], JSON_UNESCAPED_UNICODE);
    exit;
}
