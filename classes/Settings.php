<?php
/**
 * 系统设置模型类
 */
class Settings {
    private $db;
    private $table = 'settings';
    private $cache = null;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * 获取所有设置
     */
    public function getAll() {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $sql = "SELECT * FROM {$this->table} LIMIT 1";
        $result = $this->db->fetchOne($sql);

        if ($result) {
            // 解析JSON字段
            if (isset($result['site_keywords'])) {
                $result['site_keywords_array'] = json_decode($result['site_keywords'], true) ?: [];
            }
            $this->cache = $result;
        }

        return $this->cache;
    }

    /**
     * 获取单个设置项
     */
    public function get($key, $default = null) {
        $settings = $this->getAll();
        return $settings[$key] ?? $default;
    }

    /**
     * 更新设置
     */
    public function update($data) {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            if ($key === 'site_keywords' && is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $fields[] = "{$key} = ?";
            $params[] = $value;
        }

        $fields[] = "updated_at = ?";
        $params[] = date('Y-m-d H:i:s');

        // 获取第一条记录
        $current = $this->db->fetchOne("SELECT id FROM {$this->table} LIMIT 1");
        
        if ($current && isset($current['id'])) {
            // 更新现有记录
            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
            $params[] = $current['id'];
        } else {
            // 插入新记录
            $newId = $this->generateUUID();
            $keys = array_keys($data);
            $placeholders = array_fill(0, count($data), '?');
            $sql = "INSERT INTO {$this->table} (id, " . implode(', ', $keys) . ", updated_at) 
                    VALUES (?, " . implode(', ', $placeholders) . ", ?)";
            $params = array_merge([$newId], array_values($data), [date('Y-m-d H:i:s')]);
        }
        
        $this->db->query($sql, $params);

        // 清除缓存
        $this->cache = null;

        return true;
    }

    /**
     * 初始化默认设置
     */
    public function initDefaults($id) {
        $sql = "INSERT INTO {$this->table} (id, site_name, site_description, site_keywords, footer_text, posts_per_page, updated_at)
                VALUES (?, 'My Blog', 'A modern blog system', '[]', '', 10, ?)";

        $this->db->query($sql, [$id, date('Y-m-d H:i:s')]);
        return $id;
    }

    /**
     * 生成UUID
     */
    private function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
