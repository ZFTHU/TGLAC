<?php
/**
 * 用户站内通知列表
 */

require_once __DIR__ . '/../config/config.php';

// 必须登录才能查看通知
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$pageTitle = '我的通知';

$userId = $_SESSION['user_id'];

// 处理标记已读操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbConfig = getDatabaseConfig();
    $db = Database::create($dbConfig);
    $notificationModel = new Notification($db);
    $notificationModel->ensureTableExists();

    if (isset($_POST['mark_all_read'])) {
        $notificationModel->markAllAsRead($userId);
        header('Location: /pages/notifications.php');
        exit;
    }

    if (isset($_POST['mark_read']) && isset($_POST['id'])) {
        $notificationModel->markAsRead($_POST['id'], $userId);
        header('Location: /pages/notifications.php');
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';

// 获取通知列表
$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);
$notificationModel = new Notification($db);
$notificationModel->ensureTableExists();
$notifications = $notificationModel->getForUser($userId, 100);
$unreadCount = $notificationModel->getUnreadCount($userId);
?>

<div class="container">
    <div class="main-layout">
        <!-- 左栏：用户信息区（与其他页面保持一致） -->
        <aside class="site-left-panel">
            <div class="site-info-card">
                <div class="site-avatar">
                    <?= mb_substr($_SESSION['user_name'] ?? 'U', 0, 1) ?>
                </div>
                <h2 class="site-name"><?= htmlspecialchars($_SESSION['user_name']) ?></h2>
                <p class="site-desc">
                    <?php
                    $roleMap = ['admin' => '管理员', 'editor' => '编辑', 'user' => '普通用户'];
                    echo $roleMap[$_SESSION['user_role']] ?? '普通用户';
                    ?>
                </p>

                <!-- 通知摘要 -->
                <div style="margin: 16px 0; padding: 12px; background: rgba(255,255,255,0.08); border-radius: 8px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: #fff;"><?= $unreadCount ?></div>
                    <div style="font-size: 0.8rem; color: rgba(255,255,255,0.7);">未读通知</div>
                </div>

                <?php if ($unreadCount > 0): ?>
                <form method="POST" style="width: 100%;">
                    <button type="submit" name="mark_all_read" class="btn btn-outline" style="width: 100%; background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.3); color: #fff;">
                        📖 全部标记为已读
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </aside>

        <!-- 中栏：通知列表 -->
        <main class="site-main-section">
            <div class="sidebar-widget" style="padding: 24px;">
                <h1 style="font-family: var(--font-serif); font-size: 1.75rem; margin-bottom: var(--spacing-sm); color: var(--text-primary);">
                    🔔 我的通知
                </h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0 0 20px 0;">
                    共 <?= count($notifications) ?> 条通知 · 未读 <strong style="color: var(--accent-color);"><?= $unreadCount ?></strong> 条
                </p>

                <?php if (empty($notifications)): ?>
                <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                    <div style="font-size: 3rem; margin-bottom: 12px;">📭</div>
                    <p style="font-size: 1.05rem; margin-bottom: 8px;">暂无通知</p>
                    <p style="font-size: 0.9rem; color: var(--text-muted);">当有新消息时，会在这里显示</p>
                </div>
                <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($notifications as $notif): ?>
                    <?php
                    $typeStyles = [
                        'info' => ['icon' => 'ℹ️', 'border' => '#3b82f6', 'bg' => '#eff6ff'],
                        'success' => ['icon' => '✅', 'border' => '#10b981', 'bg' => '#ecfdf5'],
                        'warning' => ['icon' => '⚠️', 'border' => '#f59e0b', 'bg' => '#fffbeb'],
                        'error' => ['icon' => '❌', 'border' => '#ef4444', 'bg' => '#fef2f2']
                    ];
                    $style = $typeStyles[$notif['type']] ?? $typeStyles['info'];
                    $isUnread = empty($notif['is_read']);
                    ?>
                    <div style="
                        padding: 16px 20px;
                        background: <?= $style['bg'] ?>;
                        border-left: 4px solid <?= $style['border'] ?>;
                        border-radius: 8px;
                        position: relative;
                        <?php if ($isUnread): ?>
                        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
                        <?php endif; ?>
                    ">
                        <div style="display: flex; align-items: flex-start; gap: 12px;">
                            <div style="font-size: 1.5rem; flex-shrink: 0;"><?= $style['icon'] ?></div>
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                    <h3 style="font-size: 1rem; margin: 0; color: #111827; font-weight: 600;"><?= htmlspecialchars($notif['title']) ?></h3>
                                    <?php if ($isUnread): ?>
                                    <span style="background: #ef4444; color: white; font-size: 0.65rem; padding: 2px 8px; border-radius: 999px; font-weight: 500;">NEW</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($notif['content'])): ?>
                                <p style="color: #374151; font-size: 0.875rem; line-height: 1.6; margin: 6px 0 10px 0; white-space: pre-wrap;"><?= htmlspecialchars($notif['content']) ?></p>
                                <?php endif; ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #6b7280;">
                                    <span>📅 <?= date('Y-m-d H:i', strtotime($notif['created_at'])) ?></span>
                                    <?php if ($isUnread): ?>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="id" value="<?= $notif['id'] ?>">
                                        <button type="submit" name="mark_read" style="background: none; border: none; color: #3b82f6; cursor: pointer; font-size: 0.75rem; padding: 0;">
                                            标记已读
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
