<?php
/**
 * 公告模型类
 */
class Announcement {
    private $db;
    private $table = 'announcements';

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * 获取所有公告
     */
    public function getAll($limit = 5) {
        try {
            $limitInt = intval($limit);
            $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY is_top DESC, created_at DESC LIMIT {$limitInt}";
            $result = $this->db->fetchAll($sql, []);
            return $result ? $result : [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 获取最新公告
     */
    public function getLatest($limit = 1) {
        try {
            $limitInt = intval($limit);
            $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY is_top DESC, created_at DESC LIMIT {$limitInt}";
            $result = $this->db->fetchAll($sql, []);
            return $result ? $result : [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 通过ID获取公告
     */
    public function getById($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = ?";
            return $this->db->fetchOne($sql, [$id]);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * 创建公告
     */
    public function create($data) {
        $this->ensureTableExists();
        $sql = "INSERT INTO {$this->table} (id, title, content, image_url, is_top, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $data['id'] ?? generateUUID(),
            $data['title'],
            $data['content'],
            $data['image_url'] ?? '',
            $data['is_top'] ?? 0,
            $data['is_active'] ?? 1,
            date('Y-m-d H:i:s')
        ];

        return $this->db->query($sql, $params);
    }

    /**
     * 更新公告
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
        return $this->db->query($sql, $params);
    }

    /**
     * 删除公告
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }

    /**
     * 获取所有公告（包括未激活的）
     */
    public function getAllAdmin($limit = 100) {
        try {
            $this->ensureTableExists();
            $limitInt = intval($limit);
            $sql = "SELECT * FROM {$this->table} ORDER BY is_top DESC, created_at DESC LIMIT {$limitInt}";
            $result = $this->db->fetchAll($sql, []);
            return $result ? $result : [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 检查并创建公告表（如果不存在），同时为旧表补 image_url 字段
     */
    public function ensureTableExists() {
        try {
            $dbType = $this->db->getType();
            
            if ($dbType === 'mysql') {
                $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                    id VARCHAR(36) PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    content TEXT NOT NULL,
                    image_url VARCHAR(500) DEFAULT '',
                    is_top TINYINT(1) DEFAULT 0,
                    is_active TINYINT(1) DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            } else {
                $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                    id TEXT PRIMARY KEY,
                    title TEXT NOT NULL,
                    content TEXT NOT NULL,
                    image_url TEXT DEFAULT '',
                    is_top INTEGER DEFAULT 0,
                    is_active INTEGER DEFAULT 1,
                    created_at TEXT
                )";
            }
            
            $this->db->query($sql, []);

            try {
                if ($dbType === 'mysql') {
                    $this->db->query("ALTER TABLE {$this->table} ADD COLUMN image_url VARCHAR(500) DEFAULT ''", []);
                } else {
                    $this->db->query("ALTER TABLE {$this->table} ADD COLUMN image_url TEXT DEFAULT ''", []);
                }
            } catch (Exception $e) {}

            return true;
        } catch (Exception $e) {
            error_log("Announcement table error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取公告总数
     */
    public function getCount() {
        try {
            $sql = "SELECT COUNT(*) as cnt FROM {$this->table}";
            $result = $this->db->fetchOne($sql, []);
            return $result ? (int)$result['cnt'] : 0;
        } catch (Exception $e) {
            return 0;
        }
    }
}
