<?php
/**
 * 安装引导页面
 * 支持两种模式:
 *   - 正常安装: /install.php (从欢迎页开始)
 *   - 重置模式: /install.php?mode=reset (从数据库配置页开始,用于重新配置数据库)
 */

require_once __DIR__ . '/config/config.php';

$isResetMode = isset($_GET['mode']) && $_GET['mode'] === 'reset';

// 如果是重置模式，需要先删除安装标记和数据库配置
if ($isResetMode) {
    // 临时移除安装标记(不删除,仅重命名)
    $installedFile = CONFIG_PATH . '/installed.json';
    if (file_exists($installedFile)) {
        // 先备份
        $backupData = [
            'installed_backup' => file_get_contents($installedFile),
            'database_backup' => file_exists(CONFIG_PATH . '/database.json')
                ? file_get_contents(CONFIG_PATH . '/database.json')
                : null,
            'reset_time' => date('c')
        ];
        file_put_contents(CONFIG_PATH . '/backup_before_reset.json', json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    // 不删除数据库配置,稍后让用户在安装时选择新的数据库
    // 但要确保安装流程能顺利继续,所以临时删除installed.json
    if (file_exists($installedFile)) {
        unlink($installedFile);
    }
}

// 如果已安装且不是重置模式，跳转到首页
if (isInstalled() && !$isResetMode) {
    header('Location: /');
    exit;
}

// 获取当前步骤
$step = $_GET['step'] ?? ($isResetMode ? 'database' : 'welcome');
$steps = $isResetMode ? ['database', 'admin', 'complete'] : ['welcome', 'requirements', 'database', 'admin', 'complete'];
$currentStepIndex = array_search($step, $steps);

// 进度条显示的步骤名称
$progressSteps = $isResetMode ? ['数据库配置', '管理员设置', '完成'] : ['欢迎', '环境检测', '数据库配置', '管理员设置', '完成'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装向导 - 博客系统</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/animations.css">
    <style>
        .install-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);
            padding: 20px;
        }

        .install-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
        }

        .install-header {
            background: var(--primary-color);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .install-header h1 {
            font-family: var(--font-serif);
            font-size: 1.75rem;
            margin-bottom: 8px;
        }

        .install-header p {
            opacity: 0.8;
            font-size: 0.875rem;
        }

        .progress-container {
            padding: 20px 30px;
            background: var(--bg-gray);
            border-bottom: 1px solid var(--border-color);
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--border-color);
            z-index: 1;
        }

        .progress-bar {
            position: absolute;
            top: 15px;
            left: 0;
            height: 2px;
            background: var(--primary-color);
            z-index: 2;
            transition: width 300ms ease;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 3;
        }

        .progress-step-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 8px;
            transition: all 300ms ease;
        }

        .progress-step.active .progress-step-icon {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .progress-step.completed .progress-step-icon {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .progress-step-label {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .progress-step.active .progress-step-label,
        .progress-step.completed .progress-step-label {
            color: var(--primary-color);
            font-weight: 600;
        }

        .install-body {
            padding: 30px;
        }

        .install-step {
            display: none;
        }

        .install-step.active {
            display: block;
            animation: fadeInUp 400ms ease;
        }

        .step-title {
            font-family: var(--font-serif);
            font-size: 1.5rem;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .step-description {
            color: var(--text-secondary);
            margin-bottom: 24px;
        }

        .requirement-list {
            margin-bottom: 24px;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: var(--bg-gray);
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .requirement-item.passed {
            background: #c6f6d5;
        }

        .requirement-item.failed {
            background: #fed7d7;
        }

        .requirement-name {
            font-weight: 500;
        }

        .requirement-status {
            font-size: 0.875rem;
            font-weight: 600;
        }

        .requirement-status.pass {
            color: #38a169;
        }

        .requirement-status.fail {
            color: #e53e3e;
        }

        .install-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
        }

        .welcome-features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: var(--bg-gray);
            border-radius: 8px;
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .feature-text h4 {
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .feature-text p {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .text-success { color: #38a169; }
        .text-error { color: #e53e3e; }
        .text-muted { color: var(--text-muted); }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border-color);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        .install-success, .install-error {
            text-align: center;
            padding: 20px;
        }

        .install-success h3 {
            color: #38a169;
            margin-bottom: 16px;
        }

        .install-error h3 {
            color: #e53e3e;
            margin-bottom: 16px;
        }

        @media (max-width: 640px) {
            .progress-step-label {
                display: none;
            }

            .welcome-features {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-card">
            <div class="install-header">
                <h1><?= $isResetMode ? '数据库重新配置' : '博客系统安装向导' ?></h1>
                <p><?= $isResetMode ? '重新配置数据库连接，现有数据将被重置' : '欢迎使用博客系统，让我们开始安装吧' ?></p>
            </div>

            <div class="progress-container">
                <div class="progress-steps">
                    <div class="progress-bar" style="width: 0%"></div>
                    <?php if ($isResetMode): ?>
                    <div class="progress-step <?= $currentStepIndex >= 0 ? ($currentStepIndex > 0 ? 'completed' : 'active') : '' ?>">
                        <div class="progress-step-icon">1</div>
                        <span class="progress-step-label">数据库</span>
                    </div>
                    <div class="progress-step <?= $currentStepIndex >= 1 ? ($currentStepIndex > 1 ? 'completed' : 'active') : '' ?>">
                        <div class="progress-step-icon">2</div>
                        <span class="progress-step-label">管理员</span>
                    </div>
                    <div class="progress-step <?= $currentStepIndex >= 2 ? 'active' : '' ?>">
                        <div class="progress-step-icon">3</div>
                        <span class="progress-step-label">完成</span>
                    </div>
                    <?php else: ?>
                    <div class="progress-step <?= $currentStepIndex >= 0 ? ($currentStepIndex > 0 ? 'completed' : 'active') : '' ?>">
                        <div class="progress-step-icon">1</div>
                        <span class="progress-step-label">欢迎</span>
                    </div>
                    <div class="progress-step <?= $currentStepIndex >= 1 ? ($currentStepIndex > 1 ? 'completed' : 'active') : '' ?>">
                        <div class="progress-step-icon">2</div>
                        <span class="progress-step-label">环境检测</span>
                    </div>
                    <div class="progress-step <?= $currentStepIndex >= 2 ? ($currentStepIndex > 2 ? 'completed' : 'active') : '' ?>">
                        <div class="progress-step-icon">3</div>
                        <span class="progress-step-label">数据库</span>
                    </div>
                    <div class="progress-step <?= $currentStepIndex >= 3 ? ($currentStepIndex > 3 ? 'completed' : 'active') : '' ?>">
                        <div class="progress-step-icon">4</div>
                        <span class="progress-step-label">管理员</span>
                    </div>
                    <div class="progress-step <?= $currentStepIndex >= 4 ? 'active' : '' ?>">
                        <div class="progress-step-icon">5</div>
                        <span class="progress-step-label">完成</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="install-body">
                <!-- 步骤1: 欢迎 -->
                <div class="install-step <?= $step === 'welcome' ? 'active' : '' ?>">
                    <h2 class="step-title">欢迎使用博客系统</h2>
                    <p class="step-description">这是一个现代化的个人博客系统，支持多种数据库，开箱即用。</p>

                    <div class="welcome-features">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <h4>文章管理</h4>
                                <p>支持Markdown写作</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7z"></path>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <h4>多数据库</h4>
                                <p>MySQL/MongoDB/SQLite</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <h4>响应式设计</h4>
                                <p>完美适配移动端</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <h4>流畅动画</h4>
                                <p>优雅的交互体验</p>
                            </div>
                        </div>
                    </div>

                    <div class="install-actions">
                        <div></div>
                        <a href="?step=requirements" class="btn btn-primary btn-lg">开始安装</a>
                    </div>
                </div>

                <!-- 步骤2: 环境检测 -->
                <div class="install-step <?= $step === 'requirements' ? 'active' : '' ?>">
                    <h2 class="step-title">环境检测</h2>
                    <p class="step-description">检测您的服务器环境是否满足安装要求。</p>

                    <div class="requirement-list">
                        <?php
                        $requirements = [
                            'PHP版本 >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>='),
                            'PDO扩展' => extension_loaded('pdo'),
                            'JSON扩展' => extension_loaded('json'),
                            '配置目录可写' => is_writable(CONFIG_PATH) || is_writable(dirname(CONFIG_PATH)),
                            '上传目录可写' => is_writable(ROOT_PATH) || (is_dir(UPLOADS_PATH) && is_writable(UPLOADS_PATH)),
                        ];

                        $optional = [
                            'PDO MySQL驱动 (MySQL时需要)' => extension_loaded('pdo_mysql'),
                            'Mbstring扩展' => extension_loaded('mbstring'),
                        ];

                        $allPassed = true;
                        foreach ($requirements as $name => $passed):
                            if (!$passed) $allPassed = false;
                        ?>
                        <div class="requirement-item <?= $passed ? 'passed' : 'failed' ?>">
                            <span class="requirement-name"><?= $name ?></span>
                            <span class="requirement-status <?= $passed ? 'pass' : 'fail' ?>">
                                <?= $passed ? '✓ 通过' : '✗ 失败' ?>
                            </span>
                        </div>
                        <?php endforeach; ?>

                        <?php foreach ($optional as $name => $passed): ?>
                        <div class="requirement-item <?= $passed ? 'passed' : 'warning' ?>">
                            <span class="requirement-name"><?= $name ?></span>
                            <span class="requirement-status <?= $passed ? 'pass' : 'warn' ?>">
                                <?= $passed ? '✓ 通过' : '⚠ 可选' ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!extension_loaded('pdo_mysql')): ?>
                    <div style="padding: 12px; background: #fef5e7; border-radius: 8px; margin-bottom: 20px; color: #d69e2e;">
                        ⚠ 提示：PDO MySQL驱动未启用。如果您选择MySQL数据库，请先启用此扩展。建议使用SQLite数据库（无需额外配置）。
                    </div>
                    <?php endif; ?>

                    <div class="install-actions">
                        <a href="?step=welcome" class="btn btn-outline">上一步</a>
                        <?php if ($allPassed): ?>
                        <a href="?step=database" class="btn btn-primary">下一步</a>
                        <?php else: ?>
                        <button class="btn btn-primary" disabled>环境不满足要求</button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 步骤3: 数据库配置 -->
                <div class="install-step <?= $step === 'database' ? 'active' : '' ?>">
                    <h2 class="step-title">数据库配置</h2>
                    <p class="step-description">配置您的数据库连接信息。</p>

                    <form id="database-form">
                        <div class="form-group">
                            <label class="form-label">数据库类型</label>
                            <select id="db_type" class="form-select">
                                <option value="sqlite">SQLite (推荐)</option>
                                <option value="mysql">MySQL</option>
                                <option value="mongodb">MongoDB</option>
                            </select>
                        </div>

                        <!-- MySQL字段 -->
                        <div class="mysql-fields" style="display: none;">
                            <div class="form-group">
                                <label class="form-label">主机地址</label>
                                <input type="text" id="db_host" class="form-input" value="localhost">
                            </div>
                            <div class="form-group">
                                <label class="form-label">端口</label>
                                <input type="number" id="db_port" class="form-input" value="3306">
                            </div>
                            <div class="form-group">
                                <label class="form-label">用户名</label>
                                <input type="text" id="db_username" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">密码</label>
                                <input type="password" id="db_password" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">数据库名</label>
                                <input type="text" id="db_name" class="form-input" required>
                            </div>
                        </div>

                        <!-- SQLite字段 -->
                        <div class="sqlite-fields">
                            <div class="form-group">
                                <label class="form-label">数据库文件路径</label>
                                <input type="text" id="db_path" class="form-input" value="data/blog.db">
                                <small class="text-muted">相对于项目根目录</small>
                            </div>
                        </div>

                        <!-- MongoDB字段 -->
                        <div class="mongodb-fields" style="display: none;">
                            <div class="form-group">
                                <label class="form-label">主机地址</label>
                                <input type="text" id="db_host_mongo" class="form-input" value="localhost">
                            </div>
                            <div class="form-group">
                                <label class="form-label">端口</label>
                                <input type="number" id="db_port_mongo" class="form-input" value="27017">
                            </div>
                            <div class="form-group">
                                <label class="form-label">用户名</label>
                                <input type="text" id="db_username_mongo" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">密码</label>
                                <input type="password" id="db_password_mongo" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">数据库名</label>
                                <input type="text" id="db_name_mongo" class="form-input" required>
                            </div>
                        </div>

                        <div id="db-test-result" style="margin-top: 16px;"></div>
                    </form>

                    <div class="install-actions">
                        <?php if ($isResetMode): ?>
                        <a href="/" class="btn btn-outline">取消</a>
                        <?php else: ?>
                        <a href="?step=requirements" class="btn btn-outline">上一步</a>
                        <?php endif; ?>
                        <div>
                            <button type="button" id="test-db-btn" class="btn btn-outline" style="margin-right: 8px;">测试连接</button>
                            <a href="?step=admin<?= $isResetMode ? '&mode=reset' : '' ?>" class="btn btn-primary">下一步</a>
                        </div>
                    </div>
                </div>

                <!-- 步骤4: 管理员设置 -->
                <div class="install-step <?= $step === 'admin' ? 'active' : '' ?>">
                    <h2 class="step-title">管理员设置</h2>
                    <p class="step-description">创建您的管理员账号。</p>

                    <form id="admin-form">
                        <div class="form-group">
                            <label class="form-label">网站名称</label>
                            <input type="text" id="site_name" class="form-input" value="My Blog">
                        </div>
                        <div class="form-group">
                            <label class="form-label">网站描述</label>
                            <textarea id="site_description" class="form-textarea" rows="2">一个现代化的博客系统</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">管理员用户名 *</label>
                            <input type="text" id="admin_username" class="form-input" required minlength="3">
                        </div>
                        <div class="form-group">
                            <label class="form-label">管理员邮箱 *</label>
                            <input type="email" id="admin_email" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">管理员密码 *</label>
                            <input type="password" id="admin_password" class="form-input" required minlength="6">
                            <small class="text-muted">至少6个字符</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">确认密码 *</label>
                            <input type="password" id="admin_password_confirm" class="form-input" required>
                        </div>
                    </form>

                    <div class="install-actions">
                        <a href="?step=database<?= $isResetMode ? '&mode=reset' : '' ?>" class="btn btn-outline">上一步</a>
                        <button type="button" id="install-btn" class="btn btn-primary"><?= $isResetMode ? '重新安装' : '开始安装' ?></button>
                    </div>

                    <input type="hidden" id="install-mode" value="<?= $isResetMode ? 'reset' : 'normal' ?>">
                    <div id="install-status" style="margin-top: 24px;"></div>
                </div>

                <!-- 步骤5: 完成 -->
                <div class="install-step <?= $step === 'complete' ? 'active' : '' ?>">
                    <div class="install-success">
                        <svg width="64" height="64" fill="none" stroke="#38a169" viewBox="0 0 24 24" style="margin-bottom: 16px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3>安装成功！</h3>
                        <p>您的博客系统已成功安装。</p>
                        <div style="margin-top: 24px;">
                            <a href="/" class="btn btn-primary" style="margin-right: 8px;">访问首页</a>
                            <a href="/login.php" class="btn btn-outline">登录后台</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/install.js"></script>
</body>
</html>
