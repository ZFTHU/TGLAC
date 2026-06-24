<?php
/**
 * 用户模型类
 */
class User {
    private $db;
    private $table = 'users';

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * 通过用户名获取用户
     */
    public function getByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE username = ?";
        return $this->db->fetchOne($sql, [$username]);
    }

    /**
     * 通过邮箱获取用户
     */
    public function getByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        return $this->db->fetchOne($sql, [$email]);
    }

    /**
     * 通过ID获取用户
     */
    public function getById($id) {
        $sql = "SELECT id, username, email, role, created_at FROM {$this->table} WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * 创建用户
     */
    public function create($data) {
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        $sql = "INSERT INTO {$this->table} (id, username, email, password_hash, role, created_at)
                VALUES (?, ?, ?, ?, ?, ?)";

        $params = [
            $data['id'],
            $data['username'],
            $data['email'],
            $passwordHash,
            $data['role'] ?? 'admin',
            date('Y-m-d H:i:s')
        ];

        $this->db->query($sql, $params);
        return $data['id'];
    }

    /**
     * 更新用户
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            if ($key === 'password') {
                $fields[] = "password_hash = ?";
                $params[] = password_hash($value, PASSWORD_BCRYPT);
            } else {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }

        $params[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        $this->db->query($sql, $params);

        return true;
    }

    /**
     * 验证登录
     */
    public function verifyLogin($username, $password) {
        $user = $this->getByUsername($username);

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        return $user;
    }

    /**
     * 删除用户
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * 获取用户列表
     */
    public function getList($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT id, username, email, role, created_at FROM {$this->table} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$limit, $offset]);
    }

    /**
     * 获取用户总数
     */
    public function getCount() {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->fetchOne($sql);
        return $result ? ($result['count'] ?? 0) : 0;
    }
}
