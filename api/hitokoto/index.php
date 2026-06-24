<?php
/**
 * 随机一言 API
 * 从本地 hitokoto.json 中随机返回一条
 */

header('Content-Type: application/json; charset=utf-8');

$jsonPath = __DIR__ . '/../../data/hitokoto.json';

if (!file_exists($jsonPath)) {
    echo json_encode([
        'hitokoto' => '愿你有一天能与重要的人重逢。',
        'from' => '系统默认',
        'from_who' => '',
        'type' => 'k'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents($jsonPath), true);

if (!is_array($data) || empty($data)) {
    echo json_encode([
        'hitokoto' => '愿你有一天能与重要的人重逢。',
        'from' => '系统默认',
        'from_who' => '',
        'type' => 'k'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 随机返回一条
$randomIndex = array_rand($data);
$result = $data[$randomIndex];

// 确保返回字段
$result = [
    'hitokoto' => $result['hitokoto'] ?? '愿你有一天能与重要的人重逢。',
    'from' => $result['from'] ?? '佚名',
    'from_who' => $result['from_who'] ?? '',
    'type' => $result['type'] ?? 'k',
    'creator' => $result['creator'] ?? '未知'
];

echo json_encode($result, JSON_UNESCAPED_UNICODE);
