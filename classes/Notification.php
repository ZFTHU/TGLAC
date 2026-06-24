<?php
/**
 * 站内通知模型类
 */
class Notification {
    private $db;
    private $table = 'notifications';

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * 确保通知表存在
     */
    public function ensureTableExists() {
        try {
            $dbType = $this->db->getType();
            
            if ($dbType === 'mysql') {
                $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                    id VARCHAR(36) PRIMARY KEY,
                    user_id VARCHAR(36) DEFAULT 'ALL',
                    title VARCHAR(255) NOT NULL,
                    content TEXT,
                    type VARCHAR(20) DEFAULT 'info',
                    is_read TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            } else {
                $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                    id TEXT PRIMARY KEY,
                    user_id TEXT DEFAULT 'ALL',
                    title TEXT NOT NULL,
                    content TEXT,
                    type TEXT DEFAULT 'info',
                    is_read INTEGER DEFAULT 0,
                    created_at TEXT
                )";
            }
            
            $this->db->query($sql, []);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 发送通知给指定用户
     */
    public function sendToUser($userId, $title, $content, $type = 'info') {
        $this->ensureTableExists();
        $sql = "INSERT INTO {$this->table} (id, user_id, title, content, type, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, ?)";
        $params = [generateUUID(), $userId, $title, $content, $type, date('Y-m-d H:i:s')];
        return $this->db->query($sql, $params);
    }

    /**
     * 发送通知给所有用户（广播）
     */
    public function sendToAll($title, $content, $type = 'info') {
        $this->ensureTableExists();
        $sql = "INSERT INTO {$this->table} (id, user_id, title, content, type, is_read, created_at) VALUES (?, 'ALL', ?, ?, ?, 0, ?)";
        $params = [generateUUID(), $title, $content, $type, date('Y-m-d H:i:s')];
        return $this->db->query($sql, $params);
    }

    /**
     * 获取指定用户的通知列表（包含广播通知）
     */
    public function getForUser($userId, $limit = 50) {
        try {
            $this->ensureTableExists();
            $limitInt = intval($limit);
            $sql = "SELECT * FROM {$this->table} WHERE user_id = ? OR user_id = 'ALL' ORDER BY created_at DESC LIMIT {$limitInt}";
            $result = $this->db->fetchAll($sql, [$userId]);
            return $result ? $result : [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 获取所有通知（管理员用）
     */
    public function getAll($limit = 100) {
        try {
            $this->ensureTableExists();
            $limitInt = intval($limit);
            $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT {$limitInt}";
            $result = $this->db->fetchAll($sql, []);
            return $result ? $result : [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 获取未读通知数量
     */
    public function getUnreadCount($userId) {
        try {
            $this->ensureTableExists();
            $sql = "SELECT COUNT(*) as cnt FROM {$this->table} WHERE (user_id = ? OR user_id = 'ALL') AND is_read = 0";
            $result = $this->db->fetchOne($sql, [$userId]);
            return $result ? (int)$result['cnt'] : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * 标记为已读
     */
    public function markAsRead($id, $userId) {
        try {
            $sql = "UPDATE {$this->table} SET is_read = 1 WHERE id = ? AND (user_id = ? OR user_id = 'ALL')";
            return $this->db->query($sql, [$id, $userId]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 全部标记为已读
     */
    public function markAllAsRead($userId) {
        try {
            $sql = "UPDATE {$this->table} SET is_read = 1 WHERE (user_id = ? OR user_id = 'ALL') AND is_read = 0";
            return $this->db->query($sql, [$userId]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 删除通知
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            return $this->db->query($sql, [$id]);
        } catch (Exception $e) {
            return false;
        }
    }
}
