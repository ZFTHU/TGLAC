<?php
/**
 * 执行安装API
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => '无效的请求方法'], 405);
}

// 获取请求数据
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(['success' => false, 'message' => '无效的请求数据'], 400);
}

// 检查是否已安装（reset模式允许）
$isResetMode = isset($input['mode']) && $input['mode'] === 'reset';
if (isInstalled() && !$isResetMode) {
    jsonResponse(['success' => false, 'message' => '系统已安装']);
}

try {
    // 验证必填字段
    if (empty($input['admin']['username']) || empty($input['admin']['email']) || empty($input['admin']['password'])) {
        throw new Exception('请填写完整的管理员信息');
    }

    if (strlen($input['admin']['password']) < 6) {
        throw new Exception('密码至少需要6个字符');
    }

    // 准备数据库配置
    $dbConfig = [
        'type' => $input['database']['type'] ?? 'sqlite'
    ];

    switch ($dbConfig['type']) {
        case 'mysql':
            $dbConfig['host'] = $input['database']['host'] ?? 'localhost';
            $dbConfig['port'] = $input['database']['port'] ?? 3306;
            $dbConfig['username'] = $input['database']['username'] ?? '';
            $dbConfig['password'] = $input['database']['password'] ?? '';
            $dbConfig['database'] = $input['database']['database'] ?? '';
            break;

        case 'sqlite':
            $dbConfig['database'] = $input['database']['path'] ?? $input['database']['database'] ?? 'data/blog.db';
            break;

        case 'mongodb':
            $dbConfig['host'] = $input['database']['host'] ?? 'localhost';
            $dbConfig['port'] = $input['database']['port'] ?? 27017;
            $dbConfig['username'] = $input['database']['username'] ?? '';
            $dbConfig['password'] = $input['database']['password'] ?? '';
            $dbConfig['database'] = $input['database']['database'] ?? '';
            break;
    }

    // 保存数据库配置
    $configDir = CONFIG_PATH;
    if (!is_dir($configDir)) {
        mkdir($configDir, 0755, true);
    }

    file_put_contents($configDir . '/database.json', json_encode($dbConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // 创建数据库连接
    $db = Database::create($dbConfig);

    // reset模式：先删除所有旧表
    if ($isResetMode) {
        dropTables($db, $dbConfig['type']);
    }

    // 创建数据表
    createTables($db, $dbConfig['type']);

    // 创建管理员账号
    $userModel = new User($db);
    $userId = generateUUID();
    $userModel->create([
        'id' => $userId,
        'username' => $input['admin']['username'],
        'email' => $input['admin']['email'],
        'password' => $input['admin']['password'],
        'role' => 'admin'
    ]);

    // 创建默认分类
    $categoryModel = new Category($db);
    $categoryId = generateUUID();
    $categoryModel->create([
        'id' => $categoryId,
        'name' => '默认分类',
        'slug' => 'default',
        'description' => '默认文章分类'
    ]);

    // 创建示例文章
    $articleModel = new Article($db);
    $articleId = generateUUID();
    $articleModel->create([
        'id' => $articleId,
        'title' => '欢迎使用博客系统',
        'slug' => 'welcome',
        'content' => "# 欢迎使用博客系统\n\n这是一个现代化的博客系统，支持以下功能：\n\n- 文章发布与管理\n- 多种数据库支持\n- 响应式设计\n- 流畅的动画效果\n\n开始您的写作之旅吧！",
        'excerpt' => '欢迎使用博客系统，开始您的写作之旅！',
        'category_id' => $categoryId,
        'author_id' => $userId,
        'published' => true
    ]);

    // 初始化系统设置
    $settingsModel = new Settings($db);
    $settingsModel->initDefaults(generateUUID());
    $settingsModel->update([
        'site_name' => $input['site']['name'] ?? 'My Blog',
        'site_description' => $input['site']['description'] ?? ''
    ]);

    // 创建上传目录
    $uploadDirs = [
        UPLOADS_PATH,
        UPLOADS_PATH . '/images',
        UPLOADS_PATH . '/images/articles',
        UPLOADS_PATH . '/images/covers',
        UPLOADS_PATH . '/images/avatars',
        UPLOADS_PATH . '/temp'
    ];

    foreach ($uploadDirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    // 创建安装标记文件
    $installedData = [
        'installed' => true,
        'version' => APP_VERSION,
        'installedAt' => date('c')
    ];

    file_put_contents($configDir . '/installed.json', json_encode($installedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    jsonResponse(['success' => true, 'message' => '安装成功']);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * 删除所有数据表（用于重置模式）
 */
function dropTables($db, $type) {
    $tables = ['notifications', 'announcements', 'settings', 'comments', 'articles', 'categories', 'users'];
    foreach ($tables as $table) {
        try {
            $db->query("DROP TABLE IF EXISTS {$table}");
        } catch (Exception $e) {
        }
    }
}

/**
 * 创建数据表
 */
function createTables($db, $type) {
    if ($type === 'mysql') {
        // MySQL表结构
        $tables = [
            "CREATE TABLE IF NOT EXISTS users (
                id VARCHAR(36) PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM('admin', 'editor', 'user') DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_username (username),
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS categories (
                id VARCHAR(36) PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) UNIQUE NOT NULL,
                description TEXT,
                article_count INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS articles (
                id VARCHAR(36) PRIMARY KEY,
                title VARCHAR(200) NOT NULL,
                slug VARCHAR(200) UNIQUE NOT NULL,
                content LONGTEXT NOT NULL,
                excerpt TEXT,
                cover_image VARCHAR(500),
                category_id VARCHAR(36) NOT NULL,
                tags TEXT,
                author_id VARCHAR(36) NOT NULL,
                view_count INT DEFAULT 0,
                published BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_slug (slug),
                INDEX idx_category (category_id),
                INDEX idx_author (author_id),
                INDEX idx_published (published),
                FOREIGN KEY (category_id) REFERENCES categories(id),
                FOREIGN KEY (author_id) REFERENCES users(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS comments (
                id VARCHAR(36) PRIMARY KEY,
                article_id VARCHAR(36) NOT NULL,
                user_id VARCHAR(36),
                author_name VARCHAR(100) NOT NULL,
                author_email VARCHAR(100) NOT NULL,
                author_type ENUM('user', 'guest') DEFAULT 'guest',
                guest_id VARCHAR(32),
                content TEXT NOT NULL,
                parent_id VARCHAR(36),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_article (article_id),
                FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS settings (
                id VARCHAR(36) PRIMARY KEY,
                site_name VARCHAR(100) DEFAULT 'My Blog',
                site_description TEXT,
                site_keywords TEXT,
                footer_text TEXT,
                posts_per_page INT DEFAULT 10,
                live2d_enabled INT DEFAULT 1,
                live2d_draggable INT DEFAULT 1,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS announcements (
                id VARCHAR(36) PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                image_url VARCHAR(500) DEFAULT '',
                is_top TINYINT(1) DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_is_active (is_active),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS notifications (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) DEFAULT 'ALL',
                title VARCHAR(255) NOT NULL,
                content TEXT,
                type VARCHAR(20) DEFAULT 'info',
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_is_read (is_read),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ];
    } else {
        // SQLite表结构
        $tables = [
            "CREATE TABLE IF NOT EXISTS users (
                id TEXT PRIMARY KEY,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT DEFAULT 'admin',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE IF NOT EXISTS categories (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                description TEXT,
                article_count INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE IF NOT EXISTS articles (
                id TEXT PRIMARY KEY,
                title TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                content TEXT NOT NULL,
                excerpt TEXT,
                cover_image TEXT,
                category_id TEXT NOT NULL,
                tags TEXT,
                author_id TEXT NOT NULL,
                view_count INTEGER DEFAULT 0,
                published INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id),
                FOREIGN KEY (author_id) REFERENCES users(id)
            )",

            "CREATE TABLE IF NOT EXISTS comments (
                id TEXT PRIMARY KEY,
                article_id TEXT NOT NULL,
                user_id TEXT,
                author_name TEXT NOT NULL,
                author_email TEXT NOT NULL,
                author_type TEXT DEFAULT 'guest',
                guest_id TEXT,
                content TEXT NOT NULL,
                parent_id TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
            )",

            "CREATE TABLE IF NOT EXISTS settings (
                id TEXT PRIMARY KEY,
                site_name TEXT DEFAULT 'My Blog',
                site_description TEXT,
                site_keywords TEXT,
                footer_text TEXT,
                posts_per_page INTEGER DEFAULT 10,
                live2d_enabled INTEGER DEFAULT 1,
                live2d_draggable INTEGER DEFAULT 1,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE IF NOT EXISTS announcements (
                id TEXT PRIMARY KEY,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                image_url TEXT DEFAULT '',
                is_top INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE IF NOT EXISTS notifications (
                id TEXT PRIMARY KEY,
                user_id TEXT DEFAULT 'ALL',
                title TEXT NOT NULL,
                content TEXT,
                type TEXT DEFAULT 'info',
                is_read INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )"
        ];
    }

    foreach ($tables as $sql) {
        $db->query($sql);
    }
}
