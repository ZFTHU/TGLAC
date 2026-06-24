<?php
/**
 * 系统设置页面
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

// 获取数据库连接
$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);

// 获取系统设置
$settingsModel = new Settings($db);
$settings = $settingsModel->getAll();

$pageTitle = '系统设置';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>系统设置</h1>
    </div>

    <div class="admin-card">
        <div class="admin-card-body">
            <form id="settings-form" class="admin-form">
                <div class="form-group">
                    <label class="form-label">网站名称</label>
                    <input type="text" name="site_name" class="form-input"
                           value="<?= htmlspecialchars($settings['site_name'] ?? 'My Blog') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">网站描述</label>
                    <textarea name="site_description" class="form-textarea" rows="3"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">网站关键词</label>
                    <input type="text" name="site_keywords" class="form-input"
                           value="<?= htmlspecialchars($settings['site_keywords'] ?? '') ?>"
                           placeholder="多个关键词用逗号分隔">
                </div>

                <div class="form-group">
                    <label class="form-label">每页文章数</label>
                    <input type="number" name="posts_per_page" class="form-input"
                           value="<?= $settings['posts_per_page'] ?? 10 ?>" min="1" max="50">
                </div>

                <div class="form-group">
                    <label class="form-label">页脚文字</label>
                    <textarea name="footer_text" class="form-textarea" rows="2"><?= htmlspecialchars($settings['footer_text'] ?? '') ?></textarea>
                </div>

                <h3 class="form-section-title">看板娘设置</h3>

                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" name="live2d_enabled" value="1" <?= ($settings['live2d_enabled'] ?? 1) ? 'checked' : '' ?>>
                        启用看板娘
                    </label>
                    <p class="form-hint">开启后，网站右下角会显示 Live2D 看板娘</p>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" name="live2d_draggable" value="1" <?= ($settings['live2d_draggable'] ?? 1) ? 'checked' : '' ?>>
                        看板娘可拖动
                    </label>
                    <p class="form-hint">开启后，可以拖动看板娘到任意位置</p>
                </div>

                <div class="admin-form-actions">
                    <button type="submit" class="btn btn-primary">保存设置</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
