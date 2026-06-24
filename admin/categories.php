<?php
/**
 * 分类管理页面
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$pageTitle = '分类管理';
require_once __DIR__ . '/../includes/admin-header.php';

// 获取数据库连接
$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);

// 获取分类列表
$categoryModel = new Category($db);
$categories = $categoryModel->getAll();
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>分类管理</h1>
    </div>

    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 24px;">
        <!-- 新建分类表单 -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>新建分类</h2>
            </div>
            <div class="admin-card-body">
                <form id="category-form" class="admin-form">
                    <input type="hidden" name="id" id="category-id">

                    <div class="form-group">
                        <label class="form-label">名称 *</label>
                        <input type="text" name="name" id="category-name" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Slug（URL别名）</label>
                        <input type="text" name="slug" id="category-slug" class="form-input" placeholder="留空将自动生成">
                    </div>

                    <div class="form-group">
                        <label class="form-label">描述</label>
                        <textarea name="description" id="category-description" class="form-textarea" rows="3"></textarea>
                    </div>

                    <div class="admin-form-actions">
                        <button type="submit" class="btn btn-primary">保存</button>
                        <button type="button" class="btn btn-outline" onclick="resetCategoryForm()">取消</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 分类列表 -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>分类列表</h2>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>名称</th>
                        <th>Slug</th>
                        <th>描述</th>
                        <th>文章数</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            暂无分类
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                        <td><code><?= htmlspecialchars($cat['slug']) ?></code></td>
                        <td><?= htmlspecialchars($cat['description'] ?: '-') ?></td>
                        <td><?= $cat['article_count'] ?></td>
                        <td>
                            <button onclick="editCategory('<?= $cat['id'] ?>', '<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['slug'], ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['description'] ?? '', ENT_QUOTES) ?>')" class="btn btn-sm btn-outline">编辑</button>
                            <button onclick="deleteCategory('<?= $cat['id'] ?>')" class="btn btn-sm btn-outline" style="color: #e53e3e;">删除</button>
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
function editCategory(id, name, slug, description) {
    document.getElementById('category-id').value = id;
    document.getElementById('category-name').value = name;
    document.getElementById('category-slug').value = slug;
    document.getElementById('category-description').value = description;
}

function resetCategoryForm() {
    document.getElementById('category-form').reset();
    document.getElementById('category-id').value = '';
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
