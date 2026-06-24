<?php
/**
 * MySQL 数据库驱动（官方 PDO 标准实现）
 */
class MySQL extends Database {
    protected $type = 'mysql';

    protected function connect() {
        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? 3306;
        $dbname = $this->config['database'] ?? '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_TIMEOUT => 10,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $this->connection = new PDO(
                $dsn,
                $this->config['username'] ?? '',
                $this->config['password'] ?? '',
                $options
            );
        } catch (PDOException $e) {
            throw new Exception(
                "MySQL 连接失败: " . $e->getMessage() . "\n\n" .
                "排查方法:\n" .
                "1. 确认 MySQL 服务已启动\n" .
                "2. 检查主机地址、端口、用户名、密码是否正确\n" .
                "3. 确认数据库 '{$dbname}' 已存在\n" .
                "4. MySQL 8.x 用户如遇认证问题，可执行:\n" .
                "   ALTER USER '" . ($this->config['username'] ?? 'root') . "'@'localhost' IDENTIFIED WITH mysql_native_password BY '密码';\n" .
                "   FLUSH PRIVILEGES;"
            );
        }
    }

    public function disconnect() {
        $this->connection = null;
    }

    public function query($sql, $params = []) {
        try {
            if ($this->connection->getAttribute(PDO::ATTR_EMULATE_PREPARES)) {
                $processed = $this->fixLimitOffsetParams($sql, $params);
                $sql = $processed['sql'];
                $params = $processed['params'];
            }

            $stmt = $this->connection->prepare($sql);
            if ($stmt === false) {
                $error = $this->connection->errorInfo();
                throw new Exception("SQL 预处理失败: " . ($error[2] ?? '未知错误'));
            }
            $result = $stmt->execute($params);
            if ($result === false) {
                $error = $stmt->errorInfo();
                throw new Exception("SQL 执行失败: " . ($error[2] ?? '未知错误'));
            }
            return $stmt;
        } catch (PDOException $e) {
            if (
                strpos($e->getMessage(), 'MySQL server has gone away') !== false ||
                strpos($e->getMessage(), 'Lost connection') !== false
            ) {
                $this->connect();
                if ($this->connection->getAttribute(PDO::ATTR_EMULATE_PREPARES)) {
                    $processed = $this->fixLimitOffsetParams($sql, $params);
                    $sql = $processed['sql'];
                    $params = $processed['params'];
                }
                $stmt = $this->connection->prepare($sql);
                if ($stmt === false) {
                    $error = $this->connection->errorInfo();
                    throw new Exception("SQL 预处理失败: " . ($error[2] ?? '未知错误'));
                }
                $stmt->execute($params);
                return $stmt;
            }
            throw new Exception("查询执行失败: " . $e->getMessage());
        }
    }

    private function fixLimitOffsetParams($sql, $params) {
        $sqlTrim = rtrim($sql, "; \t\n\r\0\x0B");

        if (preg_match('/\bLIMIT\s+\?\s*(?:OFFSET|,)\s*\?\s*$/i', $sqlTrim, $m)) {
            $offsetVal = array_pop($params);
            $limitVal = array_pop($params);
            if (is_numeric($limitVal) && is_numeric($offsetVal)) {
                $limitVal = intval($limitVal);
                $offsetVal = intval($offsetVal);
                $sqlTrim = preg_replace(
                    '/\bLIMIT\s+\?\s*(?:OFFSET|,)\s*\?\s*$/i',
                    "LIMIT {$limitVal} OFFSET {$offsetVal}",
                    $sqlTrim
                );
            } else {
                $params[] = $limitVal;
                $params[] = $offsetVal;
            }
        } elseif (preg_match('/\bLIMIT\s+\?\s*$/i', $sqlTrim)) {
            $limitVal = array_pop($params);
            if (is_numeric($limitVal)) {
                $limitVal = intval($limitVal);
                $sqlTrim = preg_replace('/\bLIMIT\s+\?\s*$/i', "LIMIT {$limitVal}", $sqlTrim);
            } else {
                $params[] = $limitVal;
            }
        }

        return ['sql' => $sqlTrim, 'params' => $params];
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
