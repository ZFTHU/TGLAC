<?php
/**
 * 数据库配置页面
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

// 获取当前数据库配置
$dbConfig = getDatabaseConfig();

$pageTitle = '数据库配置';
require_once __DIR__ . '/../includes/admin-header.php';

// 获取数据库状态
$dbStatus = [];
if ($dbConfig) {
    try {
        $db = Database::create($dbConfig);
        $dbStatus['connected'] = $db->testConnection();
        $dbStatus['type'] = $dbConfig['type'];
    } catch (Exception $e) {
        $dbStatus['connected'] = false;
        $dbStatus['error'] = $e->getMessage();
    }
}
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>数据库配置</h1>
    </div>

    <!-- 当前状态 -->
    <div class="admin-card" style="margin-bottom: 24px;">
        <div class="admin-card-header">
            <h2>当前数据库状态</h2>
        </div>
        <div class="admin-card-body">
            <?php if ($dbConfig): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <div>
                    <div style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 4px;">数据库类型</div>
                    <div style="font-weight: 600;"><?= strtoupper($dbConfig['type']) ?></div>
                </div>
                <div>
                    <div style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 4px;">连接状态</div>
                    <div style="font-weight: 600; color: <?= $dbStatus['connected'] ? '#38a169' : '#e53e3e' ?>;">
                        <?= $dbStatus['connected'] ? '✓ 已连接' : '✗ 连接失败' ?>
                    </div>
                </div>
                <?php if ($dbConfig['type'] !== 'sqlite'): ?>
                <div>
                    <div style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 4px;">主机</div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($dbConfig['host'] ?? '-') ?></div>
                </div>
                <div>
                    <div style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 4px;">数据库名</div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($dbConfig['database'] ?? '-') ?></div>
                </div>
                <?php else: ?>
                <div>
                    <div style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 4px;">数据库文件</div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($dbConfig['database'] ?? '-') ?></div>
                </div>
                <?php endif; ?>
            </div>
            <?php if (isset($dbStatus['error'])): ?>
            <div style="margin-top: 16px; padding: 12px; background: #fed7d7; color: #c53030; border-radius: 8px; font-size: 0.875rem;">
                错误: <?= htmlspecialchars($dbStatus['error']) ?>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <p style="color: var(--text-muted);">数据库尚未配置</p>
            <?php endif; ?>

            <div style="margin-top: 20px;">
                <button id="start-reset-btn" class="btn btn-primary" style="padding: 10px 20px;">修改数据库配置</button>
            </div>
        </div>
    </div>
</div>

<!-- 确认对话框 -->
<div id="confirm-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 24px; max-width: 500px; width: 90%;">
        <h3 style="margin-bottom: 16px; color: var(--text-primary);">确认重新配置</h3>
        <div style="background: #fed7d7; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
            <p style="color: #c53030; margin-bottom: 8px;"><strong>重要警告：</strong></p>
            <ul style="color: #c53030; padding-left: 20px; font-size: 0.875rem;">
                <li style="margin-bottom: 4px;">重新配置数据库将<strong>清除所有现有数据</strong>（文章、分类、管理员账号等）</li>
                <li style="margin-bottom: 4px;">系统将以新配置<strong>重新初始化数据库</strong></li>
                <li>完成后需要<strong>重新设置管理员账号和网站信息</strong></li>
            </ul>
        </div>
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button id="cancel-reset-btn" class="btn btn-outline">取消</button>
            <button id="confirm-reset-btn" class="btn" style="background: #e53e3e; color: white; border: none;">确认，开始重新配置</button>
        </div>
    </div>
</div>

<script>
document.getElementById('start-reset-btn').addEventListener('click', function() {
    const modal = document.getElementById('confirm-modal');
    modal.style.display = 'flex';
});

document.getElementById('cancel-reset-btn').addEventListener('click', function() {
    const modal = document.getElementById('confirm-modal');
    modal.style.display = 'none';
});

document.getElementById('confirm-reset-btn').addEventListener('click', async function() {
    // 清除本地保存的数据库配置
    localStorage.removeItem('blog_install_db_config');
    // 跳转到简化安装流程
    window.location.href = '/install.php?mode=reset';
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php';
