<?php
/**
 * 公告管理页面
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$pageTitle = '公告管理';

// 处理删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $dbConfig = getDatabaseConfig();
    $db = Database::create($dbConfig);
    $announcementModel = new Announcement($db);

    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $announcementModel->delete($_POST['id']);
        header('Location: /admin/announcements.php');
        exit;
    }

    if ($_POST['action'] === 'toggle_top' && isset($_POST['id'])) {
        $announcement = $announcementModel->getById($_POST['id']);
        if ($announcement) {
            $announcementModel->update($_POST['id'], [
                'is_top' => $announcement['is_top'] ? 0 : 1
            ]);
        }
        header('Location: /admin/announcements.php');
        exit;
    }

    if ($_POST['action'] === 'toggle_active' && isset($_POST['id'])) {
        $announcement = $announcementModel->getById($_POST['id']);
        if ($announcement) {
            $announcementModel->update($_POST['id'], [
                'is_active' => $announcement['is_active'] ? 0 : 1
            ]);
        }
        header('Location: /admin/announcements.php');
        exit;
    }
}

// 处理创建请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $dbConfig = getDatabaseConfig();
    $db = Database::create($dbConfig);
    $announcementModel = new Announcement($db);

    if (!empty($_POST['title']) && !empty($_POST['content'])) {
        $announcementModel->create([
            'title' => trim($_POST['title']),
            'content' => trim($_POST['content']),
            'image_url' => trim($_POST['image_url'] ?? ''),
            'is_top' => isset($_POST['is_top']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ]);
        header('Location: /admin/announcements.php');
        exit;
    }
}

require_once __DIR__ . '/../includes/admin-header.php';

// 获取公告列表
$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);
$announcementModel = new Announcement($db);

// 确保公告表存在
$announcementModel->ensureTableExists();

$announcements = $announcementModel->getAllAdmin();
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>公告管理</h1>
        <button class="btn btn-accent" onclick="showAnnouncementModal()">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            发布公告
        </button>
    </div>

    <div class="admin-card">
        <div class="admin-card-body">
            <?php if (empty($announcements)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-bottom: 12px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p>暂无公告</p>
            </div>
            <?php else: ?>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                <thead>
                    <tr>
                        <th>标题</th>
                        <th>状态</th>
                        <th>置顶</th>
                        <th>发布时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($announcements as $ann): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($ann['title']) ?></strong>
                        </td>
                        <td>
                            <?php if ($ann['is_active']): ?>
                            <span class="badge badge-success">已发布</span>
                            <?php else: ?>
                            <span class="badge badge-secondary">草稿</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($ann['is_top']): ?>
                            <span class="badge badge-warning">置顶</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('Y-m-d H:i', strtotime($ann['created_at'])) ?></td>
                        <td>
                            <div class="table-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="id" value="<?= $ann['id'] ?>">
                                    <button type="submit" class="btn-icon" title="<?= $ann['is_active'] ? '撤回' : '发布' ?>">
                                        <?= $ann['is_active'] ? '📤' : '📥' ?>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_top">
                                    <input type="hidden" name="id" value="<?= $ann['id'] ?>">
                                    <button type="submit" class="btn-icon" title="<?= $ann['is_top'] ? '取消置顶' : '置顶' ?>">
                                        <?= $ann['is_top'] ? '📌' : '📍' ?>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('确定要删除这条公告吗？')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $ann['id'] ?>">
                                    <button type="submit" class="btn-icon btn-danger" title="删除">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 创建公告模态框 -->
<div id="announcementModalOverlay" class="announcement-modal-overlay">
    <div class="announcement-modal">
        <div class="announcement-modal-header">
            <h3>发布公告</h3>
            <button class="announcement-modal-close" type="button" onclick="hideAnnouncementModal()">&times;</button>
        </div>
        <form method="POST" id="announcementForm">
            <div class="announcement-modal-body">
                <div class="form-group">
                    <label class="form-label">公告标题 <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="title" class="form-input" required placeholder="请输入公告标题" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                </div>
                <div class="form-group">
                    <label class="form-label">公告内容 <span style="color: #ef4444;">*</span></label>
                    <textarea name="content" class="form-textarea" rows="5" required placeholder="请输入公告内容" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; resize: vertical;"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">封面图片（可选）</label>
                    <input type="text" name="image_url" class="form-input" placeholder="请输入图片URL地址（https://...）" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
                    <p style="font-size: 12px; color: #6b7280; margin: 6px 0 0 0;">支持输入网络图片地址，公告中会在标题下方展示</p>
                </div>
                <div class="form-group">
                    <label class="form-label" style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_top" value="1">
                        置顶公告（在列表中优先显示）
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label" style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_active" value="1" checked>
                        立即发布（不勾选则仅保存草稿）
                    </label>
                </div>
            </div>
            <div class="announcement-modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideAnnouncementModal()" style="padding: 8px 20px; border-radius: 8px;">取消</button>
                <button type="submit" name="create" class="btn btn-primary" style="padding: 8px 20px; border-radius: 8px;">发布</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAnnouncementModal() {
    var overlay = document.getElementById('announcementModalOverlay');
    overlay.classList.add('announcement-modal-visible');
    document.body.style.overflow = 'hidden';
}
function hideAnnouncementModal() {
    var overlay = document.getElementById('announcementModalOverlay');
    overlay.classList.remove('announcement-modal-visible');
    document.body.style.overflow = '';
}

// 点击遮罩层外部关闭模态框
document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'announcementModalOverlay') {
        hideAnnouncementModal();
    }
});

// 按 ESC 键关闭
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
        var overlay = document.getElementById('announcementModalOverlay');
        if (overlay && overlay.classList.contains('announcement-modal-visible')) {
            hideAnnouncementModal();
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
