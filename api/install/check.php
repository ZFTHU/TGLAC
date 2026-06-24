<?php
/**
 * 检查安装状态API
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

jsonResponse([
    'installed' => isInstalled(),
    'version' => isInstalled() ? json_decode(file_get_contents(CONFIG_PATH . '/installed.json'), true)['version'] ?? '1.0.0' : null
]);
