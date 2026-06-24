<?php
/**
 * 图片上传API
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

// 确保上传目录存在
$uploadDir = __DIR__ . '/../../uploads/images/';
if (!file_exists($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// 检查是否有文件上传
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['success' => false, 'message' => '文件上传失败'], 400);
}

$file = $_FILES['image'];

// 检查文件大小 (10MB)
if ($file['size'] > 10 * 1024 * 1024) {
    jsonResponse(['success' => false, 'message' => '图片大小不能超过10MB'], 400);
}

// 验证文件真实类型（通过文件扩展名和getimagesize双重验证）
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($extension, $allowedExtensions)) {
    jsonResponse(['success' => false, 'message' => '不支持的图片格式'], 400);
}

// 进一步验证图片文件
$imageInfo = @getimagesize($file['tmp_name']);
if (!$imageInfo) {
    jsonResponse(['success' => false, 'message' => '无效的图片文件'], 400);
}

// 生成唯一文件名
$filename = date('YmdHis') . '_' . uniqid() . '.' . $extension;
$filepath = $uploadDir . $filename;

// 移动文件
if (@move_uploaded_file($file['tmp_name'], $filepath)) {
    // 返回URL
    $url = '/uploads/images/' . $filename;
    jsonResponse([
        'success' => true,
        'message' => '上传成功',
        'url' => $url,
        'filename' => $filename
    ]);
} else {
    jsonResponse(['success' => false, 'message' => '文件保存失败'], 500);
}
