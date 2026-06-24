<?php
/**
 * 数据库抽象类
 */
abstract class Database {
    protected $connection;
    protected $config;
    protected $type;

    public function __construct($config) {
        $this->config = $config;
        $this->connect();
    }

    /**
     * 连接数据库
     */
    abstract protected function connect();

    /**
     * 断开连接
     */
    abstract public function disconnect();

    /**
     * 执行查询
     */
    abstract public function query($sql, $params = []);

    /**
     * 获取单行结果
     */
    abstract public function fetchOne($sql, $params = []);

    /**
     * 获取所有结果
     */
    abstract public function fetchAll($sql, $params = []);

    /**
     * 获取最后插入的ID
     */
    abstract public function lastInsertId();

    /**
     * 开始事务
     */
    abstract public function beginTransaction();

    /**
     * 提交事务
     */
    abstract public function commit();

    /**
     * 回滚事务
     */
    abstract public function rollback();

    /**
     * 转义字符串
     */
    abstract public function escape($string);

    /**
     * 测试连接
     */
    abstract public function testConnection();

    /**
     * 获取数据库类型
     */
    public function getType() {
        return $this->type;
    }

    /**
     * 创建数据库实例
     */
    public static function create($config) {
        $type = $config['type'] ?? 'mysql';

        switch ($type) {
            case 'mysql':
                return new MySQL($config);
            case 'sqlite':
                return new SQLite($config);
            case 'mongodb':
                return new MongoDBDriver($config);
            default:
                throw new Exception("不支持的数据库类型: {$type}");
        }
    }
}
