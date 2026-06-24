<?php
/**
 * 用户管理页面
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$pageTitle = '用户管理';

// 处理操作请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $dbConfig = getDatabaseConfig();
    $db = Database::create($dbConfig);
    $userModel = new User($db);

    if ($_POST['action'] === 'toggle_role' && isset($_POST['id'])) {
        $user = $userModel->getById($_POST['id']);
        if ($user && $user['role'] !== 'admin') {
            $newRole = $user['role'] === 'user' ? 'editor' : 'user';
            $userModel->update($_POST['id'], ['role' => $newRole]);
        }
        header('Location: /admin/users.php');
        exit;
    }

    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        // 不能删除自己
        if ($_POST['id'] !== $_SESSION['user_id']) {
            $db->query("DELETE FROM {$db->prefix}users WHERE id = ?", [$_POST['id']]);
        }
        header('Location: /admin/users.php');
        exit;
    }
}

require_once __DIR__ . '/../includes/admin-header.php';

// 获取用户列表
$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);
$users = $db->fetchAll("SELECT id, username, email, role, created_at FROM {$db->prefix}users ORDER BY created_at DESC");
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>用户管理</h1>
    </div>

    <div class="admin-card">
        <div class="admin-card-body">
            <?php if (empty($users)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <p>暂无注册用户</p>
            </div>
            <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>用户名</th>
                        <th>邮箱</th>
                        <th>角色</th>
                        <th>注册时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($user['username']) ?></strong>
                            <?php if ($user['id'] === $_SESSION['user_id']): ?>
                            <span class="badge badge-primary" style="margin-left: 8px;">当前用户</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <?php
                            $roleMap = [
                                'admin' => ['label' => '管理员', 'class' => 'badge-danger'],
                                'editor' => ['label' => '编辑', 'class' => 'badge-warning'],
                                'user' => ['label' => '用户', 'class' => 'badge-secondary']
                            ];
                            $roleInfo = $roleMap[$user['role']] ?? $roleMap['user'];
                            ?>
                            <span class="badge <?= $roleInfo['class'] ?>"><?= $roleInfo['label'] ?></span>
                        </td>
                        <td><?= date('Y-m-d H:i', strtotime($user['created_at'])) ?></td>
                        <td>
                            <div class="table-actions">
                                <?php if ($user['id'] !== $_SESSION['user_id'] && $user['role'] !== 'admin'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_role">
                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn-icon" title="修改角色">
                                        👤➡️<?= $user['role'] === 'user' ? '📝' : '👤' ?>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('确定要删除这个用户吗？')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn-icon btn-danger" title="删除">🗑️</button>
                                </form>
                                <?php else: ?>
                                <span style="color: var(--text-muted);">-</span>
                                <?php endif; ?>
                            </div>
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
