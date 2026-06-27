<?php
/**
 * 首页
 */

$pageTitle = '首页';
$isHomePage = true;
require_once __DIR__ . '/includes/header.php';

// 获取数据库连接
$dbConfig = getDatabaseConfig();
$db = Database::create($dbConfig);

// 获取系统设置
$settingsModel = new Settings($db);
$settings = $settingsModel->getAll();
$postsPerPage = $settings['posts_per_page'] ?? 10;

// 获取当前页码
$page = max(1, intval($_GET['page'] ?? 1));

// 获取文章列表
$articleModel = new Article($db);
$articles = $articleModel->getList($page, $postsPerPage);
$totalArticles = $articleModel->getCount();
$totalPages = ceil($totalArticles / $postsPerPage);

// 获取分类列表
$categoryModel = new Category($db);
$categories = $categoryModel->getAll();

// 获取热门文章
$popularArticles = $articleModel->getPopular(5);

// 获取精选文章（用于轮播）- 优先选择有封面图的
$featuredArticles = [];
$articlesWithImage = array_filter($articles, function($a) { return !empty($a['cover_image']); });
$articlesWithoutImage = array_filter($articles, function($a) { return empty($a['cover_image']); });
$featuredArticles = array_merge($articlesWithImage, $articlesWithoutImage);
$featuredArticles = array_slice($featuredArticles, 0, 3);

// 获取公告
$announcements = [];
try {
    $announcementModel = new Announcement($db);
    $announcements = $announcementModel->getAll(5);
} catch (Exception $e) {
    // 公告表可能不存在
}
?>

<div class="container">
    <!-- 主布局：左-中-右三栏 -->
    <div class="main-layout">
        <!-- 左侧：用户信息区（头像+名称+一言+公告+时间，位于看板娘上方） -->
        <aside class="site-left-panel">
            <div class="site-info-card">
                <?php
                $defaultAvatarFile = glob(__DIR__ . '/img/tx/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE);
                $defaultAvatar = !empty($defaultAvatarFile) ? basename($defaultAvatarFile[0]) : '';
                ?>
                <!-- 用户头像（使用默认头像） -->
                <div class="site-avatar" style="background: transparent; box-shadow: none; overflow: hidden;">
                    <img src="/img/tx/<?= $defaultAvatar ?>" alt="头像" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                </div>

                <!-- 用户名/游客 + 角色/ID -->
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

                <!-- 公告（移动到一言下方） -->
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

        <!-- 中间：文章列表 -->
        <main class="site-main-section">
            <!-- 文章列表 -->
            <div class="articles-section">
                <?php if (empty($articles)): ?>
                <div class="empty-state" style="text-align: center; padding: 60px 20px;">
                    <svg width="64" height="64" fill="none" stroke="var(--text-muted)" viewBox="0 0 24 24" style="margin-bottom: 16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 style="color: var(--text-secondary); margin-bottom: 8px;">暂无文章</h3>
                    <p style="color: var(--text-muted);">管理员还没有发布任何文章</p>
                </div>
                <?php else: ?>
                <div class="articles-grid">
                    <?php foreach ($articles as $index => $article): ?>
                    <article class="article-card <?= empty($article['cover_image']) ? 'article-card-no-image' : '' ?>">
                        <?php if (!empty($article['cover_image'])): ?>
                        <a href="/article.php?id=<?= $article['id'] ?>">
                            <img src="<?= $article['cover_image'] ?>" alt="<?= htmlspecialchars($article['title']) ?>" class="article-card-image">
                        </a>
                        <?php endif; ?>
                        <div class="article-card-content">
                            <?php if ($article['category_name']): ?>
                            <span class="article-card-category"><?= htmlspecialchars($article['category_name']) ?></span>
                            <?php endif; ?>
                            <h2 class="article-card-title">
                                <a href="/article.php?id=<?= $article['id'] ?>"><?= htmlspecialchars($article['title']) ?></a>
                            </h2>
                            <p class="article-card-excerpt"><?= htmlspecialchars($article['excerpt'] ?: substr(strip_tags($article['content']), 0, 150)) ?></p>
                            <div class="article-card-meta">
                                <span>
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <?= htmlspecialchars($article['author_name']) ?>
                                </span>
                                <span>
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <?= date('Y-m-d', strtotime($article['created_at'])) ?>
                                </span>
                                <span>
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <?= $article['view_count'] ?>
                                </span>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>

                <!-- 分页 -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>">上一页</a>
                    <?php else: ?>
                    <span class="disabled">上一页</span>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);

                    if ($startPage > 1): ?>
                    <a href="?page=1">1</a>
                    <?php if ($startPage > 2): ?>
                    <span>...</span>
                    <?php endif;
                    endif;

                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <?php if ($i === $page): ?>
                    <span class="active"><?= $i ?></span>
                    <?php else: ?>
                    <a href="?page=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                    <?php endfor;

                    if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                    <span>...</span>
                    <?php endif; ?>
                    <a href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>">下一页</a>
                    <?php else: ?>
                    <span class="disabled">下一页</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>

        <!-- 右侧：公告 + 分类 + 热门文章（加宽） -->
        <aside class="site-right-panel">
            <!-- 公告（加宽，预览100字） -->
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
                        <?php if (!empty($article['cover_image'])): ?>
                        <img src="<?= $article['cover_image'] ?>" alt="<?= htmlspecialchars($article['title']) ?>">
                        <?php endif; ?>
                        <div class="popular-list-content">
                            <a href="/article.php?id=<?= $article['id'] ?>" class="popular-list-title"><?= htmlspecialchars($article['title']) ?></a>
                            <div class="popular-list-meta">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <?= $article['view_count'] ?> 次阅读
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
