<?php
/**
 * 文章模型类
 */
class Article {
    private $db;
    private $table = 'articles';

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * 获取文章列表
     */
    public function getList($page = 1, $limit = 10, $categoryId = null, $published = true) {
        $offset = ($page - 1) * $limit;
        $where = "WHERE 1=1";
        $params = [];

        if ($categoryId) {
            $where .= " AND category_id = ?";
            $params[] = $categoryId;
        }

        if ($published !== null) {
            $where .= " AND published = ?";
            $params[] = $published ? 1 : 0;
        }

        $sql = "SELECT a.*, c.name as category_name, u.username as author_name
                FROM {$this->table} a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN users u ON a.author_id = u.id
                {$where}
                ORDER BY a.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * 获取文章总数
     */
    public function getCount($categoryId = null, $published = null) {
        $where = "WHERE 1=1";
        $params = [];

        if ($categoryId) {
            $where .= " AND category_id = ?";
            $params[] = $categoryId;
        }

        if ($published !== null) {
            $where .= " AND published = ?";
            $params[] = $published ? 1 : 0;
        }

        $sql = "SELECT COUNT(*) as count FROM {$this->table} {$where}";
        $result = $this->db->fetchOne($sql, $params);
        return $result ? ($result['count'] ?? 0) : 0;
    }

    /**
     * 获取单篇文章
     */
    public function getById($id) {
        $sql = "SELECT a.*, c.name as category_name, u.username as author_name
                FROM {$this->table} a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN users u ON a.author_id = u.id
                WHERE a.id = ?";

        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * 通过slug获取文章
     */
    public function getBySlug($slug) {
        $sql = "SELECT a.*, c.name as category_name, u.username as author_name
                FROM {$this->table} a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN users u ON a.author_id = u.id
                WHERE a.slug = ?";

        return $this->db->fetchOne($sql, [$slug]);
    }

    /**
     * 创建文章
     */
    public function create($data) {
        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO {$this->table} (id, title, slug, content, excerpt, cover_image, category_id, tags, author_id, view_count, published, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)";

        $params = [
            $data['id'],
            $data['title'],
            $data['slug'],
            $data['content'],
            $data['excerpt'] ?? '',
            $data['cover_image'] ?? '',
            $data['category_id'],
            $data['tags'] ?? '',
            $data['author_id'],
            $data['published'] ? 1 : 0,
            $now,
            $now
        ];

        $this->db->query($sql, $params);
        return $data['id'];
    }

    /**
     * 更新文章
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            $fields[] = "{$key} = ?";
            $params[] = $value;
        }

        $fields[] = "updated_at = ?";
        $params[] = date('Y-m-d H:i:s');
        $params[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        $this->db->query($sql, $params);

        return true;
    }

    /**
     * 删除文章
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * 增加浏览次数
     */
    public function incrementViewCount($id) {
        $sql = "UPDATE {$this->table} SET view_count = view_count + 1 WHERE id = ?";
        $this->db->query($sql, [$id]);
    }

    /**
     * 获取热门文章
     */
    public function getPopular($limit = 5) {
        $sql = "SELECT id, title, slug, view_count, cover_image
                FROM {$this->table}
                WHERE published = 1
                ORDER BY view_count DESC
                LIMIT {$limit}";

        return $this->db->fetchAll($sql, []);
    }

    /**
     * 获取相关文章
     */
    public function getRelated($articleId, $categoryId, $limit = 5) {
        $sql = "SELECT id, title, slug, cover_image
                FROM {$this->table}
                WHERE category_id = ? AND id != ? AND published = 1
                ORDER BY created_at DESC
                LIMIT {$limit}";

        return $this->db->fetchAll($sql, [$categoryId, $articleId]);
    }
}
