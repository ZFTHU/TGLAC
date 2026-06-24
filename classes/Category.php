<?php
/**
 * 分类模型类
 */
class Category {
    private $db;
    private $table = 'categories';

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * 获取所有分类
     */
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }

    /**
     * 获取单个分类
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * 通过slug获取分类
     */
    public function getBySlug($slug) {
        $sql = "SELECT * FROM {$this->table} WHERE slug = ?";
        return $this->db->fetchOne($sql, [$slug]);
    }

    /**
     * 创建分类
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (id, name, slug, description, article_count, created_at)
                VALUES (?, ?, ?, ?, 0, ?)";

        $params = [
            $data['id'],
            $data['name'],
            $data['slug'],
            $data['description'] ?? '',
            date('Y-m-d H:i:s')
        ];

        $this->db->query($sql, $params);
        return $data['id'];
    }

    /**
     * 更新分类
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            $fields[] = "{$key} = ?";
            $params[] = $value;
        }

        $params[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        $this->db->query($sql, $params);

        return true;
    }

    /**
     * 删除分类
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * 更新文章计数
     */
    public function updateArticleCount($id) {
        $sql = "UPDATE {$this->table} SET article_count = (
                    SELECT COUNT(*) FROM articles WHERE category_id = ? AND published = 1
                ) WHERE id = ?";
        $this->db->query($sql, [$id, $id]);
    }
}
