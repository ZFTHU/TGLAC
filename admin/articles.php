<?php
/**
 * 文章管理页面
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$pageTitle = '文章管理';
require_once __DIR__ . '/../includes/admin-header.php';

// 获取数据库连接
$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);

// 获取文章列表
$articleModel = new Article($db);
$articles = $articleModel->getList(1, 50, null, false);
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>文章管理</h1>
        <a href="/admin/article-edit.php" class="btn btn-primary">新建文章</a>
    </div>

    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>标题</th>
                    <th>分类</th>
                    <th>作者</th>
                    <th>状态</th>
                    <th>浏览</th>
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($articles)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px;">
                        暂无文章，<a href="/admin/article-edit.php">点击创建第一篇文章</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($articles as $article): ?>
                <tr>
                    <td>
                        <a href="/article.php?id=<?= $article['id'] ?>" target="_blank" style="font-weight: 500;">
                            <?= htmlspecialchars($article['title']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($article['category_name'] ?? '未分类') ?></td>
                    <td><?= htmlspecialchars($article['author_name']) ?></td>
                    <td>
                        <span class="badge <?= $article['published'] ? 'badge-success' : 'badge-warning' ?>">
                            <?= $article['published'] ? '已发布' : '草稿' ?>
                        </span>
                    </td>
                    <td><?= $article['view_count'] ?></td>
                    <td><?= date('Y-m-d H:i', strtotime($article['created_at'])) ?></td>
                    <td>
                        <a href="/admin/article-edit.php?id=<?= $article['id'] ?>" class="btn btn-sm btn-outline">编辑</a>
                        <button onclick="deleteArticle('<?= $article['id'] ?>')" class="btn btn-sm btn-outline" style="color: #e53e3e;">删除</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
