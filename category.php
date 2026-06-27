<?php
/**
 * 分类页面
 *   /category.php       -> 显示分类模块网格（4列 × 多行）
 *   /category.php?slug=xxx -> 显示指定分类下的文章列表（无轮播）
 */

$pageTitle = '分类';
require_once __DIR__ . '/includes/header.php';

// 获取数据库连接
$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);

// 获取系统设置
$settingsModel = new Settings($db);
$settings = $settingsModel->getAll();
$postsPerPage = $settings['posts_per_page'] ?? 10;

// 获取分类列表
$categoryModel = new Category($db);
$categories = $categoryModel->getAll();

// 初始化文章模型
$articleModel = new Article($db);

// 判断是否为具体分类页
$currentSlug = $_GET['slug'] ?? '';
$currentCategory = null;
$articles = [];
$totalArticles = 0;
$page = max(1, intval($_GET['page'] ?? 1));
$totalPages = 1;

if (!empty($currentSlug)) {
    $currentCategory = $categoryModel->getBySlug($currentSlug);
    if (!$currentCategory) {
        header('HTTP/1.0 404 Not Found');
        include __DIR__ . '/404.html';
        exit;
    }
    $articles = $articleModel->getList($page, $postsPerPage, $currentCategory['id']);
    $totalArticles = $articleModel->getCount($currentCategory['id']);
    $totalPages = ceil($totalArticles / $postsPerPage);
    $pageTitle = $currentCategory['name'];
}

// 获取热门文章
$popularArticles = $articleModel->getPopular(5);

// 获取公告
$announcements = [];
try {
    $announcementModel = new Announcement($db);
    $announcements = $announcementModel->getAll(5);
} catch (Exception $e) {}
?>

<div class="container">
    <div class="main-layout">
        <!-- 左侧：用户信息区（与首页一致） -->
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
                    <?php foreach (array_slice($announcements, 0, 2) as $ann): ?>
                    <a href="/pages/announcement.php?id=<?= $ann['id'] ?>" class="left-announcement-item">
                        <span class="left-announcement-title"><?= htmlspecialchars(mb_substr($ann['title'], 0, 20)) ?><?= mb_strlen($ann['title']) > 20 ? '...' : '' ?></span>
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

                <!-- IP签名（JS动态获取） -->
                <div class="site-ip-sign">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"></path>
                    </svg>
                    <span id="site-ip-text">正在获取IP信息...</span>
                </div>
            </div>
        </aside>

        <!-- 中间：分类模块网格 或 文章列表（无轮播） -->
        <main class="site-main-section">
            <?php if ($currentCategory): ?>
                <!-- 分类详情页：仅显示文章列表 -->
                <div class="page-header-section" style="margin-bottom: var(--spacing-lg); padding: var(--spacing-lg); background: var(--bg-white); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                    <!-- 移动端开发中提示 -->
                    <div class="category-dev-notice" style="display: none; padding: 8px 12px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; margin-bottom: 12px; font-size: 0.8rem; color: #b45309;">
                        此页面正在开发中，暂以文章列表形式展示
                    </div>
                    <h1 style="font-family: var(--font-serif); font-size: 1.75rem; color: var(--text-primary); margin: 0 0 4px 0;"><?= htmlspecialchars($currentCategory['name']) ?></h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">共 <?= $totalArticles ?> 篇文章<?php if (!empty($currentCategory['description'])) echo ' · ' . htmlspecialchars($currentCategory['description']) ?></p>
                </div>

                <?php if (empty($articles)): ?>
                <div class="sidebar-widget" style="text-align: center; padding: var(--spacing-2xl);">
                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted); margin-bottom: var(--spacing-md);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-4.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <p style="color: var(--text-muted); margin: 0;">该分类下暂无文章</p>
                </div>
                <?php else: ?>
                <div class="article-list">
                    <?php foreach ($articles as $article): ?>
                    <article class="article-card" data-href="/article.php?id=<?= $article['id'] ?>">
                        <?php if (!empty($article['cover_image'])): ?>
                        <a href="/article.php?id=<?= $article['id'] ?>" class="article-card-cover">
                            <img src="<?= $article['cover_image'] ?>" alt="<?= htmlspecialchars($article['title']) ?>">
                        </a>
                        <?php endif; ?>
                        <div class="article-card-body">
                            <h2 class="article-card-title">
                                <a href="/article.php?id=<?= $article['id'] ?>"><?= htmlspecialchars($article['title']) ?></a>
                            </h2>
                            <div class="article-card-meta">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span><?= date('Y-m-d', strtotime($article['created_at'])) ?></span>
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <span><?= $article['view_count'] ?? 0 ?> 次阅读</span>
                            </div>
                            <?php if (!empty($article['excerpt'])): ?>
                            <p class="article-card-excerpt"><?= htmlspecialchars(mb_substr($article['excerpt'], 0, 120)) ?><?= mb_strlen($article['excerpt']) > 120 ? '...' : '' ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?slug=<?= htmlspecialchars($currentSlug) ?>&page=<?= $page - 1 ?>">上一页</a>
                    <?php else: ?>
                    <span class="disabled">上一页</span>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);

                    if ($startPage > 1): ?>
                    <a href="?slug=<?= htmlspecialchars($currentSlug) ?>&page=1">1</a>
                    <?php if ($startPage > 2): ?>
                    <span>...</span>
                    <?php endif;
                    endif;

                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <?php if ($i === $page): ?>
                    <span class="active"><?= $i ?></span>
                    <?php else: ?>
                    <a href="?slug=<?= htmlspecialchars($currentSlug) ?>&page=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                    <?php endfor;

                    if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                    <span>...</span>
                    <?php endif; ?>
                    <a href="?slug=<?= htmlspecialchars($currentSlug) ?>&page=<?= $totalPages ?>"><?= $totalPages ?></a>
                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>
                    <a href="?slug=<?= htmlspecialchars($currentSlug) ?>&page=<?= $page + 1 ?>">下一页</a>
                    <?php else: ?>
                    <span class="disabled">下一页</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>

            <?php else: ?>
                <!-- 分类总览页：分类模块网格 -->
                <div class="page-header-section" style="margin-bottom: var(--spacing-lg); padding: var(--spacing-lg); background: var(--bg-white); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                    <h1 style="font-family: var(--font-serif); font-size: 1.75rem; color: var(--text-primary); margin: 0 0 4px 0;">分类总览</h1>
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">共 <?= count($categories) ?> 个分类 · 点击进入查看文章</p>
                </div>

                <?php if (empty($categories)): ?>
                <div class="sidebar-widget" style="text-align: center; padding: var(--spacing-2xl);">
                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted); margin-bottom: var(--spacing-md);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <p style="color: var(--text-muted); margin: 0 0 var(--spacing-md) 0;">暂无分类</p>
                    <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0;">请在后台管理创建分类</p>
                </div>
                <?php else: ?>
                <div class="category-module-grid-page">
                    <?php
                    $categoryIcons = [
                        'default' => '📁', '源码' => '💻', '图片' => '🖼️', '分享' => '💬',
                        '问题' => '❓', '交流' => '🌐', '教程' => '📖', '资讯' => '📰',
                        '技术' => '⚙️', '其他' => '📦', '默认' => '📁', '生活' => '🏠',
                        '日记' => '📝', '文章' => '📄', '博客' => '📰'
                    ];
                    $defaultIcon = '📂';
                    foreach ($categories as $cat):
                        $slug = $cat['slug'] ?? '';
                        $icon = $categoryIcons[$slug] ?? $categoryIcons[$cat['name']] ?? $defaultIcon;
                        $count = $cat['article_count'] ?? 0;
                    ?>
                    <a href="/category.php?slug=<?= $slug ?>" class="category-module-item category-module-item-page">
                        <span class="category-module-icon"><?= $icon ?></span>
                        <span class="category-module-name"><?= htmlspecialchars($cat['name']) ?></span>
                        <span class="category-module-count"><?= $count ?> 篇</span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>

        <!-- 右侧：公告列表 -->
        <aside class="site-right-panel">
            <!-- 公告 -->
            <?php if (!empty($announcements)): ?>
            <div class="sidebar-widget sidebar-widget-wide">
                <h3 class="sidebar-widget-title">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                    </svg>
                    公告
                </h3>
                <div class="announcement-list">
                    <?php foreach ($announcements as $ann): ?>
                    <a href="/pages/announcement.php?id=<?= $ann['id'] ?>" class="announcement-item <?= $ann['is_top'] ? 'announcement-top' : '' ?>">
                        <?php if ($ann['is_top']): ?>
                        <span class="announcement-badge">置顶</span>
                        <?php endif; ?>
                        <h4 class="announcement-title"><?= htmlspecialchars($ann['title']) ?></h4>
                        <p class="announcement-content"><?= htmlspecialchars(mb_substr($ann['content'], 0, 100)) ?><?= mb_strlen($ann['content']) > 100 ? '...' : '' ?></p>
                        <span class="announcement-date"><?= date('m-d H:i', strtotime($ann['created_at'])) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 分类列表 -->
            <div class="sidebar-widget sidebar-widget-wide hide-on-mobile">
                <h3 class="sidebar-widget-title">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    分类
                </h3>
                <ul class="category-list">
                    <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="/category.php?slug=<?= $cat['slug'] ?>">
                            <span><?= htmlspecialchars($cat['name']) ?></span>
                            <span class="category-count"><?= $cat['article_count'] ?? 0 ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- 热门文章 -->
            <div class="sidebar-widget sidebar-widget-wide hide-on-mobile">
                <h3 class="sidebar-widget-title">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 20c0-2 2-3.5 2-3.5s2 1.5 2 3.5 2 2 2 2-1 1.5-2.343-3.343z"></path>
                    </svg>
                    热门文章
                </h3>
                <ul class="popular-list">
                    <?php foreach ($popularArticles as $article): ?>
                    <li>
                        <div class="popular-list-content">
                            <a href="/article.php?id=<?= $article['id'] ?>" class="popular-list-title"><?= htmlspecialchars($article['title']) ?></a>
                            <div class="popular-list-meta">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <?= $article['view_count'] ?? 0 ?> 次阅读
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
