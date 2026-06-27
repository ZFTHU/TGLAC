<?php
/**
 * 管理后台首页
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$pageTitle = '管理后台';
require_once __DIR__ . '/../includes/admin-header.php';

// 获取统计数据
$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);

$articleModel = new Article($db);
$categoryModel = new Category($db);

$totalArticles = $articleModel->getCount(null, null);
$publishedArticles = $articleModel->getCount(null, true);
$totalCategories = count($categoryModel->getAll());

// 获取最近文章
$recentArticles = $articleModel->getList(1, 5, null, false);
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>仪表盘</h1>
        <p>欢迎回来，<?= htmlspecialchars($_SESSION['username']) ?></p>
    </div>

    <!-- 统计卡片 -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--primary-color);">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $totalArticles ?></div>
                <div class="stat-label">总文章数</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #38a169;">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $publishedArticles ?></div>
                <div class="stat-label">已发布</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: var(--accent-color);">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $totalCategories ?></div>
                <div class="stat-label">分类数</div>
            </div>
        </div>
    </div>

    <!-- 网站维护开关 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>网站维护</h2>
        </div>
        <div style="padding: 20px;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <label class="switch" style="position: relative; display: inline-block; width: 50px; height: 26px;">
                    <input type="checkbox" id="maintenanceModeToggle" style="opacity: 0; width: 0; height: 0;">
                    <span class="slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px;"></span>
                </label>
                <span id="maintenanceStatus" style="font-size: 14px; color: #666;">检测中...</span>
            </div>
            <p style="color: #888; font-size: 13px; margin: 0;">
                开启维护模式后，普通访客将看到"网站维护中"页面，管理员仍可正常访问后台。
            </p>
        </div>
    </div>

    <!-- 最近文章 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>最近文章</h2>
            <a href="/admin/articles.php" class="btn btn-sm btn-outline">查看全部</a>
        </div>
        <div class="admin-table-wrapper">
            <table class="admin-table">
            <thead>
                <tr>
                    <th>标题</th>
                    <th>分类</th>
                    <th>状态</th>
                    <th>浏览</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentArticles)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted);">暂无文章</td>
                </tr>
                <?php else: ?>
                <?php foreach ($recentArticles as $article): ?>
                <tr>
                    <td>
                        <a href="/article.php?id=<?= $article['id'] ?>" target="_blank"><?= htmlspecialchars($article['title']) ?></a>
                    </td>
                    <td><?= htmlspecialchars($article['category_name'] ?? '未分类') ?></td>
                    <td>
                        <span class="badge <?= $article['published'] ? 'badge-success' : 'badge-warning' ?>">
                            <?= $article['published'] ? '已发布' : '草稿' ?>
                        </span>
                    </td>
                    <td><?= $article['view_count'] ?></td>
                    <td><?= date('Y-m-d H:i', strtotime($article['created_at'])) ?></td>
                    <td>
                        <a href="/admin/article-edit.php?id=<?= $article['id'] ?>" class="btn btn-sm btn-outline">编辑</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggle = document.getElementById('maintenanceModeToggle');
    var status = document.getElementById('maintenanceStatus');
    
    // 加载当前状态
    fetch('/api/maintenance.php')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                toggle.checked = data.maintenance_mode;
                updateStatus(data.maintenance_mode);
            }
        });
    
    function updateStatus(enabled) {
        if (enabled) {
            status.textContent = '维护模式已开启';
            status.style.color = '#ef4444';
        } else {
            status.textContent = '维护模式已关闭';
            status.style.color = '#38a169';
        }
    }
    
    // 切换事件
    toggle.addEventListener('change', function() {
        var enabled = toggle.checked;
        status.textContent = '正在切换...';
        
        fetch('/api/maintenance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ enabled: enabled })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                updateStatus(data.maintenance_mode);
                showToast(data.message);
            } else {
                toggle.checked = !enabled;
                showToast(data.message || '操作失败', 'error');
            }
        })
        .catch(function() {
            toggle.checked = !enabled;
            showToast('网络错误', 'error');
        });
    });
    
    function showToast(message, type) {
        var toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;bottom:20px;right:20px;padding:12px 20px;background:' + 
            (type === 'error' ? '#ef4444' : '#38a169') + ';color:#fff;border-radius:8px;' +
            'box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:10000;animation:slideIn 0.3s ease';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(function() {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(function() { toast.remove(); }, 300);
        }, 3000);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
