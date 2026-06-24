<?php
/**
 * 网站维护模式 API
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// 检查是否是管理员
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '权限不足']);
    exit;
}

// 获取数据库连接
$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);

// 获取请求方法
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // 获取维护模式状态
    $maintenanceMode = getMaintenanceMode();
    
    echo json_encode([
        'success' => true,
        'maintenance_mode' => $maintenanceMode
    ]);
    exit;
}

if ($method === 'POST') {
    // 获取请求体
    $input = json_decode(file_get_contents('php://input'), true);
    $enabled = isset($input['enabled']) ? (bool)$input['enabled'] : false;
    
    try {
        // 使用统一的设置函数（文件 + 数据库双存储）
        setMaintenanceMode($enabled);
        
        echo json_encode([
            'success' => true,
            'message' => $enabled ? '网站维护模式已开启' : '网站维护模式已关闭',
            'maintenance_mode' => $enabled
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => '操作失败: ' . $e->getMessage()
        ]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
