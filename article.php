<?php
/**
 * 文章详情页
 * 布局：左栏（用户信息） | 中栏（文章内容） | 右栏（评论表单 + 评论列表）
 */

require_once __DIR__ . '/config/config.php';

// 检查安装状态
if (!isInstalled()) {
    header('Location: /install.php');
    exit;
}

// 获取文章ID
$articleId = $_GET['id'] ?? null;

if (!$articleId) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/404.html';
    exit;
}

// 获取数据库连接
$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);

// 获取文章详情
$articleModel = new Article($db);
$article = $articleModel->getById($articleId);

if (!$article) {
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/404.html';
    exit;
}

// 增加浏览次数
$articleModel->incrementViewCount($articleId);

// 获取评论
$commentModel = new Comment($db);
$comments = $commentModel->getByArticle($articleId);

// 获取相关文章
$relatedArticles = $articleModel->getRelated($articleId, $article['category_id'], 5);

// 获取分类（顶部导航已获取，但侧边栏也可能需要）
$categoryModel = new Category($db);
$categories = $categoryModel->getAll();

// 获取热门文章（右侧可能不需要，但先留着）
$popularArticles = $articleModel->getPopular(5);

// 获取公告（左栏）
$announcements = [];
try {
    $announcementModel = new Announcement($db);
    $announcements = $announcementModel->getAll(3);
} catch (Exception $e) {}

// 获取系统设置
$settingsModel = new Settings($db);
$settings = $settingsModel->getAll();

$pageTitle = $article['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="main-layout">
        <!-- 左栏：用户头像 + 名称 + 一言 + 公告 + 时间 + IP签名 -->
        <aside class="site-left-panel">
            <div class="site-info-card">
                <?php
                $defaultAvatarFile = glob(__DIR__ . '/img/tx/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE);
                $defaultAvatar = !empty($defaultAvatarFile) ? basename($defaultAvatarFile[0]) : '';
                ?>
                <div class="site-avatar" style="background: transparent; box-shadow: none; overflow: hidden;">
                    <img src="/img/tx/<?= $defaultAvatar ?>" alt="头像" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                </div>

                <?php if (isLoggedIn()): ?>
                <h2 class="site-name"><?= htmlspecialchars($_SESSION['user_name']) ?></h2>
                <p class="site-desc"><?= $_SESSION['user_role'] === 'admin' ? '网站管理员' : '注册用户' ?></p>
                <?php else: ?>
                <h2 class="site-name">游客</h2>
                <p class="site-desc">访客ID: <?= substr(session_id(), 0, 8) ?></p>
                <?php endif; ?>

                <!-- 一言 -->
                <div class="site-hitokoto">
                    <div class="hitokoto-quote-mark">&ldquo;</div>
                    <p id="hitokoto-text" class="hitokoto-content">正在寻找美好的句子...</p>
                    <div class="hitokoto-quote-mark-bottom">&rdquo;</div>
                </div>

                <!-- 公告 -->
                <?php if (!empty($announcements)): ?>
                <div class="left-announcement">
                    <?php foreach ($announcements as $ann): ?>
                    <a href="/pages/announcement.php?id=<?= $ann['id'] ?>" class="left-announcement-item">
                        <span class="left-announcement-title"><?= htmlspecialchars($ann['title']) ?></span>
                        <span class="left-announcement-date"><?= date('m-d', strtotime($ann['created_at'])) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- 时间 -->
                <div class="site-time">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span id="site-time-text">--:--:--</span>
                </div>

                <!-- IP签名 -->
                <div class="site-ip-sign">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"></path>
                    </svg>
                    <span id="site-ip-text">正在获取IP信息...</span>
                </div>
            </div>
        </aside>

        <!-- 中栏：文章内容 -->
        <main class="site-main-section">
            <article class="article-detail">
                <!-- 移动端返回键 -->
                <div class="article-back-btn-mobile">
                    <button onclick="history.back()" class="back-btn">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        返回
                    </button>
                </div>

                <!-- 文章头部 -->
                <header class="article-header" style="margin-bottom: 32px;">
                    <?php if (!empty($article['category_name'])): ?>
                    <span class="article-card-category" style="margin-bottom: 12px; display: inline-block;"><?= htmlspecialchars($article['category_name']) ?></span>
                    <?php endif; ?>

                    <h1 style="font-family: var(--font-serif); font-size: 2.5rem; font-weight: 700; line-height: 1.2; margin-bottom: 16px;"><?= htmlspecialchars($article['title']) ?></h1>

                    <div class="article-meta" style="display: flex; align-items: center; gap: 24px; color: var(--text-muted); font-size: 0.875rem;">
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <?= htmlspecialchars($article['author_name']) ?>
                        </span>
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <?= date('Y年m月d日', strtotime($article['created_at'])) ?>
                        </span>
                        <span style="display: flex; align-items: center; gap: 8px;">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <?= $article['view_count'] ?> 次阅读
                        </span>
                    </div>
                </header>

                <!-- 封面图 -->
                <?php if (!empty($article['cover_image'])): ?>
                <div class="article-cover-figure" style="margin-bottom: 32px; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);">
                    <img src="<?= htmlspecialchars($article['cover_image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" 
                         style="width: 100%; height: auto; display: block; cursor: zoom-in;" 
                         class="lightbox-trigger" loading="lazy">
                </div>
                <?php endif; ?>

                <!-- 文章内容 -->
                <div class="article-content" style="font-size: 1.125rem; line-height: 1.8; color: var(--text-primary);">
                    <?php
                    $contentVar = $article['content'];
                    $lines = explode("\n", $contentVar);
                    $paragraphBuffer = '';
                    $lineIndex = 0;
                    foreach ($lines as $line) {
                        $line = trim($line);
                        $lineIndex++;
                        // 检测图片URL行
                        if (preg_match('/^(https?:\/\/.+\.(jpg|jpeg|png|gif|webp|svg))$/i', $line)) {
                            if (!empty($paragraphBuffer)) { echo '<p>' . htmlspecialchars($paragraphBuffer) . '</p>'; $paragraphBuffer = ''; }
                            echo '<figure class="article-figure"><img src="' . htmlspecialchars($line) . '" alt="图片" loading="lazy" class="lightbox-trigger" style="cursor: zoom-in;"></figure>';
                            continue;
                        }
                        // 检测Markdown图片语法: ![alt](url)
                        if (preg_match('/^!\[([^\]]*)\]\(([^)]+)\)\s*$/i', $line, $imgMatches)) {
                            if (!empty($paragraphBuffer)) { echo '<p>' . htmlspecialchars($paragraphBuffer) . '</p>'; $paragraphBuffer = ''; }
                            $alt = htmlspecialchars(trim($imgMatches[1]));
                            $url = htmlspecialchars(trim($imgMatches[2]));
                            echo '<figure class="article-figure"><img src="' . $url . '" alt="' . ($alt ?: '图片') . '" loading="lazy" class="lightbox-trigger" style="cursor: zoom-in;">';
                            if ($alt) { echo '<figcaption>' . $alt . '</figcaption>'; }
                            echo '</figure>';
                            continue;
                        }
                        // 处理标题
                        if (preg_match('/^### (.*?)$/', $line, $matches)) {
                            if (!empty($paragraphBuffer)) { echo '<p>' . htmlspecialchars($paragraphBuffer) . '</p>'; $paragraphBuffer = ''; }
                            echo '<h3>' . htmlspecialchars($matches[1]) . '</h3>'; continue;
                        }
                        if (preg_match('/^## (.*?)$/', $line, $matches)) {
                            if (!empty($paragraphBuffer)) { echo '<p>' . htmlspecialchars($paragraphBuffer) . '</p>'; $paragraphBuffer = ''; }
                            echo '<h2>' . htmlspecialchars($matches[1]) . '</h2>'; continue;
                        }
                        if (preg_match('/^# (.*?)$/', $line, $matches)) {
                            if (!empty($paragraphBuffer)) { echo '<p>' . htmlspecialchars($paragraphBuffer) . '</p>'; $paragraphBuffer = ''; }
                            echo '<h1>' . htmlspecialchars($matches[1]) . '</h1>'; continue;
                        }
                        // 空行 - 输出段落
                        if (empty($line)) {
                            if (!empty($paragraphBuffer)) { echo '<p>' . htmlspecialchars($paragraphBuffer) . '</p>'; $paragraphBuffer = ''; }
                            continue;
                        }
                        // 处理粗体和斜体
                        $line = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $line);
                        $line = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $line);
                        $paragraphBuffer .= ($paragraphBuffer ? ' ' : '') . $line;
                    }
                    if (!empty($paragraphBuffer)) { echo '<p>' . htmlspecialchars($paragraphBuffer) . '</p>'; }
                    ?>
                </div>

                <!-- 标签 -->
                <?php if (!empty($article['tags'])): ?>
                <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border-color);">
                    <div class="tag-cloud">
                        <?php $tags = json_decode($article['tags'], true) ?: explode(',', $article['tags']);
                        foreach ($tags as $tag): $tag = trim($tag); if ($tag): ?>
                        <a href="#" class="tag"><?= htmlspecialchars($tag) ?></a>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 相关文章 -->
                <?php if (!empty($relatedArticles)): ?>
                <section class="related-articles" style="margin-top: 48px; padding-top: 32px; border-top: 1px solid var(--border-color);">
                    <h3 style="font-family: var(--font-serif); font-size: 1.5rem; margin-bottom: 24px;">相关文章</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px;">
                        <?php foreach ($relatedArticles as $related): ?>
                        <a href="/article.php?id=<?= $related['id'] ?>" class="related-article-card" style="text-decoration: none; background: var(--bg-white); border-radius: 12px; overflow: hidden; box-shadow: var(--shadow-sm); transition: transform 300ms ease, box-shadow 300ms ease; display: block;">
                            <?php if (!empty($related['cover_image'])): ?>
                            <img src="<?= htmlspecialchars($related['cover_image']) ?>" alt="<?= htmlspecialchars($related['title']) ?>" style="width: 100%; height: 140px; object-fit: cover; display: block;">
                            <?php else: ?>
                            <div style="width: 100%; height: 140px; background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); display: flex; align-items: center; justify-content: center; color: white;">
                                <svg width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                                </svg>
                            </div>
                            <?php endif; ?>
                            <div style="padding: 16px;">
                                <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--text-primary); margin: 0 0 8px 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?= htmlspecialchars($related['title']) ?></h4>
                                <div style="display: flex; align-items: center; gap: 4px; font-size: 0.75rem; color: var(--text-muted);">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <span><?= $related['view_count'] ?? 0 ?></span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
            </article>
        </main>

        <!-- 右栏：评论表单 + 评论列表 -->
        <aside class="site-right-panel">
            <!-- 评论表单 -->
            <div class="sidebar-widget sidebar-widget-wide">
                <h3 class="sidebar-widget-title">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    发表评论
                </h3>
                <form id="comment-form" style="margin-top: 16px; display: flex; gap: 10px; align-items: flex-start;">
                    <div class="form-group" style="flex: 1; margin-bottom: 0;">
                        <textarea id="comment_content" class="form-textarea" rows="1" placeholder="写下您的评论..." required style="height: 40px; min-height: 40px; resize: none; padding: 10px 12px;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="height: 40px; flex-shrink: 0;">发表评论</button>
                </form>
            </div>

            <!-- 评论列表 -->
            <div class="sidebar-widget sidebar-widget-wide">
                <h3 class="sidebar-widget-title">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path>
                    </svg>
                    评论 (<?= count($comments) ?>)
                </h3>
                <div class="comments-list" style="margin-top: 16px;">
                    <?php if (empty($comments)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 20px;">暂无评论，快来发表第一条评论吧！</p>
                    <?php else: ?>
                    <?php foreach ($comments as $comment):
                        $displayName = htmlspecialchars($comment['author_name']);
                    ?>
                    <div class="comment-item" style="padding: 20px 0; border-bottom: 1px solid var(--border-light);">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <?php if ($defaultAvatar): ?>
                            <img src="/img/tx/<?= $defaultAvatar ?>" alt="" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; flex-shrink: 0;">
                            <?php else: ?>
                            <div style="width: 28px; height: 28px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 600; flex-shrink: 0;"><?= htmlspecialchars(mb_substr($comment['author_name'], 0, 1)) ?></div>
                            <?php endif; ?>
                            <div style="flex: 1; display: flex; justify-content: space-between; align-items: center;">
                                <strong style="color: var(--text-primary);"><?= $displayName ?></strong>
                                <span style="color: var(--text-muted); font-size: 0.75rem;"><?= date('Y-m-d H:i', strtotime($comment['created_at'])) ?></span>
                            </div>
                        </div>
                        <p style="color: var(--text-secondary); line-height: 1.6; padding-left: 38px;"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
// ===== 图片灯箱 =====
(function initLightbox() {
    const images = document.querySelectorAll('.lightbox-trigger');
    if (images.length === 0) return;

    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    lightbox.innerHTML = `
        <div class="lightbox-overlay"></div>
        <div class="lightbox-content">
            <img class="lightbox-image" alt="">
            <button class="lightbox-close" aria-label="关闭">&times;</button>
        </div>
    `;
    document.body.appendChild(lightbox);

    const style = document.createElement('style');
    style.textContent = `
        .lightbox {
            position: fixed; inset: 0; z-index: 10000;
            display: none; align-items: center; justify-content: center;
        }
        .lightbox.active { display: flex; animation: fadeIn 200ms ease; }
        .lightbox-overlay { position: absolute; inset: 0; background: rgba(0, 0, 0, 0.9); backdrop-filter: blur(8px); }
        .lightbox-content { position: relative; max-width: 90vw; max-height: 90vh; z-index: 1; animation: zoomIn 300ms ease; }
        .lightbox-image { max-width: 100%; max-height: 90vh; border-radius: 8px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5); }
        .lightbox-close { position: absolute; top: -40px; right: -10px; width: 36px; height: 36px; border-radius: 50%; background: rgba(255, 255, 255, 0.1); color: white; border: none; font-size: 1.5rem; cursor: pointer; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes zoomIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .related-article-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1); }
        .article-content h1, .article-content h2, .article-content h3 {
            margin-top: 32px !important; margin-bottom: 16px !important; line-height: 1.3; padding-bottom: 8px;
        }
        .article-content h1 { font-size: 1.8rem; border-bottom: 2px solid var(--accent-color); padding-bottom: 12px; }
        .article-content h2 { font-size: 1.5rem; border-bottom: 1px solid var(--border-color); }
        .article-content h3 { font-size: 1.25rem; }
        .article-content p { margin-bottom: 24px; line-height: 1.9; animation: fadeInUp 500ms ease-out; }
        .article-content figure { margin: 40px auto; text-align: center; animation: fadeInUp 600ms ease-out; }
        .article-content figure img { max-width: 100%; height: auto; border-radius: 12px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12); transition: transform 300ms ease, box-shadow 300ms ease; }
        .article-content figure img:hover { transform: scale(1.02); box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2); }
        .article-content figcaption { margin-top: 12px; font-size: 0.875rem; color: var(--text-muted); font-style: italic; }
        .comment-item { padding: 20px !important; border-bottom: 1px solid var(--border-light) !important; border-radius: 12px; margin-bottom: 12px; transition: background-color 200ms ease; animation: fadeInUp 500ms ease-out; }
        .comment-item:hover { background-color: var(--bg-gray); }
    `;
    document.head.appendChild(style);

    const lightboxImage = lightbox.querySelector('.lightbox-image');
    const overlay = lightbox.querySelector('.lightbox-overlay');
    const closeBtn = lightbox.querySelector('.lightbox-close');

    function openLightbox(src) {
        lightboxImage.src = src;
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeLightbox() {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }
    images.forEach(img => img.addEventListener('click', function() { openLightbox(this.src); }));
    overlay.addEventListener('click', closeLightbox);
    closeBtn.addEventListener('click', closeLightbox);
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && lightbox.classList.contains('active')) closeLightbox();
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
