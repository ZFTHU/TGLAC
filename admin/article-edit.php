<?php
/**
 * 文章编辑页面
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

// 获取数据库连接
$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);

// 获取分类列表
$categoryModel = new Category($db);
$categories = $categoryModel->getAll();

// 获取文章（编辑模式）
$articleId = $_GET['id'] ?? null;
$article = null;

if ($articleId) {
    $articleModel = new Article($db);
    $article = $articleModel->getById($articleId);

    if (!$article) {
        header('HTTP/1.0 404 Not Found');
        echo '文章不存在';
        exit;
    }
}

$pageTitle = $article ? '编辑文章' : '新建文章';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1><?= $pageTitle ?></h1>
        <a href="/admin/articles.php" class="btn btn-outline">返回列表</a>
    </div>

    <div class="admin-card">
        <div class="admin-card-body">
            <form id="article-form" class="admin-form">
                <?php if ($article): ?>
                <input type="hidden" name="id" value="<?= $article['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">标题 *</label>
                    <input type="text" name="title" class="form-input" required
                           value="<?= htmlspecialchars($article['title'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Slug（URL别名）</label>
                    <input type="text" name="slug" class="form-input"
                           value="<?= htmlspecialchars($article['slug'] ?? '') ?>"
                           placeholder="留空将自动生成">
                </div>

                <div class="form-group">
                    <label class="form-label">分类 *</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">请选择分类</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($article['category_id'] ?? '') === $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 封面图片区域 - 带拖拽上传 -->
                <div class="form-group">
                    <label class="form-label">封面图片</label>
                    <div class="upload-section">
                        <input type="text" id="cover-image-url" name="cover_image" class="form-input"
                               value="<?= htmlspecialchars($article['cover_image'] ?? '') ?>"
                               placeholder="图片URL，或拖拽图片到下方区域上传">
                        <div class="drop-zone" id="cover-drop-zone" data-target="cover-image-url">
                            <div class="drop-zone-content">
                                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-bottom: 12px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <p style="margin-bottom: 8px; font-weight: 500;">拖拽图片到此处上传</p>
                                <p style="font-size: 0.875rem; color: var(--text-muted);">或点击选择文件</p>
                                <input type="file" id="cover-file-input" accept="image/*" style="display: none;">
                            </div>
                            <div class="preview-container" id="cover-preview" style="display: none;">
                                <img id="cover-preview-img" alt="预览">
                                <button type="button" class="btn btn-sm btn-danger" onclick="removeCoverImage(event)">移除</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">摘要</label>
                    <textarea name="excerpt" class="form-textarea" rows="2"
                              placeholder="文章摘要，留空将自动截取内容"><?= htmlspecialchars($article['excerpt'] ?? '') ?></textarea>
                </div>

                <!-- 文章内容区域 - 带图片插入按钮 -->
                <div class="form-group">
                    <label class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>内容 *</span>
                        <span style="font-size: 0.75rem; font-weight: normal; color: var(--text-muted);">支持一段文字+一张图片格式</span>
                    </label>
                    <textarea name="content" id="content-editor" class="form-textarea" rows="20" required
                              placeholder="支持Markdown格式。每行一个图片URL，或使用 ![描述](URL) 格式插入图片"><?= htmlspecialchars($article['content'] ?? '') ?></textarea>

                    <!-- 内容区域的图片上传工具 -->
                    <div class="content-upload-section">
                        <div class="drop-zone inline-drop-zone" id="content-drop-zone" data-target="content-editor">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span style="margin: 0 8px;">拖拽图片到此处插入到内容</span>
                            <button type="button" class="btn btn-sm btn-primary" id="content-upload-btn">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                选择图片
                            </button>
                            <input type="file" id="content-file-input" accept="image/*" multiple style="display: none;">
                        </div>
                        <div class="uploaded-images-preview" id="uploaded-images-preview"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">标签</label>
                    <input type="text" name="tags" class="form-input"
                           value="<?= htmlspecialchars($article['tags'] ?? '') ?>"
                           placeholder="多个标签用逗号分隔">
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="published" value="1"
                               <?= ($article['published'] ?? false) ? 'checked' : '' ?>>
                        <span>发布文章</span>
                    </label>
                </div>

                <div class="admin-form-actions">
                    <button type="submit" class="btn btn-primary" id="submit-btn">
                        <span id="submit-text"><?= $article ? '保存更改' : '创建文章' ?></span>
                    </button>
                    <a href="/admin/articles.php" class="btn btn-outline">取消</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
