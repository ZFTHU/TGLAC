<?php
/**
 * 站内通知管理页面
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$pageTitle = '站内通知';

// 处理发送通知请求
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $dbConfig = getDatabaseConfig();
    $db = Database::create($dbConfig);
    $notificationModel = new Notification($db);
    $notificationModel->ensureTableExists();

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $target = $_POST['target'] ?? 'all';
    $type = $_POST['type'] ?? 'info';
    $specificUser = trim($_POST['specific_user'] ?? '');

    if (!empty($title)) {
        if ($target === 'all') {
            // 广播给所有用户
            $notificationModel->sendToAll($title, $content, $type);
            $message = '✓ 已发送广播通知给所有用户';
            $messageType = 'success';
        } elseif ($target === 'user' && !empty($specificUser)) {
            // 发送给指定用户（支持用户名或用户ID）
            $userModel = new User($db);
            $targetUser = $userModel->getByUsername($specificUser);
            if (!$targetUser) {
                // 尝试按 ID 查找
                $targetUser = $userModel->getById($specificUser);
            }
            if ($targetUser) {
                $notificationModel->sendToUser($targetUser['id'], $title, $content, $type);
                $message = '✓ 已发送通知给用户：' . htmlspecialchars($targetUser['username']);
                $messageType = 'success';
            } else {
                $message = '✗ 未找到指定用户';
                $messageType = 'error';
            }
        }
    } else {
        $message = '✗ 通知标题不能为空';
        $messageType = 'error';
    }
}

// 处理删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $dbConfig = getDatabaseConfig();
    $db = Database::create($dbConfig);
    $notificationModel = new Notification($db);
    if (isset($_POST['id'])) {
        $notificationModel->delete($_POST['id']);
    }
    header('Location: /admin/notifications.php');
    exit;
}

require_once __DIR__ . '/../includes/admin-header.php';

// 获取通知列表
$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);
$notificationModel = new Notification($db);
$notificationModel->ensureTableExists();
$notifications = $notificationModel->getAll(100);

// 获取用户列表（用于选择发送对象）
$userModel = new User($db);
$allUsers = [];
try {
    $allUsers = $db->fetchAll("SELECT id, username, email FROM users ORDER BY created_at DESC", []);
} catch (Exception $e) {
    $allUsers = [];
}
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>站内通知</h1>
        <button class="btn btn-accent" onclick="document.getElementById('sendFormSection').scrollIntoView({behavior: 'smooth'})">
            📨 发送新通知
        </button>
    </div>

    <?php if (!empty($message)): ?>
    <div class="admin-card" style="margin-bottom: 16px; background: <?= $messageType === 'success' ? '#ecfdf5' : '#fef2f2' ?>; border: 1px solid <?= $messageType === 'success' ? '#a7f3d0' : '#fecaca' ?>;">
        <div style="color: <?= $messageType === 'success' ? '#047857' : '#b91c1c' ?>; font-size: 0.9rem;">
            <?= htmlspecialchars($message) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 发送通知表单 -->
    <div id="sendFormSection" class="admin-card" style="margin-bottom: 24px;">
        <div class="admin-card-header">
            <h2 style="font-size: 1.1rem; margin: 0;">发送新通知</h2>
        </div>
        <div class="admin-card-body">
            <form method="POST">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 500; font-size: 0.875rem; margin-bottom: 6px;">发送对象</label>
                    <div style="display: flex; gap: 20px; margin-bottom: 8px;">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="target" value="all" checked onchange="document.getElementById('userSelect').style.display='none'">
                            全体用户（广播）
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="target" value="user" onchange="document.getElementById('userSelect').style.display='flex'">
                            指定用户
                        </label>
                    </div>
                    <div id="userSelect" style="display: none; gap: 8px; align-items: center;">
                        <?php if (!empty($allUsers)): ?>
                        <select name="specific_user" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; min-width: 240px;">
                            <?php foreach ($allUsers as $u): ?>
                            <option value="<?= htmlspecialchars($u['username']) ?>">
                                <?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['email']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <span style="color: #6b7280;">暂无可选用户</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 500; font-size: 0.875rem; margin-bottom: 6px;">通知类型</label>
                    <select name="type" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; min-width: 160px;">
                        <option value="info">ℹ️ 一般通知</option>
                        <option value="success">✅ 成功通知</option>
                        <option value="warning">⚠️ 警告通知</option>
                        <option value="error">❌ 错误通知</option>
                    </select>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 500; font-size: 0.875rem; margin-bottom: 6px;">通知标题 <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="title" required placeholder="例如：系统维护通知" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 500; font-size: 0.875rem; margin-bottom: 6px;">通知内容</label>
                    <textarea name="content" rows="4" placeholder="请输入通知详细内容..." style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; resize: vertical; box-sizing: border-box;"></textarea>
                </div>

                <div style="text-align: right;">
                    <button type="submit" name="send_notification" class="btn btn-primary" style="padding: 10px 24px;">
                        📨 发送通知
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 通知列表 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 style="font-size: 1.1rem; margin: 0;">通知记录（共 <?= count($notifications) ?> 条）</h2>
        </div>
        <div class="admin-card-body">
            <?php if (empty($notifications)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <p>暂无通知记录</p>
            </div>
            <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>标题</th>
                        <th>接收对象</th>
                        <th>类型</th>
                        <th>发送时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $notif): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($notif['title']) ?></strong>
                            <?php if (!empty($notif['content'])): ?>
                            <div style="color: var(--text-muted); font-size: 0.8rem; margin-top: 4px;"><?= htmlspecialchars(mb_substr($notif['content'], 0, 60)) ?><?= mb_strlen($notif['content']) > 60 ? '...' : '' ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($notif['user_id'] === 'ALL'): ?>
                            <span class="badge badge-warning">全体广播</span>
                            <?php else: ?>
                            <span class="badge badge-secondary">
                                <?php
                                $u = $userModel->getById($notif['user_id']);
                                echo htmlspecialchars($u['username'] ?? $notif['user_id']);
                                ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $typeMap = [
                                'info' => ['label' => '一般', 'class' => 'badge-primary'],
                                'success' => ['label' => '成功', 'class' => 'badge-success'],
                                'warning' => ['label' => '警告', 'class' => 'badge-warning'],
                                'error' => ['label' => '错误', 'class' => 'badge-danger']
                            ];
                            $typeInfo = $typeMap[$notif['type']] ?? $typeMap['info'];
                            ?>
                            <span class="badge <?= $typeInfo['class'] ?>"><?= $typeInfo['label'] ?></span>
                        </td>
                        <td><?= date('Y-m-d H:i', strtotime($notif['created_at'])) ?></td>
                        <td>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('确定要删除这条通知吗？')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $notif['id'] ?>">
                                <button type="submit" class="btn-icon btn-danger" title="删除">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
