<?php
/**
 * SQLite 数据库驱动
 */
class SQLite extends Database {
    protected $type = 'sqlite';

    protected function connect() {
        try {
            $rawPath = $this->config['database'] ?? 'data/blog.db';
            
            $isAbsolute = false;
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $isAbsolute = preg_match('/^[A-Za-z]:[\\\\\/]/', $rawPath);
            } else {
                $isAbsolute = isset($rawPath[0]) && $rawPath[0] === '/';
            }
            
            if ($isAbsolute) {
                $dbPath = $rawPath;
            } else {
                $dbPath = ROOT_PATH . '/' . $rawPath;
            }

            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $dsn = "sqlite:{$dbPath}";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            $this->connection = new PDO($dsn, null, null, $options);

            // 启用外键约束
            $this->connection->exec('PRAGMA foreign_keys = ON');
        } catch (PDOException $e) {
            throw new Exception("SQLite 连接失败: " . $e->getMessage());
        }
    }

    public function disconnect() {
        $this->connection = null;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("查询执行失败: " . $e->getMessage());
        }
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    public function commit() {
        return $this->connection->commit();
    }

    public function rollback() {
        return $this->connection->rollBack();
    }

    public function escape($string) {
        return $this->connection->quote($string);
    }

    public function testConnection() {
        try {
            $this->query('SELECT 1');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
