<?php
/**
 * 评论模型类
 */
class Comment {
    private $db;
    private $table = 'comments';

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * 获取文章评论
     */
    public function getByArticle($articleId, $parentId = null) {
        if ($parentId === null) {
            $sql = "SELECT * FROM {$this->table} WHERE article_id = ? AND parent_id IS NULL ORDER BY created_at DESC";
            return $this->db->fetchAll($sql, [$articleId]);
        } else {
            $sql = "SELECT * FROM {$this->table} WHERE article_id = ? AND parent_id = ? ORDER BY created_at ASC";
            return $this->db->fetchAll($sql, [$articleId, $parentId]);
        }
    }

    /**
     * 创建评论
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (id, article_id, user_id, author_name, author_email, author_type, guest_id, content, parent_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $data['id'],
            $data['article_id'],
            $data['user_id'] ?? null,
            $data['author_name'],
            $data['author_email'],
            $data['author_type'] ?? 'guest',
            $data['guest_id'] ?? null,
            $data['content'],
            $data['parent_id'] ?? null,
            date('Y-m-d H:i:s')
        ];

        $this->db->query($sql, $params);
        return $data['id'];
    }

    /**
     * 删除评论
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * 获取评论数
     */
    public function getCount($articleId) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE article_id = ?";
        $result = $this->db->fetchOne($sql, [$articleId]);
        return $result['count'];
    }
}
